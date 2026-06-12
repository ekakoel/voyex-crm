<?php

namespace App\Support;

use App\Models\Destination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LocationResolver
{
    public function enrichFromGoogleMapsUrl(array &$payload, bool $overwriteResolvedFields = false): void
    {
        $url = trim((string) ($payload['google_maps_url'] ?? ''));
        if ($url === '') {
            return;
        }

        // Debugging helper: temporary resolve trace in logs when enabled.
        // Enable by setting APP_LOCATION_RESOLVE_DEBUG=1 (short-term for short-link coverage).
        $debugEnabled = (string) (env('APP_LOCATION_RESOLVE_DEBUG', '0')) === '1';

        $resolvedUrl = $this->resolveGoogleMapsRedirectUrl($url);



        // 1) Try extract coordinates from original and effective URLs.
        $coordinates = $this->extractCoordinatesFromGoogleMapsUrl($url)
            ?? ($resolvedUrl !== null ? $this->extractCoordinatesFromGoogleMapsUrl($resolvedUrl) : null);

        if ($debugEnabled) {
            try {
                logger()->warning('LocationResolver resolve trace', [
                    'input_google_maps_url' => $url,
                    'resolved_url' => $resolvedUrl,
                    'coordinates_extracted' => $coordinates,
                    'hostname' => parse_url($resolvedUrl ?? $url, PHP_URL_HOST),
                    'timestamp' => now()->toIso8601String(),
                ]);
            } catch (\Throwable $e) {
                // ignore logging failure
            }
        }




        // 2) Fallback: try extract coordinates from full URL fragment/path even if not matched earlier.
        if (! $coordinates) {
            $coordinates = $this->extractCoordinatesFromGoogleMapsUrl($resolvedUrl ?? $url);
        }

        // 3) Fallback: try geocoding using place/title search query.
        if (! $coordinates) {
            $query = $this->extractSearchQueryFromGoogleMapsUrl($resolvedUrl ?? $url);
            if ($query !== null) {
                $coordinates = $this->geocodeSearchQuery($query);
            }
        }

        // 4) Last fallback: if URL contains a valid @lat,lng segment, parse it directly.
        //    (Some Google Maps URLs keep the @lat,lng segment in the path and may be missed by earlier parsing.)
        if (! $coordinates) {
            $urlDecoded = urldecode($resolvedUrl ?? $url);
            if (preg_match('/@(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/', $urlDecoded, $m) === 1) {
                $coordinates = $this->normalizeCoordinates((float) $m[1], (float) $m[2]);
            }
        }


        if (! $coordinates) {
            return;
        }

        [$latitude, $longitude] = $coordinates;
        $payload['latitude'] = $latitude;
        $payload['longitude'] = $longitude;

        $details = $this->reverseGeocode($latitude, $longitude);

        $this->fillResolvedField($payload, 'city', $details['city'] ?? null, $overwriteResolvedFields);
        $this->fillResolvedField($payload, 'province', $details['province'] ?? null, $overwriteResolvedFields);
        $this->fillResolvedField($payload, 'country', $details['country'] ?? null, $overwriteResolvedFields);
        $this->fillResolvedField($payload, 'address', $details['address'] ?? null, $overwriteResolvedFields);
        $this->fillResolvedField($payload, 'location', $details['location'] ?? null, $overwriteResolvedFields);

        if (empty($payload['timezone'])) {
            $timezone = $this->resolveTimezone($latitude, $longitude);
            if ($timezone !== null && $timezone !== '') {
                $payload['timezone'] = $timezone;
            }
        }
    }

    public function resolveDestinationId(array &$payload, bool $autoCreate = true): void
    {
        $destinationId = (int) ($payload['destination_id'] ?? 0);
        if ($destinationId > 0) {
            return;
        }

        $province = trim((string) ($payload['province'] ?? ''));
        if ($province === '') {
            return;
        }

        $destination = Destination::query()
            ->whereRaw('LOWER(province) = ?', [mb_strtolower($province)])
            ->first();

        if (! $destination && $autoCreate) {
            $city = trim((string) ($payload['city'] ?? ''));
            $country = trim((string) ($payload['country'] ?? ''));
            $timezone = trim((string) ($payload['timezone'] ?? ''));
            $location = trim((string) ($payload['location'] ?? $province));
            $latitude = isset($payload['latitude']) && $payload['latitude'] !== '' ? (float) $payload['latitude'] : null;
            $longitude = isset($payload['longitude']) && $payload['longitude'] !== '' ? (float) $payload['longitude'] : null;

            $destination = Destination::query()->create([
                'code' => $this->generateDestinationCode(),
                'name' => $province,
                'slug' => Str::slug($province),
                'location' => $location !== '' ? $location : $province,
                'city' => $city !== '' ? $city : null,
                'province' => $province,
                'country' => $country !== '' ? $country : null,
                'timezone' => $timezone !== '' ? $timezone : null,
                'latitude' => is_finite((float) $latitude) ? $latitude : null,
                'longitude' => is_finite((float) $longitude) ? $longitude : null,
                'is_active' => true,
            ]);
        }

        if ($destination) {
            $payload['destination_id'] = (int) $destination->id;
        }
    }

    public function extractCoordinatesFromGoogleMapsUrl(string $url): ?array
    {
        $url = urldecode($url);
        if (preg_match('/@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/', $url, $matches) === 1) {
            return $this->normalizeCoordinates((float) $matches[1], (float) $matches[2]);
        }

        if (preg_match('/!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/', $url, $matches) === 1) {
            return $this->normalizeCoordinates((float) $matches[1], (float) $matches[2]);
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query)) {
            return null;
        }

        parse_str($query, $queryParameters);
        foreach (['q', 'query', 'll', 'center'] as $key) {
            $value = $queryParameters[$key] ?? null;
            if (! is_string($value)) {
                continue;
            }
            if (preg_match('/(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/', $value, $matches) === 1) {
                return $this->normalizeCoordinates((float) $matches[1], (float) $matches[2]);
            }
        }

        return null;
    }

    public function reverseGeocode(float $latitude, float $longitude): array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'voyex-crm/1.0'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'addressdetails' => 1,
                    'zoom' => 18,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $address = (array) ($response->json('address') ?? []);
            $city = $this->resolveCityRegion($address);
            $province = trim((string) ($address['state'] ?? $address['region'] ?? $address['state_district'] ?? ''));
            $country = trim((string) ($address['country'] ?? ''));
            $road = trim((string) ($address['road'] ?? ''));
            $suburb = trim((string) ($address['suburb'] ?? $address['neighbourhood'] ?? ''));
            $displayName = trim((string) ($response->json('display_name') ?? ''));

            $locationParts = array_filter([$city, $province]);
            $location = $locationParts !== [] ? implode(', ', $locationParts) : null;
            $addressParts = array_filter([$road, $suburb, $city, $province, $country]);
            $resolvedAddress = $addressParts !== [] ? implode(', ', $addressParts) : ($displayName !== '' ? Str::limit($displayName, 255, '') : null);

            return [
                'city' => $city !== '' ? $city : null,
                'province' => $province !== '' ? $province : null,
                'country' => $country !== '' ? $country : null,
                'address' => $resolvedAddress,
                'location' => $location,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function resolveGoogleMapsRedirectUrl(string $url): ?string
    {
        if (! $this->isAllowedGoogleMapsUrl($url)) {
            return null;
        }

        try {
            $effectiveUrl = null;
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'voyex-crm/1.0'])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'track_redirects' => true,
                    ],
                    'on_stats' => function ($stats) use (&$effectiveUrl): void {
                        $uri = $stats->getEffectiveUri();
                        if ($uri) {
                            $effectiveUrl = (string) $uri;
                        }
                    },
                ])
                ->get($url);

            if (is_string($effectiveUrl) && trim($effectiveUrl) !== '' && $effectiveUrl !== $url) {
                return $effectiveUrl;
            }

            $redirectHistory = $response->header('X-Guzzle-Redirect-History');
            if (is_string($redirectHistory) && trim($redirectHistory) !== '') {
                $parts = array_values(array_filter(array_map('trim', explode(',', $redirectHistory))));
                $last = end($parts);
                if (is_string($last) && $last !== '') {
                    return $last;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function isAllowedGoogleMapsUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        return $host === 'maps.app.goo.gl'
            || $host === 'goo.gl'
            || $host === 'google.com'
            || $host === 'www.google.com'
            || $host === 'maps.google.com'
            || $host === 'm.google.com'
            || str_ends_with($host, '.goo.gl')
            || preg_match('/(^|\.)google\.[a-z.]+$/', $host) === 1;
    }

    private function extractSearchQueryFromGoogleMapsUrl(string $url): ?string

    {
        $url = urldecode($url);
        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $queryParameters);
            foreach (['q', 'query'] as $key) {
                $value = trim((string) ($queryParameters[$key] ?? ''));
                if ($value !== '' && preg_match('/^-?\d{1,3}\.\d+,\s*-?\d{1,3}\.\d+$/', $value) !== 1) {
                    return $value;
                }
            }
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return null;
        }

        if (preg_match('#/(?:maps/)?(?:place|search)/([^/@?]+)#', $path, $matches) === 1) {
            $place = trim(str_replace('+', ' ', urldecode($matches[1])));
            return $place !== '' ? $place : null;
        }

        return null;
    }

    private function geocodeSearchQuery(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'voyex-crm/1.0'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'q' => $query,
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $first = $response->json('0');
            if (! is_array($first)) {
                return null;
            }

            $latitude = isset($first['lat']) ? (float) $first['lat'] : null;
            $longitude = isset($first['lon']) ? (float) $first['lon'] : null;
            if ($latitude === null || $longitude === null) {
                return null;
            }

            return $this->normalizeCoordinates($latitude, $longitude);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeCoordinates(float $latitude, float $longitude): ?array
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [$latitude, $longitude];
    }

    private function resolveCityRegion(array $address): string
    {
        $city = $this->firstAddressValue($address, ['city', 'town', 'municipality']);
        if ($city !== '') {
            return $city;
        }

        // Fallback ke wilayah tingkat kabupaten/regency jika nama kota tidak tersedia.
        $region = $this->firstAddressValue($address, ['county', 'city_district', 'district']);
        if ($region !== '') {
            return $region;
        }

        return $this->firstAddressValue($address, ['village', 'suburb', 'neighbourhood']);
    }

    private function firstAddressValue(array $address, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($address[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public function resolveTimezone(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(8)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current' => 'temperature_2m',
                'forecast_days' => 1,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $timezone = trim((string) ($response->json('timezone') ?? ''));
            return $timezone !== '' ? $timezone : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function fillResolvedField(array &$payload, string $key, mixed $value, bool $overwrite): void
    {
        if (! array_key_exists($key, $payload)) {
            return;
        }

        if ($value === null) {
            return;
        }

        if ($overwrite) {
            $payload[$key] = $value;
            return;
        }

        if (! empty($payload[$key])) {
            return;
        }

        $payload[$key] = $value;
    }

    private function generateDestinationCode(): string
    {
        do {
            $code = 'DST-' . strtoupper(Str::random(6));
        } while (Destination::query()->where('code', $code)->exists());

        return $code;
    }
}
