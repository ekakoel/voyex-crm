<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\Destination;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $query = Airport::query()
            ->withTrashed()
            ->with('destination:id,name')
            ->select([
                'id',
                'code',
                'name',
                'location',
                'city',
                'province',
                'country',
                'destination_id',
                'is_active',
                'deleted_at',
            ])
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
        $airports = $query->paginate($perPage)->withQueryString();
        $statsCards = [
            [
                'key' => 'total',
                'label' => 'Total Airports',
                'value' => Airport::query()->count(),
                'caption' => 'All records',
            ],
            [
                'key' => 'active',
                'label' => 'Active Airports',
                'value' => Airport::query()->whereNull('deleted_at')->count(),
                'caption' => 'Currently active',
            ],
            [
                'key' => 'mapped',
                'label' => 'With Coordinates',
                'value' => Airport::query()
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
                'caption' => 'Ready for maps',
            ],
        ];

        return view('modules.airports.index', compact('airports', 'statsCards'));
    }

    public function create()
    {
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.airports.create', compact('destinations'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);
        Airport::query()->create($validated);

        return redirect()->route('airports.index')->with('success', 'Airport created successfully.');
    }

    public function show(Airport $airport)
    {
        $airport->load('destination:id,name');
        if (Schema::hasColumn('airports', 'created_by')) {
            $airport->load('creator:id,name');
        }
        if (Schema::hasColumn('airports', 'updated_by')) {
            $airport->load('updater:id,name');
        }

        return view('modules.airports.show', compact('airport'));
    }

    public function edit(Airport $airport)
    {
        if (Schema::hasColumn('airports', 'created_by')) {
            $airport->load('creator:id,name');
        }
        if (Schema::hasColumn('airports', 'updated_by')) {
            $airport->load('updater:id,name');
        }

        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.airports.edit', compact('airport', 'destinations'));
    }

    public function update(Request $request, Airport $airport)
    {
        $validated = $this->validatePayload($request, $airport);
        $airport->update($validated);

        return redirect()->route('airports.index')->with('success', 'Airport updated successfully.');
    }

    public function destroy(Airport $airport)
    {
        $airport->delete();

        return redirect()->route('airports.index')->with('success', 'Airport deactivated successfully.');
    }

    public function toggleStatus($airport)
    {
        $airport = Airport::withTrashed()->findOrFail($airport);
        if ($airport->trashed()) {
            $airport->restore();
            $airport->update(['is_active' => true]);

            return redirect()
                ->route('airports.index')
                ->with('success', 'Airport activated successfully.');
        }

        $airport->update(['is_active' => false]);
        $airport->delete();

        return redirect()
            ->route('airports.index')
            ->with('success', 'Airport deactivated successfully.');
    }

    private function validatePayload(Request $request, ?Airport $airport): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('airports', 'code')->ignore($airport?->id)],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');
        app(LocationResolver::class)->enrichFromGoogleMapsUrl($validated);
        $this->applyDestinationContext($validated);
        app(LocationResolver::class)->resolveDestinationId($validated, true);

        if (empty($validated['location'])) {
            $parts = array_filter([
                trim((string) ($validated['city'] ?? '')),
                trim((string) ($validated['province'] ?? '')),
            ]);
            if ($parts !== []) {
                $validated['location'] = implode(', ', $parts);
            }
        }

        return $validated;
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
}
