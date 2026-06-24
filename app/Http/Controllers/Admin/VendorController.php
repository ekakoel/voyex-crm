<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Vendor;
use App\Support\LocationResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    private const VENDOR_TYPES = [
        Vendor::TYPE_TRANSPORTATION,
        Vendor::TYPE_ISLAND_TRANSFER,
        Vendor::TYPE_FOOD_BEVERAGE,
        Vendor::TYPE_ACTIVITIES,
    ];
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'service_type' => ['nullable', Rule::in(['activities', 'food_beverages', 'transports', 'island_transfers'])],
            'type' => ['nullable', Rule::in(self::VENDOR_TYPES)],
        ]);

        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->query('q', ''));
        $status = (string) ($validated['status'] ?? '');
        $serviceType = (string) ($validated['service_type'] ?? '');
        $vendorType = (string) ($validated['type'] ?? '');

        $filteredQuery = $this->applyIndexFilters(
            Vendor::query()->withTrashed(),
            $search,
            $status,
            $serviceType,
            $vendorType
        );

        $summaries = $this->buildVendorSummaries($filteredQuery);

        $vendors = (clone $filteredQuery)
            ->with('destination:id,name')
            ->withCount(['activities', 'foodBeverages', 'transports', 'islandTransfers'])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $vendorRows = $this->buildVendorIndexRows($vendors);

        $vendorTypeOptions = Vendor::typeOptions();

        return view('modules.vendors.index', compact('vendors', 'summaries', 'vendorRows', 'vendorTypeOptions'));
    }

    public function create()
    {
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        $vendorTypeOptions = Vendor::typeOptions();

        return view('modules.vendors.create', compact('destinations', 'vendorTypeOptions'));
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
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['required', Rule::in(self::VENDOR_TYPES)],
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
        $validated['types'] = $this->normalizeVendorTypes($validated['types'] ?? []);
        $validated['type'] = $validated['types'][0] ?? null;
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

        $vendorTypeOptions = Vendor::typeOptions();

        return view('modules.vendors.edit', compact('vendor', 'destinations', 'vendorTypeOptions'));
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
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['required', Rule::in(self::VENDOR_TYPES)],
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
        $validated['types'] = $this->normalizeVendorTypes($validated['types'] ?? []);
        $validated['type'] = $validated['types'][0] ?? null;
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
        abort_unless(auth()->user()?->canManageActivationActions(), 403);
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

    private function applyIndexFilters(
        Builder $query,
        string $search,
        string $status,
        string $serviceType,
        string $vendorType
    ): Builder {
        return $query
            ->when(mb_strlen($search) >= 3, function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('province', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%")
                        ->orWhere('contact_phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function (Builder $query) use ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true)->whereNull('deleted_at');
                }

                if ($status === 'inactive') {
                    $query->where(function (Builder $inactive) {
                        $inactive->where('is_active', false)->orWhereNotNull('deleted_at');
                    });
                }
            })
            ->when($serviceType !== '', function (Builder $query) use ($serviceType) {
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
            })
            ->when($vendorType !== '', function (Builder $query) use ($vendorType) {
                $this->whereVendorHasType($query, $vendorType);
            });
    }

    private function buildVendorSummaries(Builder $query): array
    {
        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 AND deleted_at IS NULL THEN 1 ELSE 0 END) as active_total')
            ->selectRaw('SUM(CASE WHEN is_active = 0 OR deleted_at IS NOT NULL THEN 1 ELSE 0 END) as inactive_total')
            ->selectRaw(
                'SUM(CASE WHEN type = ? OR types LIKE ? THEN 1 ELSE 0 END) as transportation_total',
                [Vendor::TYPE_TRANSPORTATION, $this->vendorTypeLikePattern(Vendor::TYPE_TRANSPORTATION)]
            )
            ->selectRaw(
                'SUM(CASE WHEN type = ? OR types LIKE ? THEN 1 ELSE 0 END) as island_transfer_total',
                [Vendor::TYPE_ISLAND_TRANSFER, $this->vendorTypeLikePattern(Vendor::TYPE_ISLAND_TRANSFER)]
            )
            ->selectRaw(
                'SUM(CASE WHEN type = ? OR types LIKE ? THEN 1 ELSE 0 END) as food_beverage_total',
                [Vendor::TYPE_FOOD_BEVERAGE, $this->vendorTypeLikePattern(Vendor::TYPE_FOOD_BEVERAGE)]
            )
            ->selectRaw(
                'SUM(CASE WHEN type = ? OR types LIKE ? THEN 1 ELSE 0 END) as activities_total',
                [Vendor::TYPE_ACTIVITIES, $this->vendorTypeLikePattern(Vendor::TYPE_ACTIVITIES)]
            )
            ->first();

        return [
            'total' => (int) ($summary->total ?? 0),
            'active' => (int) ($summary->active_total ?? 0),
            'inactive' => (int) ($summary->inactive_total ?? 0),
            'transportation' => (int) ($summary->transportation_total ?? 0),
            'island_transfer' => (int) ($summary->island_transfer_total ?? 0),
            'food_beverage' => (int) ($summary->food_beverage_total ?? 0),
            'activities' => (int) ($summary->activities_total ?? 0),
        ];
    }

    private function normalizeVendorTypes(array $types): array
    {
        return array_values(array_intersect(self::VENDOR_TYPES, array_unique($types)));
    }

    private function whereVendorHasType(Builder $query, string $vendorType): void
    {
        $query->where(function (Builder $typeQuery) use ($vendorType) {
            $typeQuery
                ->where('type', $vendorType)
                ->orWhere('types', 'like', $this->vendorTypeLikePattern($vendorType));
        });
    }

    private function vendorTypeLikePattern(string $vendorType): string
    {
        return '%"' . $vendorType . '"%';
    }

    private function buildVendorIndexRows($vendors): array
    {
        return $vendors->getCollection()
            ->map(function (Vendor $vendor): array {
                $serviceBadges = [
                    [
                        'label' => ui_phrase('Act'),
                        'value' => (int) ($vendor->activities_count ?? 0),
                        'class' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
                    ],
                    [
                        'label' => ui_phrase('F&B'),
                        'value' => (int) ($vendor->food_beverages_count ?? 0),
                        'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                    ],
                    [
                        'label' => ui_phrase('Trf'),
                        'value' => (int) ($vendor->transports_count ?? 0),
                        'class' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300',
                    ],
                    [
                        'label' => ui_phrase('Isl'),
                        'value' => (int) ($vendor->island_transfers_count ?? 0),
                        'class' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                    ],
                ];

                $filteredServiceBadges = array_values(array_filter(
                    $serviceBadges,
                    static fn (array $badge): bool => (int) ($badge['value'] ?? 0) > 0
                ));
                $serviceCount = array_sum(array_column($filteredServiceBadges, 'value'));
                $isActive = (bool) ($vendor->is_active ?? false);
                $typeLabels = $vendor->typeLabels();

                return [
                    'vendor' => $vendor,
                    'name' => (string) ($vendor->name ?? '-'),
                    'destination_name' => (string) ($vendor->destination?->name ?? '-'),
                    'vendor_type' => $typeLabels !== []
                        ? implode(', ', $typeLabels)
                        : ($serviceCount > 0 ? ui_phrase('Service Provider') : ui_phrase('General')),
                    'contact_name' => (string) ($vendor->contact_name ?? '-'),
                    'contact_phone' => (string) ($vendor->contact_phone ?? '-'),
                    'contact_email' => (string) ($vendor->contact_email ?? '-'),
                    'service_badges' => $filteredServiceBadges,
                    'status_key' => $isActive ? 'active' : 'inactive',
                    'toggle_title' => $isActive
                        ? ui_phrase('Deactivate') . ' ' . ui_phrase('Vendor')
                        : ui_phrase('Activate') . ' ' . ui_phrase('Vendor'),
                    'toggle_message' => $isActive
                        ? ui_phrase('confirm deactivate')
                        : ui_phrase('confirm activate'),
                    'toggle_impact' => $isActive
                        ? ui_phrase('Vendor will be set as inactive and hidden from active options.')
                        : ui_phrase('Vendor will be set as active and available for selection.'),
                    'toggle_label' => $isActive ? ui_phrase('Deactivate') : ui_phrase('Activate'),
                    'toggle_icon' => $isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4',
                    'toggle_class' => $isActive
                        ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20'
                        : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20',
                ];
            })
            ->all();
    }
}
