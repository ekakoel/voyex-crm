<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\NormalizesDisplayCurrencyToIdr;
use App\Http\Controllers\Controller;
use App\Models\IslandTransfer;
use App\Models\Vendor;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IslandTransferController extends Controller
{
    use NormalizesDisplayCurrencyToIdr;

    public function index(Request $request)
    {
        $transferTypeOptions = [
            ['value' => 'fastboat', 'label' => ui_phrase('Fast Boat')],
            ['value' => 'ferry', 'label' => ui_phrase('Ferry')],
            ['value' => 'speedboat', 'label' => ui_phrase('Speedboat')],
            ['value' => 'boat', 'label' => ui_phrase('Boat')],
        ];

        $query = IslandTransfer::query()
            ->withTrashed()
            ->with('vendor:id,name,city,province,latitude,longitude')
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
                'label' => ui_phrase('Total Transfers'),
                'value' => (string) IslandTransfer::withTrashed()->count(),
            ],
            [
                'label' => ui_phrase('Active'),
                'value' => (string) IslandTransfer::query()->count(),
            ],
            [
                'label' => ui_phrase('Inactive'),
                'value' => (string) IslandTransfer::onlyTrashed()->count(),
            ],
            [
                'label' => ui_phrase('Fast Boat'),
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
        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'island-transfers');
        IslandTransfer::query()->create($validated);

        return redirect()->route('island-transfers.index')->with('success', ui_phrase('Island Transfer created successfully.'));
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
        $existingGallery = $this->normalizeGalleryImages($islandTransfer->gallery_images ?? []);
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGallery = array_values(array_diff($existingGallery, $removedGallery));

        if ($removedGallery !== []) {
            $this->deleteGalleryImages($removedGallery);
        }

        $newGallery = $this->storeGalleryImages($request->file('gallery_images', []), 'island-transfers');
        $validated['gallery_images'] = array_values(array_merge($remainingGallery, $newGallery));
        unset($validated['removed_gallery_images']);
        $islandTransfer->update($validated);

        return redirect()->route('island-transfers.index')->with('success', ui_phrase('Island Transfer updated successfully.'));
    }

    public function duplicate($islandTransfer)
    {
        $source = IslandTransfer::query()
            ->withTrashed()
            ->findOrFail($islandTransfer);

        $duplicated = DB::transaction(function () use ($source): IslandTransfer {
            $copy = $source->replicate();
            $copy->name = $this->buildDuplicatedName((string) $source->name);
            $copy->is_active = true;
            $copy->deleted_at = null;
            $copy->gallery_images = $this->duplicateGalleryImages($source->gallery_images ?? [], 'island-transfers');
            $copy->save();

            return $copy;
        });

        return redirect()
            ->route('island-transfers.edit', $duplicated)
            ->with('success', ui_phrase('Island Transfer duplicated successfully. Please review and save your changes.'));
    }

    public function toggleStatus($islandTransfer)
    {
        $islandTransfer = IslandTransfer::withTrashed()->findOrFail($islandTransfer);
        if ($islandTransfer->trashed()) {
            $islandTransfer->restore();
            $islandTransfer->update(['is_active' => true]);

            return redirect()
                ->route('island-transfers.index')
                ->with('success', ui_phrase('Island Transfer activated successfully.'));
        }

        $islandTransfer->update(['is_active' => false]);
        $islandTransfer->delete();

        return redirect()
            ->route('island-transfers.index')
            ->with('success', ui_phrase('Island Transfer deactivated successfully.'));
    }

    public function destroy(IslandTransfer $islandTransfer)
    {
        $this->deleteGalleryImages($islandTransfer->gallery_images ?? []);
        $islandTransfer->delete();

        return redirect()->route('island-transfers.index')->with('success', ui_phrase('Island Transfer deactivated successfully.'));
    }

    public function removeGalleryImage(Request $request, IslandTransfer $islandTransfer)
    {
        $validated = $request->validate([
            'image' => ['required', 'string'],
        ]);

        $image = (string) $validated['image'];
        $gallery = $this->normalizeGalleryImages($islandTransfer->gallery_images ?? []);
        if (! in_array($image, $gallery, true)) {
            return response()->json([
                'message' => 'Image not found in gallery.',
            ], 404);
        }

        $remaining = array_values(array_diff($gallery, [$image]));
        $this->deleteGalleryImages([$image]);
        $islandTransfer->update(['gallery_images' => $remaining]);

        return response()->json([
            'message' => 'Image removed successfully.',
            'remaining_count' => count($remaining),
        ]);
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
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image'],
            'removed_gallery_images' => ['nullable', 'array'],
            'removed_gallery_images.*' => ['string'],
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

    /**
     * @param  mixed  $images
     * @return array<int, string>
     */
    private function normalizeGalleryImages($images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter($images, function ($path) {
            return is_string($path) && trim($path) !== '';
        }));
    }

    private function storeGalleryImages(array $files, string $directory): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $originalPath = $file->store($directory, 'public');
            $processedPath = ImageThumbnailGenerator::processAndGenerate('public', $originalPath, 3, 2, 360, 240) ?? $originalPath;
            $stored[] = $processedPath;
        }

        return $stored;
    }

    private function deleteGalleryImages(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
                Storage::disk('public')->delete(ImageThumbnailGenerator::thumbnailPathFor($path));
            }
        }
    }

    /**
     * @param  mixed  $images
     * @return array<int, string>
     */
    private function duplicateGalleryImages($images, string $directory): array
    {
        $sourceImages = $this->normalizeGalleryImages($images);
        if ($sourceImages === []) {
            return [];
        }

        $disk = Storage::disk('public');
        $copied = [];
        $directory = trim($directory, '/');

        foreach ($sourceImages as $sourcePath) {
            if (! $disk->exists($sourcePath)) {
                continue;
            }

            try {
                $contents = $disk->get($sourcePath);
                $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
                if ($extension === '') {
                    $extension = 'jpg';
                }

                $newPath = $directory . '/' . Str::uuid() . '.' . $extension;
                $disk->put($newPath, $contents);

                $processedPath = ImageThumbnailGenerator::processAndGenerate('public', $newPath, 3, 2, 360, 240) ?? $newPath;
                $copied[] = $processedPath;
            } catch (\Throwable) {
                continue;
            }
        }

        return $copied;
    }

    private function buildDuplicatedName(string $name): string
    {
        $baseName = trim($name) !== '' ? trim($name) : 'Island Transfer';
        $candidate = $baseName . ' (Copy)';

        if (! IslandTransfer::withTrashed()->where('name', $candidate)->exists()) {
            return $candidate;
        }

        $counter = 2;
        while (IslandTransfer::withTrashed()->where('name', "{$candidate} {$counter}")->exists()) {
            $counter++;
        }

        return "{$candidate} {$counter}";
    }
}
