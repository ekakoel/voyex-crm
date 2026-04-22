<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\NormalizesDisplayCurrencyToIdr;
use App\Http\Controllers\Controller;
use App\Models\IslandTransfer;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IslandTransferController extends Controller
{
    use NormalizesDisplayCurrencyToIdr;

    public function index(Request $request)
    {
        $transferTypeOptions = [
            ['value' => 'fastboat', 'label' => __('ui.modules.island_transfers.types.fastboat')],
            ['value' => 'ferry', 'label' => __('ui.modules.island_transfers.types.ferry')],
            ['value' => 'speedboat', 'label' => __('ui.modules.island_transfers.types.speedboat')],
            ['value' => 'boat', 'label' => __('ui.modules.island_transfers.types.boat')],
        ];

        $query = IslandTransfer::query()
            ->withTrashed()
            ->with('vendor:id,name,city,province')
            ->latest('id');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->integer('vendor_id'));
        }
        if ($request->filled('transfer_type')) {
            $query->where('transfer_type', (string) $request->string('transfer_type'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $islandTransfers = $query->paginate($perPage)->withQueryString();
        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);
        $statsCards = [
            [
                'label' => __('ui.modules.island_transfers.stats_total'),
                'value' => (string) IslandTransfer::withTrashed()->count(),
            ],
            [
                'label' => __('ui.modules.island_transfers.stats_active'),
                'value' => (string) IslandTransfer::query()->count(),
            ],
            [
                'label' => __('ui.modules.island_transfers.stats_inactive'),
                'value' => (string) IslandTransfer::onlyTrashed()->count(),
            ],
            [
                'label' => __('ui.modules.island_transfers.stats_fast_boat'),
                'value' => (string) IslandTransfer::withTrashed()->where('transfer_type', 'fastboat')->count(),
            ],
        ];

        return view('modules.island-transfers.index', compact('islandTransfers', 'vendors', 'transferTypeOptions', 'statsCards'));
    }

    public function create()
    {
        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.island-transfers.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        IslandTransfer::query()->create($validated);

        return redirect()->route('island-transfers.index')->with('success', __('ui.modules.island_transfers.messages.created'));
    }

    public function show($islandTransfer)
    {
        $islandTransfer = IslandTransfer::query()
            ->withTrashed()
            ->with('vendor:id,name,city,province,location,address')
            ->findOrFail($islandTransfer);

        return view('modules.island-transfers.show', compact('islandTransfer'));
    }

    public function edit(IslandTransfer $islandTransfer)
    {
        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.island-transfers.edit', compact('islandTransfer', 'vendors'));
    }

    public function update(Request $request, IslandTransfer $islandTransfer)
    {
        $validated = $this->validatePayload($request);
        $islandTransfer->update($validated);

        return redirect()->route('island-transfers.index')->with('success', __('ui.modules.island_transfers.messages.updated'));
    }

    public function toggleStatus($islandTransfer)
    {
        $islandTransfer = IslandTransfer::withTrashed()->findOrFail($islandTransfer);
        if ($islandTransfer->trashed()) {
            $islandTransfer->restore();
            $islandTransfer->update(['is_active' => true]);

            return redirect()
                ->route('island-transfers.index')
                ->with('success', __('ui.modules.island_transfers.messages.activated'));
        }

        $islandTransfer->update(['is_active' => false]);
        $islandTransfer->delete();

        return redirect()
            ->route('island-transfers.index')
            ->with('success', __('ui.modules.island_transfers.messages.deactivated'));
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'transfer_type' => ['required', 'string', Rule::in(['fastboat', 'ferry', 'speedboat', 'boat'])],
            'departure_point_name' => ['required', 'string', 'max:150'],
            'departure_latitude' => ['required', 'numeric', 'between:-90,90'],
            'departure_longitude' => ['required', 'numeric', 'between:-180,180'],
            'arrival_point_name' => ['required', 'string', 'max:150'],
            'arrival_latitude' => ['required', 'numeric', 'between:-90,90'],
            'arrival_longitude' => ['required', 'numeric', 'between:-180,180'],
            'route_geojson' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:1440'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'contract_rate' => ['nullable', 'numeric', 'min:0'],
            'markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'markup' => ['nullable', 'numeric', 'min:0'],
            'publish_rate' => ['nullable', 'numeric', 'min:0'],
            'capacity_min' => ['nullable', 'integer', 'min:1'],
            'capacity_max' => ['nullable', 'integer', 'min:1', 'gte:capacity_min'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['route_geojson'] = $this->normalizeRouteGeoJson((string) ($validated['route_geojson'] ?? ''));
        $validated['distance_km'] = $this->resolveDistanceKm($validated);
        $validated['markup_type'] = (($validated['markup_type'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
        $validated['contract_rate'] = max(0, (float) ($validated['contract_rate'] ?? 0));
        $validated['markup'] = max(0, (float) ($validated['markup'] ?? 0));

        // Persist service pricing in canonical IDR.
        $validated['contract_rate'] = $this->displayCurrencyToIdr($validated['contract_rate']);
        if ($validated['markup_type'] === 'fixed') {
            $validated['markup'] = $this->displayCurrencyToIdr($validated['markup']);
        }

        if ($validated['markup_type'] === 'percent' && $validated['markup'] > 100) {
            throw ValidationException::withMessages([
                'markup' => 'Markup percent cannot be greater than 100.',
            ]);
        }

        $validated['publish_rate'] = round($this->calculatePublishRate(
            $validated['contract_rate'],
            $validated['markup_type'],
            $validated['markup']
        ), 0);
        $validated['contract_rate'] = round($validated['contract_rate'], 0);
        $validated['markup'] = round($validated['markup'], 0);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function resolveDistanceKm(array $validated): ?float
    {
        $routeGeoJson = $validated['route_geojson'] ?? null;
        if (is_array($routeGeoJson) && strtolower((string) ($routeGeoJson['type'] ?? '')) === 'linestring') {
            $coordinates = $routeGeoJson['coordinates'] ?? null;
            if (is_array($coordinates) && count($coordinates) >= 2) {
                $distance = 0.0;
                for ($i = 1; $i < count($coordinates); $i++) {
                    $previous = $coordinates[$i - 1] ?? null;
                    $current = $coordinates[$i] ?? null;
                    if (! is_array($previous) || ! is_array($current) || count($previous) < 2 || count($current) < 2) {
                        continue;
                    }

                    $prevLng = $this->toFiniteFloat($previous[0] ?? null);
                    $prevLat = $this->toFiniteFloat($previous[1] ?? null);
                    $currLng = $this->toFiniteFloat($current[0] ?? null);
                    $currLat = $this->toFiniteFloat($current[1] ?? null);

                    if ($prevLat === null || $prevLng === null || $currLat === null || $currLng === null) {
                        continue;
                    }

                    $distance += $this->haversineKm($prevLat, $prevLng, $currLat, $currLng);
                }

                return $distance > 0 ? round($distance, 2) : null;
            }
        }

        $departureLat = $this->toFiniteFloat($validated['departure_latitude'] ?? null);
        $departureLng = $this->toFiniteFloat($validated['departure_longitude'] ?? null);
        $arrivalLat = $this->toFiniteFloat($validated['arrival_latitude'] ?? null);
        $arrivalLng = $this->toFiniteFloat($validated['arrival_longitude'] ?? null);

        if ($departureLat === null || $departureLng === null || $arrivalLat === null || $arrivalLng === null) {
            return null;
        }

        return round($this->haversineKm($departureLat, $departureLng, $arrivalLat, $arrivalLng), 2);
    }

    private function toFiniteFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;
        return is_finite($float) ? $float : null;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));

        return $earthRadiusKm * $c;
    }

    private function calculatePublishRate(float $contractRate, string $markupType, float $markup): float
    {
        $base = max(0, $contractRate);
        $value = max(0, $markup);

        if ($markupType === 'percent') {
            return $base + ($base * ($value / 100));
        }

        return $base + $value;
    }

    private function normalizeRouteGeoJson(string $rawValue): ?array
    {
        $value = $this->normalizePotentialRichTextInput($rawValue);
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', trim($value)) ?? trim($value);
        if ($value === '') {
            return null;
        }

        $decoded = $this->decodeRouteJson($value);
        if (is_string($decoded)) {
            $decoded = $this->decodeRouteJson($decoded);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'route_geojson' => 'Route JSON must be a valid JSON object/array. Please ensure no wrapping quotes, valid UTF-8 text, and no HTML entities like &quot;.',
            ]);
        }

        $coordinates = [];
        $decodedType = strtolower((string) ($decoded['type'] ?? ''));
        if ($decodedType === 'linestring' && is_array($decoded['coordinates'] ?? null)) {
            $coordinates = $decoded['coordinates'];
        } elseif ($decodedType === 'feature' && is_array($decoded['geometry'] ?? null)) {
            $geometry = $decoded['geometry'];
            $geometryType = strtolower((string) ($geometry['type'] ?? ''));
            if ($geometryType === 'linestring' && is_array($geometry['coordinates'] ?? null)) {
                $coordinates = $geometry['coordinates'];
            }
        } elseif ($decodedType === 'featurecollection' && is_array($decoded['features'] ?? null)) {
            foreach ($decoded['features'] as $feature) {
                if (! is_array($feature) || ! is_array($feature['geometry'] ?? null)) {
                    continue;
                }

                $geometry = $feature['geometry'];
                $geometryType = strtolower((string) ($geometry['type'] ?? ''));
                if ($geometryType === 'linestring' && is_array($geometry['coordinates'] ?? null)) {
                    $coordinates = $geometry['coordinates'];
                    break;
                }
            }
        } elseif (array_is_list($decoded)) {
            $coordinates = $decoded;
        } else {
            throw ValidationException::withMessages([
                'route_geojson' => 'Route JSON must be a LineString or coordinate list.',
            ]);
        }

        $normalized = [];
        foreach ($coordinates as $index => $coord) {
            if (! is_array($coord) || count($coord) < 2) {
                throw ValidationException::withMessages([
                    'route_geojson' => "Coordinate at index {$index} is invalid. Use [longitude, latitude].",
                ]);
            }

            $lng = is_numeric($coord[0] ?? null) ? (float) $coord[0] : null;
            $lat = is_numeric($coord[1] ?? null) ? (float) $coord[1] : null;
            if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                throw ValidationException::withMessages([
                    'route_geojson' => "Coordinate at index {$index} is out of range.",
                ]);
            }

            $normalized[] = [$lng, $lat];
        }

        if (count($normalized) < 2) {
            throw ValidationException::withMessages([
                'route_geojson' => 'Route needs at least 2 coordinates.',
            ]);
        }

        return [
            'type' => 'LineString',
            'coordinates' => $normalized,
        ];
    }

    private function decodeRouteJson(string $value): mixed
    {
        $candidate = trim($value);
        if ($candidate === '') {
            return null;
        }

        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Handle content copied from HTML-escaped sources (&quot;...&quot;).
        $candidate = html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Normalize curly quotes and remove invisible control characters often introduced by editors/chat apps.
        $candidate = str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"],
            ['"', '"', "'", "'"],
            $candidate
        );
        $candidate = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $candidate) ?? $candidate;

        return json_decode($candidate, true);
    }

    private function normalizePotentialRichTextInput(string $value): string
    {
        $candidate = trim($value);
        if ($candidate === '' || ! str_contains($candidate, '<')) {
            return $candidate;
        }

        $withLineBreaks = preg_replace('/<br\s*\/?>/i', "\n", $candidate) ?? $candidate;
        $withLineBreaks = preg_replace('/<\/(p|div|li|h1|h2|h3|h4|h5|h6|blockquote)>/i', "\n", $withLineBreaks) ?? $withLineBreaks;

        return trim(strip_tags(html_entity_decode($withLineBreaks, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
