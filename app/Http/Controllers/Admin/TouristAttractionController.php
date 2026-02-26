<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TouristAttraction;
use Illuminate\Http\Request;
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

        TouristAttraction::query()->create($validated);

        return redirect()->route('tourist-attractions.index')->with('success', 'Tourist attraction created successfully.');
    }

    public function edit(TouristAttraction $touristAttraction)
    {
        return view('modules.tourist-attractions.edit', compact('touristAttraction'));
    }

    public function update(Request $request, TouristAttraction $touristAttraction)
    {
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

        $touristAttraction->update($validated);

        return redirect()->route('tourist-attractions.index')->with('success', 'Tourist attraction updated successfully.');
    }

    public function destroy(TouristAttraction $touristAttraction)
    {
        $touristAttraction->delete();

        return redirect()->route('tourist-attractions.index')->with('success', 'Tourist attraction deleted successfully.');
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



