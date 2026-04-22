<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\TouristAttraction;
use App\Services\Maps\GooglePlacesService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TouristAttractionGoogleSyncService
{
    /**
     * @return array<string, array{label: string, queries: array<int, string>}>
     */
    public static function categoryOptions(): array
    {
        return [
            'beach_marine' => [
                'label' => 'Beach / Marine',
                'queries' => ['beach', 'marine tourism'],
            ],
            'waterfall' => [
                'label' => 'Waterfall',
                'queries' => ['waterfall'],
            ],
            'adventure' => [
                'label' => 'Adventure',
                'queries' => ['adventure tourism', 'outdoor adventure'],
            ],
            'mountain_hiking' => [
                'label' => 'Mountain / Hiking',
                'queries' => ['mountain', 'hiking trail'],
            ],
            'culture_history' => [
                'label' => 'Culture / History',
                'queries' => ['cultural attraction', 'historical landmark'],
            ],
            'nature_park' => [
                'label' => 'Nature Park',
                'queries' => ['nature park', 'national park'],
            ],
            'family_recreation' => [
                'label' => 'Family Recreation',
                'queries' => ['family recreation', 'theme park'],
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, provinces: array<int, string>}>
     */
    public static function islandOptions(): array
    {
        return [
            'sumatra' => [
                'label' => 'Sumatra',
                'provinces' => ['Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau', 'Jambi', 'Sumatera Selatan', 'Kepulauan Bangka Belitung', 'Bengkulu', 'Lampung'],
            ],
            'java' => [
                'label' => 'Jawa',
                'provinces' => ['Banten', 'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur'],
            ],
            'bali' => [
                'label' => 'Bali',
                'provinces' => ['Bali'],
            ],
            'nusa_tenggara' => [
                'label' => 'Nusa Tenggara',
                'provinces' => ['Nusa Tenggara Barat', 'Nusa Tenggara Timur'],
            ],
            'kalimantan' => [
                'label' => 'Kalimantan',
                'provinces' => ['Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara'],
            ],
            'sulawesi' => [
                'label' => 'Sulawesi',
                'provinces' => ['Sulawesi Utara', 'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan', 'Sulawesi Tenggara'],
            ],
            'maluku' => [
                'label' => 'Maluku',
                'provinces' => ['Maluku', 'Maluku Utara'],
            ],
            'papua' => [
                'label' => 'Papua',
                'provinces' => ['Papua', 'Papua Barat', 'Papua Barat Daya', 'Papua Tengah', 'Papua Pegunungan', 'Papua Selatan'],
            ],
        ];
    }

    public function __construct(
        private readonly GooglePlacesService $googlePlacesService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function syncDestination(Destination $destination, array $options = []): array
    {
        $configuredMaxResults = (int) config('services.google_maps.places_default_max_results', 60);
        $requestedMaxResults = (int) ($options['max_results'] ?? 0);
        $maxResults = $requestedMaxResults > 0 ? $requestedMaxResults : $configuredMaxResults;

        $languageCode = trim((string) ($options['language_code'] ?? ''));
        if ($languageCode === '') {
            $languageCode = (string) config('services.google_maps.places_default_language', 'id');
        }

        $regionCode = trim((string) ($options['region_code'] ?? ''));
        if ($regionCode === '') {
            $regionCode = (string) config('services.google_maps.places_default_region', 'ID');
        }
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $selectedCategoryKeys = is_array($options['category_keys'] ?? null) ? array_values($options['category_keys']) : [];
        $selectedIslandKey = trim((string) ($options['island_key'] ?? ''));

        $queries = $this->buildQueries(
            destination: $destination,
            customQuery: trim((string) ($options['query'] ?? '')),
            categoryKeys: $selectedCategoryKeys,
            islandKey: $selectedIslandKey
        );
        $queryCount = max(1, count($queries));
        $perQueryLimit = max(5, (int) ceil($maxResults / $queryCount));
        $perQueryLimit = min($perQueryLimit, $maxResults);
        $maxResults = max(1, min(200, $maxResults));

        $allowedIslandProvinces = $this->resolveIslandProvinceMap($selectedIslandKey);
        $rows = [];
        $seenPlaceIds = [];
        foreach ($queries as $query) {
            $batch = $this->googlePlacesService->searchTouristAttractions($query, $perQueryLimit, $languageCode, $regionCode);
            foreach ($batch as $row) {
                $placeId = trim((string) ($row['google_place_id'] ?? ''));
                if ($placeId === '' || isset($seenPlaceIds[$placeId])) {
                    continue;
                }

                if (! $this->passesIslandFilter($row, $allowedIslandProvinces)) {
                    continue;
                }

                $seenPlaceIds[$placeId] = true;
                $rows[] = $row;
                if (count($rows) >= $maxResults) {
                    break 2;
                }
            }
        }

        $summary = [
            'destination_id' => (int) $destination->id,
            'destination_name' => (string) $destination->name,
            'query' => implode(' | ', $queries),
            'fetched' => count($rows),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'invalid' => 0,
            'dry_run' => $dryRun,
            'items' => [],
        ];

        foreach ($rows as $row) {
            $mapped = $this->mapPlaceToPayload($destination, $row);
            if ($mapped === null) {
                $summary['invalid']++;
                continue;
            }

            if ($dryRun) {
                $summary['items'][] = [
                    'name' => $mapped['name'],
                    'google_place_id' => $mapped['google_place_id'],
                    'city' => $mapped['city'],
                    'province' => $mapped['province'],
                    'latitude' => $mapped['latitude'],
                    'longitude' => $mapped['longitude'],
                ];
                continue;
            }

            $result = $this->persistAttraction($mapped);
            $summary[$result]++;
        }

        return $summary;
    }

    private function defaultQuery(Destination $destination): string
    {
        $parts = array_filter([
            trim((string) $destination->city),
            trim((string) $destination->province),
            trim((string) ($destination->country ?: 'Indonesia')),
        ]);
        $locationContext = implode(', ', $parts);

        return 'tourist attractions in ' . ($locationContext !== '' ? $locationContext : (string) $destination->name);
    }

    /**
     * @param  array<int, string>  $categoryKeys
     * @return array<int, string>
     */
    private function buildQueries(
        Destination $destination,
        string $customQuery,
        array $categoryKeys,
        string $islandKey
    ): array {
        $categoryMap = self::categoryOptions();
        $islandMap = self::islandOptions();
        $islandLabel = $islandMap[$islandKey]['label'] ?? null;

        if ($customQuery !== '') {
            $query = $customQuery;
            if (is_string($islandLabel) && $islandLabel !== '') {
                $query .= ' in ' . $islandLabel . ', Indonesia';
            }

            return [trim($query)];
        }

        $locationBits = array_filter([
            trim((string) $destination->city),
            trim((string) $destination->province),
            trim((string) ($destination->country ?: 'Indonesia')),
        ]);
        if (is_string($islandLabel) && $islandLabel !== '') {
            $locationBits = [trim($islandLabel), 'Indonesia'];
        }
        $locationContext = implode(', ', $locationBits);
        $locationContext = trim($locationContext) !== '' ? $locationContext : 'Indonesia';

        $queries = [];
        foreach ($categoryKeys as $key) {
            $phrases = $categoryMap[(string) $key]['queries'] ?? null;
            if (! is_array($phrases)) {
                continue;
            }

            foreach ($phrases as $phrase) {
                $phrase = trim((string) $phrase);
                if ($phrase === '') {
                    continue;
                }
                $queries[] = sprintf('%s in %s', $phrase, $locationContext);
            }
        }

        if ($queries === []) {
            $queries[] = 'tourist attractions in ' . $locationContext;
        }

        return array_values(array_unique($queries));
    }

    /**
     * @return array<string, bool>
     */
    private function resolveIslandProvinceMap(string $islandKey): array
    {
        $islandMap = self::islandOptions();
        $provinces = $islandMap[$islandKey]['provinces'] ?? null;
        if (! is_array($provinces) || $provinces === []) {
            return [];
        }

        $normalized = [];
        foreach ($provinces as $province) {
            $key = $this->normalizeText((string) $province);
            if ($key !== '') {
                $normalized[$key] = true;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, bool>  $allowedIslandProvinces
     */
    private function passesIslandFilter(array $row, array $allowedIslandProvinces): bool
    {
        if ($allowedIslandProvinces === []) {
            return true;
        }

        $province = $this->normalizeText((string) ($row['province'] ?? ''));
        if ($province === '') {
            return false;
        }

        return isset($allowedIslandProvinces[$province]);
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>|null
     */
    private function mapPlaceToPayload(Destination $destination, array $place): ?array
    {
        $googlePlaceId = trim((string) ($place['google_place_id'] ?? ''));
        $name = trim((string) ($place['name'] ?? ''));
        $latitude = $this->toFiniteFloat($place['latitude'] ?? null);
        $longitude = $this->toFiniteFloat($place['longitude'] ?? null);

        if ($googlePlaceId === '' || $name === '' || $latitude === null || $longitude === null) {
            return null;
        }

        $city = trim((string) ($place['city'] ?? $destination->city ?? ''));
        $province = trim((string) ($place['province'] ?? $destination->province ?? ''));
        $country = trim((string) ($place['country'] ?? $destination->country ?? 'Indonesia'));
        $locationParts = array_filter([$city, $province]);
        $location = $locationParts !== [] ? implode(', ', $locationParts) : trim((string) $destination->location);

        return [
            'google_place_id' => $googlePlaceId,
            'name' => Str::limit($name, 255, ''),
            'ideal_visit_minutes' => 120,
            'contract_rate_per_pax' => 0,
            'markup_type' => 'fixed',
            'markup' => 0,
            'publish_rate_per_pax' => 0,
            'location' => Str::limit($location, 255, ''),
            'city' => $city !== '' ? Str::limit($city, 100, '') : null,
            'province' => $province !== '' ? Str::limit($province, 100, '') : null,
            'country' => $country !== '' ? Str::limit($country, 100, '') : null,
            'timezone' => $destination->timezone ?: null,
            'address' => ($place['address'] ?? null) ? Str::limit((string) $place['address'], 255, '') : null,
            'destination_id' => (int) $destination->id,
            'google_maps_url' => ($place['google_maps_url'] ?? null) ? Str::limit((string) $place['google_maps_url'], 5000, '') : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'description' => null,
            'gallery_images' => [],
            'is_active' => true,
            'source' => 'google_places',
            'last_synced_at' => Carbon::now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistAttraction(array $payload): string
    {
        $existing = TouristAttraction::withTrashed()
            ->where('google_place_id', (string) $payload['google_place_id'])
            ->first();

        if (! $existing) {
            TouristAttraction::query()->create($payload);

            return 'created';
        }

        $existing->fill([
            'name' => $payload['name'],
            'location' => $payload['location'],
            'city' => $payload['city'],
            'province' => $payload['province'],
            'country' => $payload['country'],
            'timezone' => $payload['timezone'],
            'address' => $payload['address'],
            'destination_id' => $payload['destination_id'],
            'google_maps_url' => $payload['google_maps_url'],
            'latitude' => $payload['latitude'],
            'longitude' => $payload['longitude'],
            'is_active' => true,
            'last_synced_at' => $payload['last_synced_at'],
        ]);

        if (empty($existing->source)) {
            $existing->source = 'google_places';
        }
        if ((string) $existing->markup_type === '') {
            $existing->markup_type = 'fixed';
        }

        if ($existing->trashed()) {
            $existing->restore();
        }

        if (! $existing->isDirty()) {
            return 'skipped';
        }

        $existing->save();

        return 'updated';
    }

    private function toFiniteFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        return is_finite($float) ? $float : null;
    }
}
