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

        $coordinates = $this->extractCoordinatesFromGoogleMapsUrl($url);
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
        if (preg_match('/@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/', $url, $matches) === 1) {
            return [(float) $matches[1], (float) $matches[2]];
        }

        if (preg_match('/!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/', $url, $matches) === 1) {
            return [(float) $matches[1], (float) $matches[2]];
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query)) {
            return null;
        }

        parse_str($query, $queryParameters);
        $q = $queryParameters['q'] ?? $queryParameters['query'] ?? null;
        if (! is_string($q)) {
            return null;
        }

        if (preg_match('/(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/', $q, $matches) !== 1) {
            return null;
        }

        return [(float) $matches[1], (float) $matches[2]];
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

