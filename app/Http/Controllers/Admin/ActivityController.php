<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\NormalizesDisplayCurrencyToIdr;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Destination;
use App\Models\Vendor;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    use NormalizesDisplayCurrencyToIdr;

    public function index(Request $request)
    {
        $query = Activity::query()->withTrashed()->with(['vendor:id,name', 'activityType:id,name'])->latest('id');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->integer('vendor_id'));
        }

        if ($request->filled('activity_type_id')) {
            $query->where('activity_type_id', (int) $request->integer('activity_type_id'));
        } elseif ($request->filled('activity_type')) {
            $query->where('activity_type', (string) $request->string('activity_type'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $activities = $query->paginate($perPage)->withQueryString();
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $types = $this->buildTypeFilterOptions();
        $statsCards = [
            [
                'key' => 'total',
                'label' => 'Total Activities',
                'value' => Activity::query()->count(),
                'caption' => 'All records',
            ],
            [
                'key' => 'active',
                'label' => 'Active Activities',
                'value' => Activity::query()->whereNull('deleted_at')->count(),
                'caption' => 'Currently active',
            ],
            [
                'key' => 'vendors',
                'label' => 'Vendors',
                'value' => Vendor::query()->where('is_active', true)->count(),
                'caption' => 'Active vendors',
            ],
        ];

        if ($this->wantsAjaxFragment($request)) {
            return response()->json([
                'html' => view('modules.activities.partials._index-results', compact('activities'))->render(),
                'url' => route('activities.index', $request->query()),
            ]);
        }

        return view('modules.activities.index', compact('activities', 'vendors', 'types', 'statsCards'));
    }

    public function create()
    {
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province', 'destination_id']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);
        $activityTypes = ActivityType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        return view('modules.activities.create', compact('vendors', 'destinations', 'activityTypes'));
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
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province', 'destination_id']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);
        $activityTypes = ActivityType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
        if (! $activity->activity_type_id && trim((string) $activity->activity_type) !== '') {
            $resolvedType = $this->resolveOrCreateActivityType((string) $activity->activity_type);
            $activity->activity_type_id = (int) $resolvedType->id;
            $activity->save();
        }

        return view('modules.activities.edit', compact('activity', 'vendors', 'destinations', 'activityTypes'));
    }

    public function show($activity)
    {
        $activity = Activity::query()
            ->withTrashed()
            ->with(['vendor:id,name,contact_name,contact_phone,contact_email,website,location,address,city,province,country,timezone', 'activityType:id,name'])
            ->findOrFail($activity);

        return view('modules.activities.show', compact('activity'));
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

        return redirect()->route('activities.index')->with('success', 'Activity deactivated successfully.');
    }

    public function toggleStatus($activity)
    {
        $activity = Activity::withTrashed()->findOrFail($activity);
        if ($activity->trashed()) {
            $activity->restore();
            $activity->update(['is_active' => true]);

            return redirect()
                ->route('activities.index')
                ->with('success', 'Activity activated successfully.');
        }

        $activity->update(['is_active' => false]);
        $activity->delete();

        return redirect()
            ->route('activities.index')
            ->with('success', 'Activity deactivated successfully.');
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
        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'activity_type_name' => ['required', 'string', 'max:100'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'benefits' => ['nullable', 'string'],
            'descriptions' => ['nullable', 'string'],
            'adult_contract_rate' => ['nullable', 'numeric', 'min:0'],
            'child_contract_rate' => ['nullable', 'numeric', 'min:0'],
            'adult_markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'adult_markup' => ['nullable', 'numeric', 'min:0'],
            'child_markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'child_markup' => ['nullable', 'numeric', 'min:0'],
            'adult_publish_rate' => ['nullable', 'numeric', 'min:0'],
            'child_publish_rate' => ['nullable', 'numeric', 'min:0'],
            'capacity_min' => ['nullable', 'integer', 'min:1'],
            'capacity_max' => ['nullable', 'integer', 'min:1', 'gte:capacity_min'],
            'includes' => ['nullable', 'string'],
            'excludes' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image'],
            'removed_gallery_images' => ['nullable', 'array'],
            'removed_gallery_images.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $typeName = trim((string) ($validated['activity_type_name'] ?? ''));
        $type = $this->resolveOrCreateActivityType($typeName);

        $validated['activity_type_id'] = (int) $type->id;
        $validated['activity_type'] = (string) $type->name;
        unset($validated['activity_type_name']);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['adult_markup_type'] = (($validated['adult_markup_type'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
        $validated['child_markup_type'] = (($validated['child_markup_type'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
        $validated['adult_contract_rate'] = max(0, (float) ($validated['adult_contract_rate'] ?? 0));
        $validated['child_contract_rate'] = max(0, (float) ($validated['child_contract_rate'] ?? 0));
        $validated['adult_markup'] = max(0, (float) ($validated['adult_markup'] ?? 0));
        $validated['child_markup'] = max(0, (float) ($validated['child_markup'] ?? 0));

        // Persist service pricing in canonical IDR.
        $validated['adult_contract_rate'] = $this->displayCurrencyToIdr($validated['adult_contract_rate']);
        $validated['child_contract_rate'] = $this->displayCurrencyToIdr($validated['child_contract_rate']);
        if ($validated['adult_markup_type'] === 'fixed') {
            $validated['adult_markup'] = $this->displayCurrencyToIdr($validated['adult_markup']);
        }
        if ($validated['child_markup_type'] === 'fixed') {
            $validated['child_markup'] = $this->displayCurrencyToIdr($validated['child_markup']);
        }

        if ($validated['adult_markup_type'] === 'percent' && $validated['adult_markup'] > 100) {
            throw ValidationException::withMessages([
                'adult_markup' => 'Adult markup percent cannot be greater than 100.',
            ]);
        }
        if ($validated['child_markup_type'] === 'percent' && $validated['child_markup'] > 100) {
            throw ValidationException::withMessages([
                'child_markup' => 'Child markup percent cannot be greater than 100.',
            ]);
        }

        $validated['adult_publish_rate'] = round($this->calculatePublishRate(
            $validated['adult_contract_rate'],
            $validated['adult_markup_type'],
            $validated['adult_markup']
        ), 0);
        $validated['child_publish_rate'] = round($this->calculatePublishRate(
            $validated['child_contract_rate'],
            $validated['child_markup_type'],
            $validated['child_markup']
        ), 0);
        $validated['adult_contract_rate'] = round($validated['adult_contract_rate'], 0);
        $validated['child_contract_rate'] = round($validated['child_contract_rate'], 0);
        $validated['adult_markup'] = round($validated['adult_markup'], 0);
        $validated['child_markup'] = round($validated['child_markup'], 0);

        // Keep backward compatibility for flows that still read `contract_price`.
        $validated['adult_contract_rate'] = $validated['adult_contract_rate'] ?? null;
        $validated['contract_price'] = $validated['adult_contract_rate'];

        return $validated;
    }

    private function calculatePublishRate(float $contractRate, string $markupType, float $markup): float
    {
        $base = max(0, $contractRate);
        $value = max(0, $markup);

        if ($markupType === 'percent') {
            return $base + ($base * ($value / 100));
        }

        return $base + $value;
    }

    private function wantsAjaxFragment(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Activities-Ajax') === '1';
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function buildTypeFilterOptions(): array
    {
        $types = ActivityType::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();

        return array_map(function (ActivityType $type): array {
            return [
                'value' => (string) $type->id,
                'label' => (string) $type->name,
            ];
        }, $types);
    }

    private function resolveOrCreateActivityType(string $name): ActivityType
    {
        $normalizedName = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($normalizedName === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'activity_type_new' => 'New activity type name cannot be empty.',
            ]);
        }

        $existing = ActivityType::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }
            return $existing;
        }

        $baseSlug = Str::slug($normalizedName);
        if ($baseSlug === '') {
            $baseSlug = 'activity-type';
        }
        $slug = $baseSlug;
        $counter = 2;
        while (ActivityType::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return ActivityType::query()->create([
            'name' => $normalizedName,
            'slug' => $slug,
            'is_active' => true,
        ]);
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


