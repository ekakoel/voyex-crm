<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $query = FoodBeverage::query()->with('vendor:id,name')->latest('id');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->integer('vendor_id'));
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', (string) $request->string('service_type'));
        }

        $foodBeverages = $query->paginate(10)->withQueryString();
        $vendors = Vendor::query()->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $types = $this->buildTypeFilterOptions();

        return view('modules.food-beverages.index', compact('foodBeverages', 'vendors', 'types'));
    }

    public function create()
    {
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $standardServiceTypes = $this->serviceTypes();
        $serviceTypes = $standardServiceTypes;

        return view('modules.food-beverages.create', compact('vendors', 'serviceTypes', 'standardServiceTypes'));
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
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $standardServiceTypes = $this->serviceTypes();
        $serviceTypes = $standardServiceTypes;
        if (! in_array((string) $foodBeverage->service_type, $serviceTypes, true)) {
            $serviceTypes[] = (string) $foodBeverage->service_type;
        }

        return view('modules.food-beverages.edit', compact('foodBeverage', 'vendors', 'serviceTypes', 'standardServiceTypes'));
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

        return redirect()->route('food-beverages.index')->with('success', 'F&B service deleted successfully.');
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
            'contract_price' => ['nullable', 'numeric', 'min:0'],
            'agent_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'meal_period' => ['nullable', 'string', 'max:50'],
            'menu_highlights' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image'],
            'removed_gallery_images' => ['nullable', 'array'],
            'removed_gallery_images.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['currency'] = strtoupper($validated['currency']);
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
