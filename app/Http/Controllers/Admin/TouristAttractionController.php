<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\NormalizesDisplayCurrencyToIdr;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\TouristAttraction;
use App\Services\Maps\GooglePlacesService;
use App\Services\TouristAttractionGoogleSyncService;
use App\Support\ImageThumbnailGenerator;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TouristAttractionController extends Controller
{
    use NormalizesDisplayCurrencyToIdr;

    private const GALLERY_DIRECTORY = 'tourist-attractions';

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = TouristAttraction::query()
            ->withTrashed()
            ->with('destination:id,name');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('province', 'like', "%{$term}%")
                    ->orWhere('country', 'like', "%{$term}%");
            });
        }
        if ($request->filled('destination_id')) {
            $query->where('destination_id', (int) $request->input('destination_id'));
        }

        $touristAttractions = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        if ($this->wantsAjaxFragment($request)) {
            return response()->json([
                'html' => view('modules.tourist-attractions.partials._index-results', compact('touristAttractions'))->render(),
                'url' => route('tourist-attractions.index', $request->query()),
            ]);
        }

        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        $googleImportDefaults = [
            'max_results' => (int) config('services.google_maps.places_default_max_results', 60),
            'language' => (string) config('services.google_maps.places_default_language', 'id'),
            'region' => (string) config('services.google_maps.places_default_region', 'ID'),
            'is_configured' => app(GooglePlacesService::class)->isConfigured(),
            'can_import' => (bool) ($request->user()?->isSuperAdmin()),
        ];
        $importCategoryOptions = TouristAttractionGoogleSyncService::categoryOptions();
        $importIslandOptions = TouristAttractionGoogleSyncService::islandOptions();

        return view('modules.tourist-attractions.index', compact(
            'touristAttractions',
            'destinations',
            'googleImportDefaults',
            'importCategoryOptions',
            'importIslandOptions'
        ));
    }

    public function importFromGoogle(
        Request $request,
        GooglePlacesService $googlePlacesService,
        TouristAttractionGoogleSyncService $syncService
    ) {
        if (! ($request->user()?->isSuperAdmin())) {
            abort(403, 'Only super admin can use this feature.');
        }

        if (! $googlePlacesService->isConfigured()) {
            return redirect()
                ->route('tourist-attractions.index')
                ->with('error', __('ui.modules.tourist_attractions.messages.google_places_not_configured'));
        }

        $validated = $request->validate([
            'destination_id' => ['required', 'integer', 'exists:destinations,id'],
            'query' => ['nullable', 'string', 'max:255'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:200'],
            'region' => ['nullable', 'string', 'min:2', 'max:5'],
            'island_key' => ['nullable', Rule::in(array_keys(TouristAttractionGoogleSyncService::islandOptions()))],
            'place_categories' => ['nullable', 'array'],
            'place_categories.*' => ['string', Rule::in(array_keys(TouristAttractionGoogleSyncService::categoryOptions()))],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $destination = Destination::query()
            ->where('is_active', true)
            ->find($validated['destination_id']);
        if (! $destination) {
            return redirect()
                ->route('tourist-attractions.index')
                ->with('error', __('ui.modules.tourist_attractions.messages.destination_not_found'));
        }

        try {
            $summary = $syncService->syncDestination($destination, [
                'query' => $validated['query'] ?? null,
                'max_results' => (int) ($validated['max_results'] ?? 0),
                'language_code' => 'en',
                'region_code' => $validated['region'] ?? null,
                'island_key' => $validated['island_key'] ?? null,
                'category_keys' => $validated['place_categories'] ?? [],
                'dry_run' => $request->boolean('dry_run'),
            ]);

            $message = __('ui.modules.tourist_attractions.messages.google_places_import_summary', [
                'destination' => (string) $summary['destination_name'],
                'fetched' => (int) $summary['fetched'],
                'created' => (int) $summary['created'],
                'updated' => (int) $summary['updated'],
                'skipped' => (int) $summary['skipped'],
                'invalid' => (int) $summary['invalid'],
            ]);
            if ($request->boolean('dry_run')) {
                $message .= ' ' . __('ui.modules.tourist_attractions.messages.google_places_dry_run_note');
            }

            return redirect()
                ->route('tourist-attractions.index')
                ->with('success', $message);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('tourist-attractions.index')
                ->with('error', __('ui.modules.tourist_attractions.messages.google_places_import_failed') . ' ' . $exception->getMessage());
        }
    }

    public function create()
    {
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.tourist-attractions.create', compact('destinations'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);

        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), self::GALLERY_DIRECTORY);

        TouristAttraction::query()->create($validated);

        return redirect()->route('tourist-attractions.index')->with('success', __('ui.modules.tourist_attractions.messages.created'));
    }

    public function edit(TouristAttraction $touristAttraction)
    {
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.tourist-attractions.edit', compact('touristAttraction', 'destinations'));
    }

    public function update(Request $request, TouristAttraction $touristAttraction)
    {
        $validated = $this->validatePayload($request, $touristAttraction);
        $existingGallery = $this->normalizeGalleryImages($touristAttraction->gallery_images ?? []);
        $storage = Storage::disk('public');
        $existingGallery = array_values(array_filter($existingGallery, fn ($path) => $storage->exists($path)));
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGallery = array_values(array_diff($existingGallery, $removedGallery));

        if ($removedGallery !== []) {
            $this->deleteGalleryImages($removedGallery);
        }

        $newGallery = $this->storeGalleryImages($request->file('gallery_images', []), self::GALLERY_DIRECTORY);
        $validated['gallery_images'] = array_values(array_merge($remainingGallery, $newGallery));
        unset($validated['removed_gallery_images']);

        $touristAttraction->update($validated);

        return redirect()->route('tourist-attractions.index')->with('success', __('ui.modules.tourist_attractions.messages.updated'));
    }

    public function destroy($touristAttraction)
    {
        if (! (request()->user()?->isSuperAdmin())) {
            abort(403, 'Only super admin can delete tourist attractions.');
        }

        $touristAttraction = TouristAttraction::withTrashed()->findOrFail($touristAttraction);
        $this->deleteGalleryImages($touristAttraction->gallery_images ?? []);
        $touristAttraction->forceDelete();

        if ($this->wantsAjaxFragment(request())) {
            return response()->json([
                'success' => true,
                'message' => __('ui.modules.tourist_attractions.messages.deleted'),
            ]);
        }

        return redirect()->route('tourist-attractions.index')->with('success', __('ui.modules.tourist_attractions.messages.deleted'));
    }

    public function toggleStatus($touristAttraction)
    {
        $touristAttraction = TouristAttraction::withTrashed()->findOrFail($touristAttraction);
        if ($touristAttraction->trashed()) {
            $touristAttraction->restore();
            $touristAttraction->update(['is_active' => true]);

            if ($this->wantsAjaxFragment(request())) {
                return response()->json([
                    'success' => true,
                    'status' => 'active',
                    'message' => __('ui.modules.tourist_attractions.messages.activated'),
                ]);
            }

            return redirect()
                ->route('tourist-attractions.index')
                ->with('success', __('ui.modules.tourist_attractions.messages.activated'));
        }

        $touristAttraction->update(['is_active' => false]);
        $touristAttraction->delete();

        if ($this->wantsAjaxFragment(request())) {
            return response()->json([
                'success' => true,
                'status' => 'inactive',
                'message' => __('ui.modules.tourist_attractions.messages.deactivated'),
            ]);
        }

        return redirect()
            ->route('tourist-attractions.index')
            ->with('success', __('ui.modules.tourist_attractions.messages.deactivated'));
    }

    public function removeGalleryImage(Request $request, TouristAttraction $touristAttraction)
    {
        $validated = $request->validate([
            'image' => ['required', 'string'],
        ]);

        $imageRaw = (string) $validated['image'];
        $gallery = $this->normalizeGalleryImages($touristAttraction->gallery_images ?? []);
        $rawNormalized = trim(str_replace('\\', '/', $imageRaw), '/');
        $imageCandidates = array_values(array_unique(array_filter([
            $this->normalizeGalleryPath($imageRaw),
            $rawNormalized,
            $rawNormalized !== '' ? (self::GALLERY_DIRECTORY . '/' . ltrim($rawNormalized, '/')) : '',
        ], fn ($value) => is_string($value) && trim($value) !== '')));
        $image = collect($imageCandidates)->first(fn ($candidate) => in_array($candidate, $gallery, true));

        if (! is_string($image) || $image === '') {
            return response()->json([
                'message' => 'Image not found in gallery.',
            ], 404);
        }

        $remaining = array_values(array_diff($gallery, [$image]));
        $this->deleteGalleryImages([$image]);
        $touristAttraction->update(['gallery_images' => $remaining]);

        return response()->json([
            'message' => 'Image removed successfully.',
            'remaining_count' => count($remaining),
        ]);
    }

    private function validatePayload(Request $request, ?TouristAttraction $touristAttraction): array
    {
        $existingGallery = $this->normalizeGalleryImages($touristAttraction?->gallery_images ?? []);
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGalleryCount = count(array_values(array_diff($existingGallery, $removedGallery)));
        $newUploads = $request->file('gallery_images', []);
        $newUploads = is_array($newUploads) ? array_values(array_filter($newUploads)) : [];
        $newUploadsCount = count($newUploads);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ideal_visit_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'contract_rate_per_pax' => ['nullable', 'numeric', 'min:0'],
            'markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'markup' => ['nullable', 'numeric', 'min:0'],
            'publish_rate_per_pax' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'description' => ['nullable', 'string'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image'],
            'removed_gallery_images' => ['nullable', 'array'],
            'removed_gallery_images.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['markup_type'] = (($validated['markup_type'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
        $validated['contract_rate_per_pax'] = max(0, (float) ($validated['contract_rate_per_pax'] ?? 0));
        $validated['markup'] = max(0, (float) ($validated['markup'] ?? 0));

        // Persist service pricing in canonical IDR.
        $validated['contract_rate_per_pax'] = $this->displayCurrencyToIdr($validated['contract_rate_per_pax']);
        if ($validated['markup_type'] === 'fixed') {
            $validated['markup'] = $this->displayCurrencyToIdr($validated['markup']);
        }

        if ($validated['markup_type'] === 'percent' && $validated['markup'] > 100) {
            throw ValidationException::withMessages([
                'markup' => 'Markup percent cannot be greater than 100.',
            ]);
        }

        $validated['publish_rate_per_pax'] = round($this->calculatePublishRate(
            $validated['contract_rate_per_pax'],
            $validated['markup_type'],
            $validated['markup']
        ), 0);
        $validated['contract_rate_per_pax'] = round($validated['contract_rate_per_pax'], 0);
        $validated['markup'] = round($validated['markup'], 0);

        app(LocationResolver::class)->enrichFromGoogleMapsUrl($validated);
        $this->applyDestinationContext($validated);
        app(LocationResolver::class)->resolveDestinationId($validated, true);
        if (empty($validated['location'])) {
            $parts = array_filter([trim((string) ($validated['city'] ?? '')), trim((string) ($validated['province'] ?? ''))]);
            if ($parts !== []) {
                $validated['location'] = implode(', ', $parts);
            }
        }

        return $validated;
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

    private function storeGalleryImages(array $files, string $directory): array
    {
        $stored = [];
        $targetDirectory = trim($directory) !== '' ? trim($directory) : self::GALLERY_DIRECTORY;
        $storage = Storage::disk('public');
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $originalPath = $file->store($targetDirectory, 'public');
            if (! is_string($originalPath) || trim($originalPath) === '') {
                continue;
            }

            $processedPath = ImageThumbnailGenerator::processAndGenerate('public', $originalPath, 3, 2, 360, 240) ?? $originalPath;
            $selectedPath = is_string($processedPath) && trim($processedPath) !== '' ? $processedPath : $originalPath;

            $normalizedSelectedPath = $this->normalizeGalleryPath($selectedPath);
            if (! $storage->exists($normalizedSelectedPath)) {
                $normalizedOriginalPath = $this->normalizeGalleryPath($originalPath);
                if ($storage->exists($normalizedOriginalPath)) {
                    $normalizedSelectedPath = $normalizedOriginalPath;
                } else {
                    // Prevent DB drift: do not persist gallery path when file is missing on disk.
                    continue;
                }
            }

            // Ensure thumbnail always exists for every uploaded image.
            ImageThumbnailGenerator::generate('public', $normalizedSelectedPath, 360, 240);
            $stored[] = $normalizedSelectedPath;
        }
        return $stored;
    }

    private function normalizeGalleryImages($images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($images)) {
            return [];
        }

        $normalized = [];
        foreach ($images as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }
            $normalizedPath = $this->normalizeGalleryPath($path);
            if ($normalizedPath !== '') {
                $normalized[] = $normalizedPath;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function deleteGalleryImages(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                $normalizedPath = $this->normalizeGalleryPath($path);
                $baseNormalized = trim(str_replace('\\', '/', $path), '/');
                $candidates = array_values(array_unique(array_filter([
                    $normalizedPath,
                    $baseNormalized,
                    $baseNormalized !== '' && ! str_contains($baseNormalized, '/')
                        ? self::GALLERY_DIRECTORY . '/' . ltrim($baseNormalized, '/')
                        : '',
                ], fn ($value) => is_string($value) && trim($value) !== '')));

                foreach ($candidates as $candidate) {
                    Storage::disk('public')->delete($candidate);
                    Storage::disk('public')->delete(ImageThumbnailGenerator::thumbnailPathFor($candidate));
                }
            }
        }
    }

    private function normalizeGalleryPath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        if ($normalized === '') {
            return '';
        }

        if (str_starts_with($normalized, 'storage/')) {
            $normalized = ltrim(substr($normalized, 8), '/');
        }

        if (str_starts_with($normalized, self::GALLERY_DIRECTORY . '/')) {
            return $normalized;
        }

        if (str_contains($normalized, '/')) {
            return $normalized;
        }

        // Legacy compatibility: filename-only can be at root OR in tourist-attractions/.
        $storage = Storage::disk('public');
        $prefixed = self::GALLERY_DIRECTORY . '/' . $normalized;
        if ($storage->exists($prefixed)) {
            return $prefixed;
        }
        if ($storage->exists($normalized)) {
            return $normalized;
        }

        // Default for new canonical path when file check cannot determine.
        return $prefixed;
    }

    private function applyDestinationContext(array &$validated): void
    {
        $destinationId = (int) ($validated['destination_id'] ?? 0);
        if ($destinationId <= 0) {
            return;
        }

        $destination = Destination::query()->find($destinationId);
        if (! $destination) {
            return;
        }

        if (empty($validated['city']) && ! empty($destination->city)) {
            $validated['city'] = (string) $destination->city;
        }
        if (empty($validated['province']) && ! empty($destination->province)) {
            $validated['province'] = (string) $destination->province;
        }
        if (empty($validated['country']) && ! empty($destination->country)) {
            $validated['country'] = (string) $destination->country;
        }
        if (empty($validated['timezone']) && ! empty($destination->timezone)) {
            $validated['timezone'] = (string) $destination->timezone;
        }
    }

    private function wantsAjaxFragment(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Tourist-Attractions-Ajax') === '1';
    }
}
