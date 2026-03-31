<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\TouristAttraction;
use App\Support\ImageThumbnailGenerator;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TouristAttractionController extends Controller
{
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

        return view('modules.tourist-attractions.index', compact('touristAttractions'));
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

        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'tourist-attractions');

        TouristAttraction::query()->create($validated);

        return redirect()->route('tourist-attractions.index')->with('success', 'Tourist attraction created successfully.');
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
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGallery = array_values(array_diff($existingGallery, $removedGallery));

        if ($removedGallery !== []) {
            $this->deleteGalleryImages($removedGallery);
        }

        $newGallery = $this->storeGalleryImages($request->file('gallery_images', []), 'tourist-attractions');
        $validated['gallery_images'] = array_values(array_merge($remainingGallery, $newGallery));
        unset($validated['removed_gallery_images']);

        $touristAttraction->update($validated);

        return redirect()->route('tourist-attractions.index')->with('success', 'Tourist attraction updated successfully.');
    }

    public function destroy(TouristAttraction $touristAttraction)
    {
        $this->deleteGalleryImages($touristAttraction->gallery_images ?? []);
        $touristAttraction->delete();

        return redirect()->route('tourist-attractions.index')->with('success', 'Tourist attraction deactivated successfully.');
    }

    public function toggleStatus($touristAttraction)
    {
        $touristAttraction = TouristAttraction::withTrashed()->findOrFail($touristAttraction);
        if ($touristAttraction->trashed()) {
            $touristAttraction->restore();
            $touristAttraction->update(['is_active' => true]);

            return redirect()
                ->route('tourist-attractions.index')
                ->with('success', 'Tourist attraction activated successfully.');
        }

        $touristAttraction->update(['is_active' => false]);
        $touristAttraction->delete();

        return redirect()
            ->route('tourist-attractions.index')
            ->with('success', 'Tourist attraction deactivated successfully.');
    }

    public function removeGalleryImage(Request $request, TouristAttraction $touristAttraction)
    {
        $validated = $request->validate([
            'image' => ['required', 'string'],
        ]);

        $image = (string) $validated['image'];
        $gallery = $this->normalizeGalleryImages($touristAttraction->gallery_images ?? []);
        if (! in_array($image, $gallery, true)) {
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

    private function normalizeGalleryImages($images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter($images, fn ($path) => is_string($path) && trim($path) !== ''));
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



