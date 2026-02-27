<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\ImageThumbnailGenerator;
use App\Models\Vendor;
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
        $types = Activity::query()->select('activity_type')->distinct()->orderBy('activity_type')->pluck('activity_type');

        return view('modules.activities.index', compact('activities', 'vendors', 'types'));
    }

    public function create()
    {
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);

        return view('modules.activities.create', compact('vendors'));
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

        return view('modules.activities.edit', compact('activity', 'vendors'));
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $this->validatePayload($request, $activity);
        if ($request->hasFile('gallery_images')) {
            $this->deleteGalleryImages($activity->gallery_images ?? []);
            $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'activities');
        } else {
            $validated['gallery_images'] = $activity->gallery_images ?? [];
        }
        $activity->update($validated);

        return redirect()->route('activities.index')->with('success', 'Activity updated successfully.');
    }

    public function destroy(Activity $activity)
    {
        $this->deleteGalleryImages($activity->gallery_images ?? []);
        $activity->delete();

        return redirect()->route('activities.index')->with('success', 'Activity deleted successfully.');
    }

    private function validatePayload(Request $request, ?Activity $activity): array
    {
        $hasExistingGallery = $activity && is_array($activity->gallery_images) && count($activity->gallery_images) > 0;
        $galleryRules = ['array', 'max:3'];
        if (! $hasExistingGallery) {
            array_unshift($galleryRules, 'required', 'min:1');
        } elseif ($request->hasFile('gallery_images')) {
            array_unshift($galleryRules, 'min:1');
        } else {
            array_unshift($galleryRules, 'sometimes');
        }

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'activity_type' => ['required', 'string', 'max:100'],
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
            'gallery_images' => $galleryRules,
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['currency'] = strtoupper($validated['currency']);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
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



