<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Vendor;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FoodBeverageController extends Controller
{
    public function index(Request $request)
    {
        $query = FoodBeverage::query()->withTrashed()->with('vendor:id,name')->latest('id');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->integer('vendor_id'));
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', (string) $request->string('service_type'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $foodBeverages = $query->paginate($perPage)->withQueryString();
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $types = $this->buildTypeFilterOptions();

        return view('modules.food-beverages.index', compact('foodBeverages', 'vendors', 'types'));
    }

    public function create(Request $request)
    {
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province', 'destination_id']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);
        $standardServiceTypes = $this->serviceTypes();
        $serviceTypes = $standardServiceTypes;
        $prefill = [];
        $copiedFrom = null;

        $copyId = (int) $request->integer('copy');
        if ($copyId > 0) {
            $copiedFrom = FoodBeverage::query()
                ->withTrashed()
                ->find($copyId);

            if ($copiedFrom) {
                $prefill = [
                    'vendor_id' => $copiedFrom->vendor_id,
                    'name' => trim(((string) $copiedFrom->name) . ' (Copy)'),
                    'service_type' => $copiedFrom->service_type,
                    'duration_minutes' => $copiedFrom->duration_minutes,
                    'meal_period' => $copiedFrom->meal_period,
                    'contract_rate' => $copiedFrom->contract_rate,
                    'publish_rate' => $copiedFrom->publish_rate,
                    'menu_highlights' => $copiedFrom->menu_highlights,
                    'notes' => $copiedFrom->notes,
                    'is_active' => (bool) $copiedFrom->is_active,
                ];
            }
        }

        return view('modules.food-beverages.create', compact('vendors', 'destinations', 'serviceTypes', 'standardServiceTypes', 'prefill', 'copiedFrom'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);
        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'food-beverages');
        FoodBeverage::query()->create($validated);

        return redirect()->route('food-beverages.index')->with('success', 'F&B service created successfully.');
    }

    public function edit(FoodBeverage $foodBeverage)
    {
        $foodBeverage->loadMissing('vendor:id,name,contact_name,contact_phone,contact_email,website,location,address,city,province,country,timezone');
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province', 'destination_id']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);
        $standardServiceTypes = $this->serviceTypes();
        $serviceTypes = $standardServiceTypes;
        if (! in_array((string) $foodBeverage->service_type, $serviceTypes, true)) {
            $serviceTypes[] = (string) $foodBeverage->service_type;
        }

        return view('modules.food-beverages.edit', compact('foodBeverage', 'vendors', 'destinations', 'serviceTypes', 'standardServiceTypes'));
    }

    public function update(Request $request, FoodBeverage $foodBeverage)
    {
        $validated = $this->validatePayload($request, $foodBeverage);
        $existingGallery = $this->normalizeGalleryImages($foodBeverage->gallery_images ?? []);
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGallery = array_values(array_diff($existingGallery, $removedGallery));

        if ($removedGallery !== []) {
            $this->deleteGalleryImages($removedGallery);
        }

        $newGallery = $this->storeGalleryImages($request->file('gallery_images', []), 'food-beverages');
        $validated['gallery_images'] = array_values(array_merge($remainingGallery, $newGallery));
        unset($validated['removed_gallery_images']);
        $foodBeverage->update($validated);

        return redirect()->route('food-beverages.index')->with('success', 'F&B service updated successfully.');
    }

    public function destroy(FoodBeverage $foodBeverage)
    {
        $this->deleteGalleryImages($foodBeverage->gallery_images ?? []);
        $foodBeverage->delete();

        return redirect()->route('food-beverages.index')->with('success', 'F&B service deactivated successfully.');
    }

    public function toggleStatus($foodBeverage)
    {
        $foodBeverage = FoodBeverage::withTrashed()->findOrFail($foodBeverage);
        if ($foodBeverage->trashed()) {
            $foodBeverage->restore();
            $foodBeverage->update(['is_active' => true]);

            return redirect()
                ->route('food-beverages.index')
                ->with('success', 'F&B service activated successfully.');
        }

        $foodBeverage->update(['is_active' => false]);
        $foodBeverage->delete();

        return redirect()
            ->route('food-beverages.index')
            ->with('success', 'F&B service deactivated successfully.');
    }

    public function removeGalleryImage(Request $request, FoodBeverage $foodBeverage)
    {
        $validated = $request->validate([
            'image' => ['required', 'string'],
        ]);

        $image = (string) $validated['image'];
        $gallery = $this->normalizeGalleryImages($foodBeverage->gallery_images ?? []);
        if (! in_array($image, $gallery, true)) {
            return response()->json([
                'message' => 'Image not found in gallery.',
            ], 404);
        }

        $remaining = array_values(array_diff($gallery, [$image]));
        $this->deleteGalleryImages([$image]);
        $foodBeverage->update(['gallery_images' => $remaining]);

        return response()->json([
            'message' => 'Image removed successfully.',
            'remaining_count' => count($remaining),
        ]);
    }

    private function validatePayload(Request $request, ?FoodBeverage $foodBeverage): array
    {
        // Backward compatibility for legacy payload keys during transition.
        if (! $request->has('contract_rate') && $request->has('contract_price')) {
            $request->merge(['contract_rate' => $request->input('contract_price')]);
        }
        if (! $request->has('publish_rate') && $request->has('agent_price')) {
            $request->merge(['publish_rate' => $request->input('agent_price')]);
        }

        $existingGallery = $this->normalizeGalleryImages($foodBeverage?->gallery_images ?? []);
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGalleryCount = count(array_values(array_diff($existingGallery, $removedGallery)));
        $newUploads = $request->file('gallery_images', []);
        $newUploads = is_array($newUploads) ? array_values(array_filter($newUploads)) : [];
        $newUploadsCount = count($newUploads);

        $allowedTypes = $this->serviceTypes();
        $currentType = trim((string) ($foodBeverage?->service_type ?? ''));
        if ($currentType !== '' && ! in_array($currentType, $allowedTypes, true)) {
            $allowedTypes[] = $currentType;
        }

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', 'max:100', Rule::in($allowedTypes)],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'contract_rate' => ['nullable', 'numeric', 'min:0'],
            'publish_rate' => ['nullable', 'numeric', 'min:0'],
            'meal_period' => ['nullable', 'string', 'max:50'],
            'menu_highlights' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image'],
            'removed_gallery_images' => ['nullable', 'array'],
            'removed_gallery_images.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function serviceTypes(): array
    {
        return [
            'restaurant',
            'cafe',
            'buffet',
            'set_menu',
            'snack_box',
            'coffee_break',
            'other',
        ];
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function buildTypeFilterOptions(): array
    {
        $dbTypes = FoodBeverage::query()
            ->select('service_type')
            ->whereNotNull('service_type')
            ->where('service_type', '!=', '')
            ->distinct()
            ->pluck('service_type')
            ->map(fn ($type) => (string) $type)
            ->all();

        $standard = $this->serviceTypes();
        $ordered = [];

        foreach ($standard as $type) {
            if (in_array($type, $dbTypes, true)) {
                $ordered[] = $type;
            }
        }

        $unknown = array_values(array_filter($dbTypes, fn ($type) => ! in_array($type, $standard, true)));
        sort($unknown);
        $ordered = array_merge($ordered, $unknown);

        return array_map(function (string $type): array {
            return [
                'value' => $type,
                'label' => $this->formatTypeLabel($type),
            ];
        }, $ordered);
    }

    private function formatTypeLabel(string $value): string
    {
        return ucwords(str_replace('_', ' ', trim($value)));
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
}
