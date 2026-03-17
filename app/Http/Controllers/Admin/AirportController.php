<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\Destination;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $query = Airport::query()
            ->withTrashed()
            ->with('destination:id,name')
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

        $airports = $query->paginate(10)->withQueryString();

        return view('modules.airports.index', compact('airports'));
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

        return view('modules.airports.show', compact('airport'));
    }

    public function edit(Airport $airport)
    {
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
