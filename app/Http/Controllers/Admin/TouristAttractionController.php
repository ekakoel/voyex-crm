<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TouristAttraction;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TouristAttractionController extends Controller
{
    public function index()
    {
        $touristAttractions = TouristAttraction::query()->orderBy('name')->paginate(10);

        return view('modules.tourist-attractions.index', compact('touristAttractions'));
    }

    public function create()
    {
        return view('modules.tourist-attractions.create');
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
        return view('modules.tourist-attractions.edit', compact('touristAttraction'));
    }

    public function update(Request $request, TouristAttraction $touristAttraction)
    {
        $validated = $this->validatePayload($request, $touristAttraction);

        if ($request->hasFile('gallery_images')) {
            $this->deleteGalleryImages($touristAttraction->gallery_images ?? []);
            $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'tourist-attractions');
        } else {
            $validated['gallery_images'] = $touristAttraction->gallery_images ?? [];
        }

        $touristAttraction->update($validated);

        return redirect()->route('tourist-attractions.index')->with('success', 'Tourist attraction updated successfully.');
    }

    public function destroy(TouristAttraction $touristAttraction)
    {
        $this->deleteGalleryImages($touristAttraction->gallery_images ?? []);
        $touristAttraction->delete();

        return redirect()->route('tourist-attractions.index')->with('success', 'Tourist attraction deleted successfully.');
    }

    private function validatePayload(Request $request, ?TouristAttraction $touristAttraction): array
    {
        $hasExistingGallery = $touristAttraction && is_array($touristAttraction->gallery_images) && count($touristAttraction->gallery_images) > 0;
        $galleryRules = ['array', 'max:3'];
        if (! $hasExistingGallery) {
            array_unshift($galleryRules, 'required', 'min:1');
        } elseif ($request->hasFile('gallery_images')) {
            array_unshift($galleryRules, 'min:1');
        } else {
            array_unshift($galleryRules, 'sometimes');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ideal_visit_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'description' => ['nullable', 'string'],
            'gallery_images' => $galleryRules,
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        if (empty($validated['location'])) {
            $parts = array_filter([trim((string) ($validated['city'] ?? '')), trim((string) ($validated['province'] ?? ''))]);
            if ($parts !== []) {
                $validated['location'] = implode(', ', $parts);
            }
        }
        $this->fillCoordinatesFromGoogleMapsUrl($validated);

        return $validated;
    }

    private function storeGalleryImages(array $files, string $directory): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }
            $originalPath = $file->store($directory, 'public');
            $stored[] = $originalPath;
            ImageThumbnailGenerator::generate('public', $originalPath);
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

    private function fillCoordinatesFromGoogleMapsUrl(array &$validated): void
    {
        $hasCoordinates = isset($validated['latitude'], $validated['longitude'])
            && $validated['latitude'] !== ''
            && $validated['longitude'] !== '';

        $googleMapsUrl = (string) ($validated['google_maps_url'] ?? '');
        if ($hasCoordinates || $googleMapsUrl === '') {
            return;
        }

        $coordinates = $this->extractCoordinatesFromGoogleMapsUrl($googleMapsUrl);
        if (! $coordinates) {
            throw ValidationException::withMessages([
                'google_maps_url' => 'Google Maps link does not contain valid coordinates.',
            ]);
        }

        [$latitude, $longitude] = $coordinates;
        $validated['latitude'] = $latitude;
        $validated['longitude'] = $longitude;
    }

    private function extractCoordinatesFromGoogleMapsUrl(string $url): ?array
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
}



