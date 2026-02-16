<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceItemController extends Controller
{
    public function index(string $serviceType): View
    {
        $serviceType = $this->resolveType($serviceType);

        $services = Service::query()
            ->with('vendor')
            ->where('service_type', $serviceType)
            ->orderBy('name')
            ->paginate(10);

        return view('admin.services.items.index', [
            'services' => $services,
            'serviceType' => $serviceType,
            'serviceTypeLabel' => Service::labels()[$serviceType],
            'typeLabels' => Service::labels(),
        ]);
    }

    public function create(string $serviceType): View
    {
        $serviceType = $this->resolveType($serviceType);

        return view('admin.services.items.create', [
            'serviceType' => $serviceType,
            'serviceTypeLabel' => Service::labels()[$serviceType],
            'typeLabels' => Service::labels(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $serviceType): RedirectResponse
    {
        $serviceType = $this->resolveType($serviceType);

        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['service_type'] = $serviceType;
        $validated['is_active'] = $request->boolean('is_active');

        Service::query()->create($validated);

        return redirect()
            ->route($this->routeName($serviceType, 'index'))
            ->with('success', "{$this->serviceNoun($serviceType)} created successfully.");
    }

    public function edit(string $serviceType, Service $service): View
    {
        $serviceType = $this->resolveType($serviceType);
        $this->guardServiceType($service, $serviceType);

        return view('admin.services.items.edit', [
            'service' => $service,
            'serviceType' => $serviceType,
            'serviceTypeLabel' => Service::labels()[$serviceType],
            'typeLabels' => Service::labels(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $serviceType, Service $service): RedirectResponse
    {
        $serviceType = $this->resolveType($serviceType);
        $this->guardServiceType($service, $serviceType);

        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'service_type' => ['sometimes', Rule::in(Service::TYPES)],
        ]);

        $validated['service_type'] = $serviceType;
        $validated['is_active'] = $request->boolean('is_active');

        $service->update($validated);

        return redirect()
            ->route($this->routeName($serviceType, 'index'))
            ->with('success', "{$this->serviceNoun($serviceType)} updated successfully.");
    }

    public function destroy(string $serviceType, Service $service): RedirectResponse
    {
        $serviceType = $this->resolveType($serviceType);
        $this->guardServiceType($service, $serviceType);

        $service->delete();

        return redirect()
            ->route($this->routeName($serviceType, 'index'))
            ->with('success', "{$this->serviceNoun($serviceType)} deleted successfully.");
    }

    private function resolveType(string $serviceType): string
    {
        abort_unless(in_array($serviceType, Service::TYPES, true), 404);

        return $serviceType;
    }

    private function guardServiceType(Service $service, string $serviceType): void
    {
        abort_unless($service->service_type === $serviceType, 404);
    }

    private function serviceNoun(string $serviceType): string
    {
        $map = [
            Service::TYPE_ACCOMMODATIONS => 'Accommodation service',
            Service::TYPE_TRANSPORTS => 'Transport service',
            Service::TYPE_GUIDES => 'Guide service',
            Service::TYPE_ATTRACTIONS => 'Attraction service',
            Service::TYPE_TRAVEL_ACTIVITIES => 'Travel activity service',
        ];

        return $map[$serviceType] ?? 'Service';
    }

    private function routeName(string $serviceType, string $action): string
    {
        $prefixMap = [
            Service::TYPE_ACCOMMODATIONS => 'accommodations',
            Service::TYPE_TRANSPORTS => 'transports',
            Service::TYPE_GUIDES => 'guides',
            Service::TYPE_ATTRACTIONS => 'attractions',
            Service::TYPE_TRAVEL_ACTIVITIES => 'travel-activities',
        ];

        $prefix = $prefixMap[$serviceType] ?? 'accommodations';

        return "admin.services.items.{$prefix}.{$action}";
    }
}
