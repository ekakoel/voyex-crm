<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Vendor;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'service_type' => ['nullable', Rule::in(['activities', 'food_beverages', 'transports', 'island_transfers'])],
        ]);

        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->query('q', ''));
        $status = (string) ($validated['status'] ?? '');
        $serviceType = (string) ($validated['service_type'] ?? '');

        $baseQuery = Vendor::query()
            ->withTrashed()
            ->with('destination:id,name')
            ->withCount(['activities', 'foodBeverages', 'transports', 'islandTransfers'])
            ->when(mb_strlen($search) >= 3, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('province', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%")
                        ->orWhere('contact_phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true)->whereNull('deleted_at');
                }
                if ($status === 'inactive') {
                    $query->where(function ($inactive) {
                        $inactive->where('is_active', false)->orWhereNotNull('deleted_at');
                    });
                }
            })
            ->when($serviceType !== '', function ($query) use ($serviceType) {
                if ($serviceType === 'activities') {
                    $query->whereHas('activities');
                }
                if ($serviceType === 'food_beverages') {
                    $query->whereHas('foodBeverages');
                }
                if ($serviceType === 'transports') {
                    $query->whereHas('transports');
                }
                if ($serviceType === 'island_transfers') {
                    $query->whereHas('islandTransfers');
                }
            });

        $summaryQuery = clone $baseQuery;
        $summaries = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('is_active', true)->whereNull('deleted_at')->count(),
            'inactive' => (clone $summaryQuery)->where(function ($inactive) {
                $inactive->where('is_active', false)->orWhereNotNull('deleted_at');
            })->count(),
            'with_services' => (clone $summaryQuery)
                ->where(function ($q) {
                    $q->whereHas('activities')
                        ->orWhereHas('foodBeverages')
                        ->orWhereHas('transports')
                        ->orWhereHas('islandTransfers');
                })->count(),
        ];

        $vendors = $baseQuery
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('modules.vendors.index', compact('vendors', 'summaries'));
    }

    public function create()
    {
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.vendors.create', compact('destinations'));
    }

    public function store(Request $request, LocationResolver $locationResolver)
    {
        $prefilled = $request->only([
            'google_maps_url', 'location', 'city', 'province', 'country', 'address', 'latitude', 'longitude', 'timezone',
        ]);
        $locationResolver->enrichFromGoogleMapsUrl($prefilled);
        $request->merge($prefilled);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:500'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $locationResolver->enrichFromGoogleMapsUrl($validated);
        $this->applyDestinationContext($validated);
        $locationResolver->resolveDestinationId($validated, true);
        if (empty($validated['location'])) {
            $parts = array_filter([trim((string) ($validated['city'] ?? '')), trim((string) ($validated['province'] ?? ''))]);
            if ($parts !== []) {
                $validated['location'] = implode(', ', $parts);
            }
        }

        Vendor::query()->create($this->filterPersistableColumns($validated));

        return redirect()->route('vendors.index')->with('success', ui_phrase('Vendor created successfully.'));
    }

    public function edit(Vendor $vendor)
    {
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.vendors.edit', compact('vendor', 'destinations'));
    }

    public function update(Request $request, Vendor $vendor, LocationResolver $locationResolver)
    {
        $prefilled = $request->only([
            'google_maps_url', 'location', 'city', 'province', 'country', 'address', 'latitude', 'longitude', 'timezone',
        ]);
        $locationResolver->enrichFromGoogleMapsUrl($prefilled);
        $request->merge($prefilled);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:500'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $locationResolver->enrichFromGoogleMapsUrl($validated);
        $this->applyDestinationContext($validated);
        $locationResolver->resolveDestinationId($validated, true);
        if (empty($validated['location'])) {
            $parts = array_filter([trim((string) ($validated['city'] ?? '')), trim((string) ($validated['province'] ?? ''))]);
            if ($parts !== []) {
                $validated['location'] = implode(', ', $parts);
            }
        }

        $vendor->update($this->filterPersistableColumns($validated));

        return redirect()->route('vendors.index')->with('success', ui_phrase('Vendor updated successfully.'));
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->loadCount(['activities', 'foodBeverages', 'transports']);
        if (($vendor->activities_count ?? 0) > 0 || ($vendor->food_beverages_count ?? 0) > 0 || ($vendor->transports_count ?? 0) > 0) {
            return redirect()
                ->route('vendors.index')
                ->with('error', ui_phrase('Vendor cannot be deleted because it is used by Activities, Food & Beverage, or Transports. Deactivate it instead.'));
        }

        $vendor->delete();
        return redirect()->route('vendors.index')->with('success', ui_phrase('Vendor deleted successfully.'));
    }

    public function toggleStatus($vendor)
    {
        $vendor = Vendor::withTrashed()->findOrFail($vendor);
        if ($vendor->trashed()) {
            $vendor->restore();
            $vendor->update(['is_active' => true]);
            return redirect()
                ->route('vendors.index')
                ->with('success', ui_phrase('Vendor activated successfully.'));
        }

        $vendor->update(['is_active' => false]);
        $vendor->delete();

        return redirect()
            ->route('vendors.index')
            ->with('success', ui_phrase('Vendor deactivated successfully.'));
    }

    private function applyDestinationContext(array &$validated): void
    {
        $destinationId = (int) ($validated['destination_id'] ?? 0);
        if ($destinationId > 0) {
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
            return;
        }
    }

    private function filterPersistableColumns(array $payload): array
    {
        $table = (new Vendor())->getTable();
        $columns = array_flip(Schema::getColumnListing($table));

        $filtered = [];
        foreach ($payload as $key => $value) {
            if (isset($columns[$key])) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
