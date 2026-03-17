<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Transport;
use App\Support\ImageThumbnailGenerator;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TransportController extends Controller
{
    public function index(Request $request)
    {
        $query = Transport::query()
            ->withTrashed()
            ->with('destination:id,name')
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
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.transports.create', compact('destinations'));
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
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.transports.edit', compact('transport', 'destinations'));
    }

    public function update(Request $request, Transport $transport)
    {
        $transport->load('units');
        [$validated, $unitPayload] = $this->validatePayload($request, $transport);
        $oldUnitImagePaths = $this->extractUnitImagePaths($transport->units->all());
        $existingGallery = $this->normalizeGalleryImages($transport->gallery_images ?? []);
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGallery = array_values(array_diff($existingGallery, $removedGallery));

        if ($removedGallery !== []) {
            $this->deleteGalleryImages($removedGallery);
        }

        $newGallery = $this->storeGalleryImages($request->file('gallery_images', []), 'transports');
        $validated['gallery_images'] = array_values(array_merge($remainingGallery, $newGallery));
        unset($validated['removed_gallery_images']);

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

        return redirect()->route('transports.index')->with('success', 'Transport deactivated successfully.');
    }

    public function toggleStatus($transport)
    {
        $transport = Transport::withTrashed()->findOrFail($transport);
        if ($transport->trashed()) {
            $transport->restore();
            $transport->update(['is_active' => true]);

            return redirect()
                ->route('transports.index')
                ->with('success', 'Transport activated successfully.');
        }

        $transport->update(['is_active' => false]);
        $transport->delete();

        return redirect()
            ->route('transports.index')
            ->with('success', 'Transport deactivated successfully.');
    }

    public function removeGalleryImage(Request $request, Transport $transport)
    {
        $validated = $request->validate([
            'image' => ['required', 'string'],
        ]);

        $image = (string) $validated['image'];
        $gallery = $this->normalizeGalleryImages($transport->gallery_images ?? []);
        if (! in_array($image, $gallery, true)) {
            return response()->json([
                'message' => 'Image not found in gallery.',
            ], 404);
        }

        $remaining = array_values(array_diff($gallery, [$image]));
        $this->deleteGalleryImages([$image]);
        $transport->update(['gallery_images' => $remaining]);

        return response()->json([
            'message' => 'Image removed successfully.',
            'remaining_count' => count($remaining),
        ]);
    }

    private function validatePayload(Request $request, ?Transport $transport): array
    {
        $existingGallery = $this->normalizeGalleryImages($transport?->gallery_images ?? []);
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGalleryCount = count(array_values(array_diff($existingGallery, $removedGallery)));
        $newUploads = $request->file('gallery_images', []);
        $newUploads = is_array($newUploads) ? array_values(array_filter($newUploads)) : [];
        $newUploadsCount = count($newUploads);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('transports', 'code')->ignore($transport?->id)],
            'name' => ['required', 'string', 'max:255'],
            'transport_type' => ['required', 'string', 'max:50'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'service_scope' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'inclusions' => ['nullable', 'string'],
            'exclusions' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image'],
            'removed_gallery_images' => ['nullable', 'array'],
            'removed_gallery_images.*' => ['string'],
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
            'units.*.images.*' => ['image'],
            'units.*.benefits' => ['nullable', 'string'],
            'units.*.notes' => ['nullable', 'string'],
            'units.*.is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');
        app(LocationResolver::class)->enrichFromGoogleMapsUrl($validated);
        $this->applyDestinationContext($validated);
        app(LocationResolver::class)->resolveDestinationId($validated, true);

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

    /**
     * @param  mixed  $images
     * @return array<int, string>
     */
    private function normalizeGalleryImages($images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter($images, function ($path) {
            return is_string($path) && trim($path) !== '';
        }));
    }

    private function storeGalleryImages(array $files, string $directory): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }
            $originalPath = $file->store($directory, 'public');
            $processedPath = ImageThumbnailGenerator::processAndGenerate('public', $originalPath, 3, 2, 360, 240) ?? $originalPath;
            $stored[] = $processedPath;
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
