<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $query = Destination::query()
            ->withTrashed()
            ->withCount(['vendors', 'hotels', 'touristAttractions', 'airports'])
            ->latest('id');

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('province', 'like', "%{$term}%");
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $destinations = $query->paginate($perPage)->withQueryString();

        return view('modules.destinations.index', compact('destinations'));
    }

    public function create()
    {
        return view('modules.destinations.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);
        Destination::query()->create($validated);

        return redirect()->route('destinations.index')->with('success', 'Destination created successfully.');
    }

    public function show(Destination $destination)
    {
        $destination->loadCount(['vendors', 'hotels', 'touristAttractions', 'airports']);

        return view('modules.destinations.show', compact('destination'));
    }

    public function edit(Destination $destination)
    {
        return view('modules.destinations.edit', compact('destination'));
    }

    public function update(Request $request, Destination $destination)
    {
        $validated = $this->validatePayload($request, $destination);
        $destination->update($validated);

        return redirect()->route('destinations.index')->with('success', 'Destination updated successfully.');
    }

    public function destroy(Destination $destination)
    {
        $destination->delete();

        return redirect()->route('destinations.index')->with('success', 'Destination deactivated successfully.');
    }

    public function toggleStatus($destination)
    {
        $destination = Destination::withTrashed()->findOrFail($destination);
        if ($destination->trashed()) {
            $destination->restore();
            $destination->update(['is_active' => true]);

            return redirect()
                ->route('destinations.index')
                ->with('success', 'Destination activated successfully.');
        }

        $destination->update(['is_active' => false]);
        $destination->delete();

        return redirect()
            ->route('destinations.index')
            ->with('success', 'Destination deactivated successfully.');
    }

    private function validatePayload(Request $request, ?Destination $destination): array
    {
        $prefilled = $request->only([
            'google_maps_url', 'location', 'city', 'province', 'country', 'address', 'latitude', 'longitude', 'timezone',
        ]);
        app(LocationResolver::class)->enrichFromGoogleMapsUrl($prefilled, true);
        $request->merge($prefilled);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('destinations', 'code')->ignore($destination?->id)],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('destinations', 'slug')->ignore($destination?->id)],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100', Rule::unique('destinations', 'province')->ignore($destination?->id)],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim((string) $validated['code']));
        $validated['province'] = trim((string) $validated['province']);
        $validated['name'] = $validated['province'];
        $validated['slug'] = $this->resolveSlug($validated['slug'] ?? null, $validated['province']);
        $validated['is_active'] = $request->boolean('is_active');

        if (empty($validated['location'])) {
            $validated['location'] = $validated['province'];
        }

        return $validated;
    }

    private function resolveSlug(?string $slug, string $name): string
    {
        $base = trim((string) $slug) !== '' ? trim((string) $slug) : $name;
        $normalized = \Illuminate\Support\Str::slug($base);

        return $normalized !== '' ? $normalized : \Illuminate\Support\Str::slug($name . '-' . uniqid());
    }
}

