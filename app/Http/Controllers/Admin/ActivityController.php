<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Vendor;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::query()->with('vendor:id,name')->latest('id');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->integer('vendor_id'));
        }

        if ($request->filled('activity_type')) {
            $query->where('activity_type', (string) $request->string('activity_type'));
        }

        $activities = $query->paginate(10)->withQueryString();
        $vendors = Vendor::query()->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $types = $this->buildTypeFilterOptions();

        return view('modules.activities.index', compact('activities', 'vendors', 'types'));
    }

    public function create()
    {
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $standardActivityTypes = $this->activityTypes();
        $activityTypes = $standardActivityTypes;

        return view('modules.activities.create', compact('vendors', 'activityTypes', 'standardActivityTypes'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);
        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'activities');
        Activity::query()->create($validated);

        return redirect()->route('activities.index')->with('success', 'Activity created successfully.');
    }

    public function edit(Activity $activity)
    {
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $standardActivityTypes = $this->activityTypes();
        $activityTypes = $standardActivityTypes;
        if (! in_array((string) $activity->activity_type, $activityTypes, true)) {
            $activityTypes[] = (string) $activity->activity_type;
        }

        return view('modules.activities.edit', compact('activity', 'vendors', 'activityTypes', 'standardActivityTypes'));
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $this->validatePayload($request, $activity);
        $existingGallery = $this->normalizeGalleryImages($activity->gallery_images ?? []);
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGallery, $requestedRemoved));
        $remainingGallery = array_values(array_diff($existingGallery, $removedGallery));

        if ($removedGallery !== []) {
            $this->deleteGalleryImages($removedGallery);
        }

        $newGallery = $this->storeGalleryImages($request->file('gallery_images', []), 'activities');
        $validated['gallery_images'] = array_values(array_merge($remainingGallery, $newGallery));
        unset($validated['removed_gallery_images']);
        $activity->update($validated);

        return redirect()->route('activities.index')->with('success', 'Activity updated successfully.');
    }

    public function destroy(Activity $activity)
    {
        $this->deleteGalleryImages($activity->gallery_images ?? []);
        $activity->delete();

        return redirect()->route('activities.index')->with('success', 'Activity deleted successfully.');
    }

    public function removeGalleryImage(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'image' => ['required', 'string'],
        ]);

        $image = (string) $validated['image'];
        $gallery = $this->normalizeGalleryImages($activity->gallery_images ?? []);
        if (! in_array($image, $gallery, true)) {
            return response()->json([
                'message' => 'Image not found in gallery.',
            ], 404);
        }

        $remaining = array_values(array_diff($gallery, [$image]));
        $this->deleteGalleryImages([$image]);
        $activity->update(['gallery_images' => $remaining]);

        return response()->json([
            'message' => 'Image removed successfully.',
            'remaining_count' => count($remaining),
        ]);
    }

    private function validatePayload(Request $request, ?Activity $activity): array
    {
        $existingGalleryImages = $this->normalizeGalleryImages($activity?->gallery_images);
        $requestedRemoved = $request->input('removed_gallery_images', []);
        $requestedRemoved = is_array($requestedRemoved) ? $requestedRemoved : [];
        $removedGallery = array_values(array_intersect($existingGalleryImages, $requestedRemoved));
        $remainingGalleryCount = count(array_values(array_diff($existingGalleryImages, $removedGallery)));
        $newUploads = $request->file('gallery_images', []);
        $newUploads = is_array($newUploads) ? array_values(array_filter($newUploads)) : [];
        $newUploadsCount = count($newUploads);

        $allowedTypes = $this->activityTypes();
        $currentType = trim((string) ($activity?->activity_type ?? ''));
        if ($currentType !== '' && ! in_array($currentType, $allowedTypes, true)) {
            $allowedTypes[] = $currentType;
        }

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'activity_type' => ['required', 'string', 'max:100', Rule::in($allowedTypes)],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'benefits' => ['nullable', 'string'],
            'descriptions' => ['nullable', 'string'],
            'contract_price' => ['nullable', 'numeric', 'min:0'],
            'agent_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'capacity_min' => ['nullable', 'integer', 'min:1'],
            'capacity_max' => ['nullable', 'integer', 'min:1', 'gte:capacity_min'],
            'includes' => ['nullable', 'string'],
            'excludes' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'gallery_images' => ['nullable', 'array', 'max:3'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp'],
            'removed_gallery_images' => ['nullable', 'array'],
            'removed_gallery_images.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $finalGalleryCount = ($activity ? $remainingGalleryCount : 0) + $newUploadsCount;
        if ($finalGalleryCount < 1) {
            $message = $activity
                ? 'Gallery images minimal 1 file. Hapus semua gambar lama hanya jika upload gambar baru.'
                : 'Gallery images minimal 1 file.';
            throw \Illuminate\Validation\ValidationException::withMessages([
                'gallery_images' => $message,
            ]);
        }
        if ($finalGalleryCount > 3) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'gallery_images' => 'Maksimal total 3 gambar (existing + upload baru).',
            ]);
        }

        $validated['currency'] = strtoupper($validated['currency']);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function activityTypes(): array
    {
        return [
            'sightseeing_tour',
            'cultural_experience',
            'nature_adventure',
            'water_activity',
            'outdoor_adventure',
            'wellness_spa',
            'culinary_experience',
            'entertainment_show',
            'workshop_class',
            'transport_shuttle',
            'other',
        ];
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function buildTypeFilterOptions(): array
    {
        $dbTypes = Activity::query()
            ->select('activity_type')
            ->whereNotNull('activity_type')
            ->where('activity_type', '!=', '')
            ->distinct()
            ->pluck('activity_type')
            ->map(fn ($type) => (string) $type)
            ->all();

        $standard = $this->activityTypes();
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



