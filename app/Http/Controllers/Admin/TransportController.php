<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transport;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TransportController extends Controller
{
    public function index(Request $request)
    {
        $query = Transport::query()
            ->withCount('units')
            ->withMin('units', 'contract_rate')
            ->latest('id');

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('provider_name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%");
            });
        }

        if ($request->filled('transport_type')) {
            $query->where('transport_type', (string) $request->string('transport_type'));
        }

        $transports = $query->paginate(10)->withQueryString();
        $types = Transport::query()->select('transport_type')->distinct()->orderBy('transport_type')->pluck('transport_type');

        return view('modules.transports.index', compact('transports', 'types'));
    }

    public function create()
    {
        return view('modules.transports.create');
    }

    public function show(Transport $transport)
    {
        $transport->load([
            'units' => function ($query) {
                $query->orderBy('contract_rate')->orderBy('name');
            },
        ]);

        return view('modules.transports.show', compact('transport'));
    }

    public function store(Request $request)
    {
        [$validated, $unitPayload] = $this->validatePayload($request, null);
        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'transports');

        DB::transaction(function () use ($validated, $unitPayload) {
            $transport = Transport::query()->create($validated);
            $transport->units()->createMany($unitPayload);
        });

        return redirect()->route('transports.index')->with('success', 'Transport created successfully.');
    }

    public function edit(Transport $transport)
    {
        $transport->load('units');
        return view('modules.transports.edit', compact('transport'));
    }

    public function update(Request $request, Transport $transport)
    {
        $transport->load('units');
        [$validated, $unitPayload] = $this->validatePayload($request, $transport);
        $oldUnitImagePaths = $this->extractUnitImagePaths($transport->units->all());

        if ($request->hasFile('gallery_images')) {
            $this->deleteGalleryImages($transport->gallery_images ?? []);
            $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'transports');
        } else {
            $validated['gallery_images'] = $transport->gallery_images ?? [];
        }

        DB::transaction(function () use ($transport, $validated, $unitPayload) {
            $transport->update($validated);
            $transport->units()->delete();
            $transport->units()->createMany($unitPayload);
        });

        $newUnitImagePaths = $this->extractUnitImagePaths($unitPayload);
        $staleUnitImagePaths = array_values(array_diff($oldUnitImagePaths, $newUnitImagePaths));
        $this->deleteGalleryImages($staleUnitImagePaths);

        return redirect()->route('transports.index')->with('success', 'Transport updated successfully.');
    }

    public function destroy(Transport $transport)
    {
        $transport->load('units');
        $this->deleteGalleryImages($transport->gallery_images ?? []);
        $this->deleteGalleryImages($this->extractUnitImagePaths($transport->units->all()));
        $transport->delete();

        return redirect()->route('transports.index')->with('success', 'Transport deleted successfully.');
    }

    private function validatePayload(Request $request, ?Transport $transport): array
    {
        $hasExistingGallery = $transport && is_array($transport->gallery_images) && count($transport->gallery_images) > 0;
        $galleryRules = ['array', 'max:5'];
        if (! $hasExistingGallery) {
            array_unshift($galleryRules, 'required', 'min:1');
        } elseif ($request->hasFile('gallery_images')) {
            array_unshift($galleryRules, 'min:1');
        } else {
            array_unshift($galleryRules, 'sometimes');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('transports', 'code')->ignore($transport?->id)],
            'name' => ['required', 'string', 'max:255'],
            'transport_type' => ['required', 'string', 'max:50'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'service_scope' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'inclusions' => ['nullable', 'string'],
            'exclusions' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'gallery_images' => $galleryRules,
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],

            'units' => ['required', 'array', 'min:1'],
            'units.*.name' => ['required', 'string', 'max:120'],
            'units.*.vehicle_type' => ['nullable', 'string', 'max:80'],
            'units.*.brand_model' => ['nullable', 'string', 'max:120'],
            'units.*.seat_capacity' => ['required', 'integer', 'min:1', 'max:80'],
            'units.*.luggage_capacity' => ['nullable', 'integer', 'min:0'],
            'units.*.contract_rate' => ['required', 'numeric', 'min:0'],
            'units.*.publish_rate' => ['nullable', 'numeric', 'min:0'],
            'units.*.overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'units.*.currency' => ['required', 'string', 'size:3'],
            'units.*.fuel_type' => ['nullable', 'string', 'max:60'],
            'units.*.transmission' => ['nullable', 'string', 'max:40'],
            'units.*.air_conditioned' => ['nullable', 'boolean'],
            'units.*.with_driver' => ['nullable', 'boolean'],
            'units.*.existing_images' => ['nullable', 'array', 'max:2'],
            'units.*.existing_images.*' => ['string'],
            'units.*.images' => ['nullable', 'array', 'max:2'],
            'units.*.images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'units.*.benefits' => ['nullable', 'string'],
            'units.*.notes' => ['nullable', 'string'],
            'units.*.is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');

        $unitPayload = [];
        foreach ($validated['units'] as $index => $row) {
            $existingImages = array_values(array_filter((array) ($row['existing_images'] ?? []), function ($path) {
                return is_string($path) && $path !== '';
            }));
            $uploadedImages = $this->storeGalleryImages(
                $request->file("units.{$index}.images", []),
                'transports/units'
            );
            $unitImages = $uploadedImages !== [] ? $uploadedImages : $existingImages;
            $unitImages = array_slice(array_values(array_unique($unitImages)), 0, 2);

            $unitPayload[] = [
                'name' => trim((string) $row['name']),
                'vehicle_type' => $row['vehicle_type'] ?? null,
                'brand_model' => $row['brand_model'] ?? null,
                'seat_capacity' => (int) ($row['seat_capacity'] ?? 4),
                'luggage_capacity' => ($row['luggage_capacity'] ?? '') !== '' ? (int) $row['luggage_capacity'] : null,
                'contract_rate' => $row['contract_rate'],
                'publish_rate' => ($row['publish_rate'] ?? '') !== '' ? $row['publish_rate'] : null,
                'overtime_rate' => ($row['overtime_rate'] ?? '') !== '' ? $row['overtime_rate'] : null,
                'currency' => strtoupper((string) ($row['currency'] ?? 'IDR')),
                'fuel_type' => $row['fuel_type'] ?? null,
                'transmission' => $row['transmission'] ?? null,
                'air_conditioned' => filter_var($row['air_conditioned'] ?? true, FILTER_VALIDATE_BOOL),
                'with_driver' => filter_var($row['with_driver'] ?? true, FILTER_VALIDATE_BOOL),
                'images' => $unitImages,
                'benefits' => $row['benefits'] ?? null,
                'notes' => $row['notes'] ?? null,
                'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOL),
            ];
        }

        unset($validated['units']);

        return [$validated, $unitPayload];
    }

    private function extractUnitImagePaths(array $units): array
    {
        $paths = [];
        foreach ($units as $unit) {
            $images = is_array($unit) ? ($unit['images'] ?? []) : ($unit->images ?? []);
            foreach ((array) $images as $path) {
                if (is_string($path) && $path !== '') {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
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
}
