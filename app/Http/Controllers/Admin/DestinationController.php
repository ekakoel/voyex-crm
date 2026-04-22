<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\IslandTransfer;
use App\Models\Transport;
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
        $destination->loadCount(['vendors', 'hotels', 'touristAttractions', 'airports', 'islandTransfers']);

        $normalizedProvince = mb_strtolower(trim((string) ($destination->province ?? '')));
        $islandTransfers = IslandTransfer::query()
            ->with([
                'vendor:id,name,destination_id,city,province',
            ])
            ->where(function ($query) use ($destination, $normalizedProvince) {
                $query->whereHas('vendor', function ($vendorQuery) use ($destination) {
                    $vendorQuery->where('destination_id', $destination->id);
                });

                if ($normalizedProvince !== '') {
                    $query->orWhereHas('vendor', function ($vendorQuery) use ($normalizedProvince) {
                        $vendorQuery->whereRaw('LOWER(TRIM(province)) = ?', [$normalizedProvince]);
                    });
                }
            })
            ->orderBy('name')
            ->limit(25)
            ->get([
                'id',
                'vendor_id',
                'name',
                'transfer_type',
                'departure_point_name',
                'departure_latitude',
                'departure_longitude',
                'arrival_point_name',
                'arrival_latitude',
                'arrival_longitude',
                'duration_minutes',
                'distance_km',
                'gallery_images',
                'is_active',
            ]);

        $vendors = $destination->vendors()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'city',
                'province',
                'is_active',
            ]);

        $activities = Activity::query()
            ->with(['vendor:id,name,destination_id'])
            ->whereHas('vendor', function ($query) use ($destination) {
                $query->where('destination_id', $destination->id);
            })
            ->orderBy('name')
            ->get([
                'id',
                'vendor_id',
                'name',
                'activity_type',
                'duration_minutes',
                'is_active',
            ]);

        $hotels = $destination->hotels()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'city',
                'province',
                'status',
            ]);

        $touristAttractions = $destination->touristAttractions()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'city',
                'province',
                'ideal_visit_minutes',
                'is_active',
            ]);

        $airports = $destination->airports()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'city',
                'province',
                'is_active',
            ]);

        $transports = Transport::query()
            ->with(['vendor:id,name,destination_id'])
            ->whereHas('vendor', function ($query) use ($destination) {
                $query->where('destination_id', $destination->id);
            })
            ->orderBy('name')
            ->get([
                'id',
                'vendor_id',
                'name',
                'code',
                'transport_type',
                'seat_capacity',
                'is_active',
            ]);

        $foodBeverages = FoodBeverage::query()
            ->with(['vendor:id,name,destination_id'])
            ->whereHas('vendor', function ($query) use ($destination) {
                $query->where('destination_id', $destination->id);
            })
            ->orderBy('name')
            ->get([
                'id',
                'vendor_id',
                'name',
                'service_type',
                'duration_minutes',
                'is_active',
            ]);

        return view('modules.destinations.show', [
            'destination' => $destination,
            'islandTransfers' => $islandTransfers,
            'vendors' => $vendors,
            'activities' => $activities,
            'hotels' => $hotels,
            'touristAttractions' => $touristAttractions,
            'airports' => $airports,
            'transports' => $transports,
            'foodBeverages' => $foodBeverages,
            'provinceGeoJsonUrl' => asset('data/IDN_adm_1_province.json'),
        ]);
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
        app(LocationResolver::class)->enrichFromGoogleMapsUrl($prefilled);
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
