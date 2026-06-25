<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\NormalizesDisplayCurrencyToIdr;
use App\Http\Controllers\Concerns\ManagesServiceCancellationPolicy;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Destination;
use App\Models\Vendor;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    use NormalizesDisplayCurrencyToIdr;
    use ManagesServiceCancellationPolicy;

    public function index(Request $request)
    {
        $perPageOptions = [10, 25, 50, 100];
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'vendor_id' => ['nullable', 'integer', 'min:1'],
            'activity_type_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', Rule::in(array_map('strval', $perPageOptions))],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $vendorId = (int) ($validated['vendor_id'] ?? 0);
        $activityTypeId = (int) ($validated['activity_type_id'] ?? 0);
        $status = (string) ($validated['status'] ?? '');
        $perPage = (int) ($validated['per_page'] ?? 10);
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 10;

        $query = $this->buildIndexQuery($search, $vendorId, $activityTypeId, $status);
        $activities = $query->paginate($perPage)->withQueryString();
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $types = $this->buildTypeFilterOptions();
        $activityRows = $this->buildActivityIndexRows($activities);
        $canManageActivationActions = auth()->user()?->canManageActivationActions() === true;
        $statsCards = [
            [
                'key' => 'total',
                'label' => ui_phrase('Total Activities'),
                'value' => (clone $query)->count(),
                'caption' => ui_phrase('Current filter result'),
            ],
            [
                'key' => 'active',
                'label' => ui_phrase('Active Activities'),
                'value' => (clone $query)->whereNull('deleted_at')->count(),
                'caption' => ui_phrase('Active in current result'),
            ],
            [
                'key' => 'vendors',
                'label' => ui_phrase('Vendors'),
                'value' => (clone $query)
                    ->whereNotNull('vendor_id')
                    ->distinct('vendor_id')
                    ->count('vendor_id'),
                'caption' => ui_phrase('Vendors in current result'),
            ],
        ];

        if ($request->header('X-Activities-Ajax') === '1' || $request->expectsJson()) {
            return response()->json([
                'html' => view('modules.activities.partials._index-results', compact(
                    'activities',
                    'activityRows',
                    'canManageActivationActions'
                ))->render(),
                'url' => route('activities.index', $request->query()),
            ]);
        }

        if ($this->wantsAjaxFragment($request)) {
            return view('modules.activities.index', compact(
                'activities',
                'activityRows',
                'vendors',
                'types',
                'statsCards',
                'perPageOptions',
                'canManageActivationActions'
            ));
        }

        return view('modules.activities.index', compact(
            'activities',
            'activityRows',
            'vendors',
            'types',
            'statsCards',
            'perPageOptions',
            'canManageActivationActions'
        ));
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

        $cancellationPolicyRules = [];
        return view('modules.activities.create', compact('vendors', 'destinations', 'activityTypes', 'cancellationPolicyRules'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);
        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'activities');
        $activity = Activity::query()->create($validated);
        $this->syncCancellationPolicy($activity, $request->input('cancellation_rules', []), (string) ($activity->name ?? ''));

        return redirect()->route('activities.index')->with('success', ui_phrase('Activity created successfully.'));
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

        $cancellationPolicyRules = $this->resolveCancellationPolicyRules($activity);
        return view('modules.activities.edit', compact('activity', 'vendors', 'destinations', 'activityTypes', 'cancellationPolicyRules'));
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
        $this->syncCancellationPolicy($activity, $request->input('cancellation_rules', []), (string) ($activity->name ?? ''));

        return redirect()->route('activities.index')->with('success', ui_phrase('Activity updated successfully.'));
    }

    public function destroy(Activity $activity)
    {
        $this->deleteGalleryImages($activity->gallery_images ?? []);
        $activity->delete();

        return redirect()->route('activities.index')->with('success', ui_phrase('Activity deactivated successfully.'));
    }

    public function toggleStatus($activity)
    {
        abort_unless(auth()->user()?->canManageActivationActions(), 403);
        $activity = Activity::withTrashed()->findOrFail($activity);
        if ($activity->trashed()) {
            $activity->restore();
            $activity->update(['is_active' => true]);

            return redirect()
                ->route('activities.index')
                ->with('success', ui_phrase('Activity activated successfully.'));
        }

        $activity->update(['is_active' => false]);
        $activity->delete();

        return redirect()
            ->route('activities.index')
            ->with('success', ui_phrase('Activity deactivated successfully.'));
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

    private function buildIndexQuery(string $search, int $vendorId, int $activityTypeId, string $status)
    {
        return Activity::query()
            ->withTrashed()
            ->with([
                'vendor:id,name,latitude,longitude,destination_id',
                'activityType:id,name',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                if (mb_strlen($search) < 3) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('activity_type', 'like', "%{$search}%")
                        ->orWhereHas('activityType', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($vendorId > 0, fn ($query) => $query->where('vendor_id', $vendorId))
            ->when($activityTypeId > 0, fn ($query) => $query->where('activity_type_id', $activityTypeId))
            ->when($status === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($status === 'inactive', fn ($query) => $query->onlyTrashed())
            ->latest('id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildActivityIndexRows(LengthAwarePaginator $activities): array
    {
        return $activities->getCollection()
            ->values()
            ->map(function (Activity $activity, int $index) use ($activities): array {
                $isActive = ! $activity->trashed();
                $galleryImages = $this->normalizeGalleryImages($activity->gallery_images ?? []);
                $hasDestination = (int) ($activity->vendor?->destination_id ?? 0) > 0;
                $typeLabel = trim((string) ($activity->activityType?->name ?? $activity->activity_type ?? ''));
                $hasActivityType = (int) ($activity->activity_type_id ?? 0) > 0 || $typeLabel !== '';

                return [
                    'activity' => $activity,
                    'row_number' => $activities->firstItem() !== null ? $activities->firstItem() + $index : $index + 1,
                    'name' => (string) ($activity->name ?? '-'),
                    'vendor_name' => trim((string) ($activity->vendor?->name ?? '')) !== '' ? (string) $activity->vendor->name : '-',
                    'type_label' => $typeLabel !== '' ? $typeLabel : '-',
                    'duration_label' => ((int) ($activity->duration_minutes ?? 0)) . ' min',
                    'is_active' => $isActive,
                    'status' => $isActive ? 'active' : 'inactive',
                    'needs_data_attention' => count($galleryImages) === 0 || ! $hasDestination || ! $hasActivityType,
                    'rate_lines' => $this->buildActivityRateLines($activity),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{label:string,value:float,is_money:bool}>
     */
    private function buildActivityRateLines(Activity $activity): array
    {
        $lines = [];

        if ($activity->adult_contract_rate !== null) {
            $lines[] = [
                'label' => 'ACR',
                'value' => (float) $activity->adult_contract_rate,
                'is_money' => true,
            ];
        }

        $lines[] = [
            'label' => 'AM',
            'value' => (float) ($activity->adult_markup ?? 0),
            'is_money' => (string) ($activity->adult_markup_type ?? 'fixed') !== 'percent',
        ];

        if ($activity->adult_publish_rate !== null) {
            $lines[] = [
                'label' => 'APR',
                'value' => (float) $activity->adult_publish_rate,
                'is_money' => true,
            ];
        }

        if ($activity->child_contract_rate !== null) {
            $lines[] = [
                'label' => 'CCR',
                'value' => (float) $activity->child_contract_rate,
                'is_money' => true,
            ];
        }

        $lines[] = [
            'label' => 'CM',
            'value' => (float) ($activity->child_markup ?? 0),
            'is_money' => (string) ($activity->child_markup_type ?? 'fixed') !== 'percent',
        ];

        if ($activity->child_publish_rate !== null) {
            $lines[] = [
                'label' => 'CPR',
                'value' => (float) $activity->child_publish_rate,
                'is_money' => true,
            ];
        }

        return $lines;
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
