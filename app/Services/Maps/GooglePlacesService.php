<?php

namespace App\Services\Maps;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GooglePlacesService
{
    private string $apiKey;

    private string $baseUrl;

    private int $timeout;

    private int $connectTimeout;

    private int $retryTimes;

    private int $retrySleepMs;

    private int $nextPageDelayMs;

    public function __construct()
    {
        $this->apiKey = trim((string) config('services.google_maps.places_api_key', ''));
        $this->baseUrl = rtrim((string) config('services.google_maps.places_base_url', 'https://places.googleapis.com/v1'), '/');
        $this->timeout = max(5, (int) config('services.google_maps.places_timeout', 12));
        $this->connectTimeout = max(2, (int) config('services.google_maps.places_connect_timeout', 5));
        $this->retryTimes = max(0, (int) config('services.google_maps.places_retry_times', 2));
        $this->retrySleepMs = max(0, (int) config('services.google_maps.places_retry_sleep_ms', 250));
        $this->nextPageDelayMs = max(0, (int) config('services.google_maps.places_next_page_delay_ms', 1500));
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchTouristAttractions(
        string $textQuery,
        int $maxResults = 60,
        string $languageCode = 'id',
        string $regionCode = 'ID'
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Google Maps Places API key is not configured.');
        }

        $maxResults = max(1, min(200, $maxResults));
        $results = [];
        $nextPageToken = null;
        $fieldMask = implode(',', [
            'places.id',
            'places.name',
            'places.displayName',
            'places.formattedAddress',
            'places.googleMapsUri',
            'places.location',
            'places.addressComponents',
            'places.primaryType',
            'places.types',
            'nextPageToken',
        ]);

        do {
            $requestBody = [
                'textQuery' => $textQuery,
                'languageCode' => $languageCode,
                'regionCode' => $regionCode,
                'pageSize' => min(20, $maxResults - count($results)),
            ];
            if (is_string($nextPageToken) && $nextPageToken !== '') {
                $requestBody['pageToken'] = $nextPageToken;
            }

            $response = $this->client($fieldMask)->post($this->baseUrl . '/places:searchText', $requestBody);
            if (! $response->successful()) {
                $message = (string) ($response->json('error.message') ?? $response->body());
                throw new RuntimeException(
                    sprintf('Google Places request failed (%d): %s', $response->status(), $message)
                );
            }

            $places = $response->json('places');
            if (is_array($places)) {
                foreach ($places as $place) {
                    if (count($results) >= $maxResults) {
                        break;
                    }

                    if (is_array($place)) {
                        $results[] = $this->normalizePlace($place);
                    }
                }
            }

            $nextPageToken = (string) ($response->json('nextPageToken') ?? '');
            if ($nextPageToken !== '' && count($results) < $maxResults && $this->nextPageDelayMs > 0) {
                usleep($this->nextPageDelayMs * 1000);
            }
        } while ($nextPageToken !== '' && count($results) < $maxResults);

        return $results;
    }

    private function client(string $fieldMask): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry($this->retryTimes, $this->retrySleepMs)
            ->acceptJson()
            ->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => $fieldMask,
            ]);
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>
     */
    private function normalizePlace(array $place): array
    {
        $displayName = '';
        $displayNamePayload = $place['displayName'] ?? null;
        if (is_array($displayNamePayload)) {
            $displayName = trim((string) ($displayNamePayload['text'] ?? ''));
        }
        if ($displayName === '') {
            $displayName = trim((string) ($place['displayName'] ?? ''));
        }

        $addressComponents = is_array($place['addressComponents'] ?? null) ? $place['addressComponents'] : [];

        $city = $this->resolveAddressComponent($addressComponents, [
            'locality',
            'administrative_area_level_2',
            'sublocality_level_1',
            'postal_town',
        ]);
        $province = $this->resolveAddressComponent($addressComponents, ['administrative_area_level_1']);
        $country = $this->resolveAddressComponent($addressComponents, ['country']);

        $placeId = trim((string) ($place['id'] ?? ''));
        if ($placeId === '') {
            $nameToken = trim((string) ($place['name'] ?? ''));
            if ($nameToken !== '' && str_contains($nameToken, '/')) {
                $parts = explode('/', $nameToken);
                $placeId = trim((string) end($parts));
            }
        }

        $lat = $this->toFloat($place['location']['latitude'] ?? null);
        $lng = $this->toFloat($place['location']['longitude'] ?? null);

        return [
            'google_place_id' => $placeId !== '' ? $placeId : null,
            'name' => $displayName !== '' ? $displayName : null,
            'address' => trim((string) ($place['formattedAddress'] ?? '')) ?: null,
            'city' => $city !== '' ? $city : null,
            'province' => $province !== '' ? $province : null,
            'country' => $country !== '' ? $country : null,
            'google_maps_url' => trim((string) ($place['googleMapsUri'] ?? '')) ?: null,
            'latitude' => $lat,
            'longitude' => $lng,
            'primary_type' => trim((string) ($place['primaryType'] ?? '')) ?: null,
            'types' => is_array($place['types'] ?? null) ? array_values(array_filter($place['types'], 'is_string')) : [],
        ];
    }

    /**
     * @param  array<int, mixed>  $components
     * @param  array<int, string>  $targetTypes
     */
    private function resolveAddressComponent(array $components, array $targetTypes): string
    {
        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }
            $componentTypes = is_array($component['types'] ?? null) ? $component['types'] : [];
            if (array_intersect($targetTypes, $componentTypes) === []) {
                continue;
            }

            $longText = trim((string) ($component['longText'] ?? ''));
            if ($longText !== '') {
                return $longText;
            }

            $longName = trim((string) ($component['long_name'] ?? ''));
            if ($longName !== '') {
                return $longName;
            }

            $shortText = trim((string) ($component['shortText'] ?? ''));
            if ($shortText !== '') {
                return $shortText;
            }
        }

        return '';
    }

    private function toFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;
        if (! is_finite($float)) {
            return null;
        }

        return $float;
    }
}
