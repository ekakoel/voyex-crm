<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Vendor;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    public function index()
    {
        $services = Service::query()->with('vendor')->orderBy('name')->paginate(10);
        return view('admin.services-catalog.index', compact('services'));
    }

    public function create()
    {
        $vendors = Vendor::query()->orderBy('name')->get();
        return view('admin.services-catalog.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        Service::query()->create($validated);

        return redirect()->route('admin.services-catalog.index')->with('success', 'Service created successfully.');
    }

    public function edit(Service $service)
    {
        $vendors = Vendor::query()->orderBy('name')->get();
        return view('admin.services-catalog.edit', compact('service', 'vendors'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $service->update($validated);

        return redirect()->route('admin.services-catalog.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('admin.services-catalog.index')->with('success', 'Service deleted successfully.');
    }
}
