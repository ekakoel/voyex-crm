<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\NormalizesDisplayCurrencyToIdr;
use App\Http\Controllers\Concerns\ManagesServiceCancellationPolicy;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Vendor;
use App\Support\Currency;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FoodBeverageController extends Controller
{
    use NormalizesDisplayCurrencyToIdr;
    use ManagesServiceCancellationPolicy;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $query = FoodBeverage::query()->withTrashed()->with('vendor:id,name,latitude,longitude,destination_id')->latest('id');

        $search = trim((string) $request->string('q'));
        if (mb_strlen($search) >= 3) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vendorQ) => $vendorQ->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->integer('vendor_id'));
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', (string) $request->string('service_type'));
        }
        $query->when(($validated['status'] ?? null) === 'active', fn ($q) => $q->whereNull('deleted_at'));
        $query->when(($validated['status'] ?? null) === 'inactive', fn ($q) => $q->onlyTrashed());

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $foodBeverages = $query->paginate($perPage)->withQueryString();
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'province']);
        $types = $this->buildTypeFilterOptions();
        $foodBeverageRows = $this->buildFoodBeverageIndexRows($foodBeverages);
        $perPageOptions = [10, 25, 50, 100];
        $canManageActivationActions = auth()->user()?->canManageActivationActions() === true;

        return view('modules.food-beverages.index', compact(
            'foodBeverages',
            'vendors',
            'types',
            'foodBeverageRows',
            'perPageOptions',
            'canManageActivationActions'
        ));
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
                ->with('vendor:id,destination_id')
                ->withTrashed()
                ->find($copyId);

            if ($copiedFrom) {
                $prefill = [
                    'destination_filter_id' => (int) ($copiedFrom->vendor?->destination_id ?? 0),
                    'vendor_id' => $copiedFrom->vendor_id,
                    'name' => trim(((string) $copiedFrom->name) . ' (Copy)'),
                    'service_type' => $copiedFrom->service_type,
                    'duration_minutes' => $copiedFrom->duration_minutes,
                    'meal_period' => $copiedFrom->meal_period,
                    'adult_contract_rate' => $copiedFrom->adult_contract_rate ?? $copiedFrom->contract_rate,
                    'adult_markup_type' => $copiedFrom->adult_markup_type ?? $copiedFrom->markup_type ?? 'fixed',
                    'adult_markup' => $copiedFrom->adult_markup ?? $copiedFrom->markup ?? max(0, (float) (($copiedFrom->adult_publish_rate ?? $copiedFrom->publish_rate ?? 0) - ($copiedFrom->adult_contract_rate ?? $copiedFrom->contract_rate ?? 0))),
                    'adult_publish_rate' => $copiedFrom->adult_publish_rate ?? $copiedFrom->publish_rate,
                    'child_contract_rate' => $copiedFrom->child_contract_rate ?? 0,
                    'child_markup_type' => $copiedFrom->child_markup_type ?? 'fixed',
                    'child_markup' => $copiedFrom->child_markup ?? max(0, (float) (($copiedFrom->child_publish_rate ?? 0) - ($copiedFrom->child_contract_rate ?? 0))),
                    'child_publish_rate' => $copiedFrom->child_publish_rate ?? 0,
                    'menu_highlights' => $copiedFrom->menu_highlights,
                    'notes' => $copiedFrom->notes,
                    'is_active' => (bool) $copiedFrom->is_active,
                ];
            }
        }

        $cancellationPolicyRules = [];
        return view('modules.food-beverages.create', compact('vendors', 'destinations', 'serviceTypes', 'standardServiceTypes', 'prefill', 'copiedFrom', 'cancellationPolicyRules'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);
        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'food-beverages');
        $foodBeverage = FoodBeverage::query()->create($validated);
        $this->syncCancellationPolicy($foodBeverage, $request->input('cancellation_rules', []), (string) ($foodBeverage->name ?? ''));

        return redirect()->route('food-beverages.index')->with('success', ui_phrase('F&B service created successfully.'));
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

        $cancellationPolicyRules = $this->resolveCancellationPolicyRules($foodBeverage);
        return view('modules.food-beverages.edit', compact('foodBeverage', 'vendors', 'destinations', 'serviceTypes', 'standardServiceTypes', 'cancellationPolicyRules'));
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
        $this->syncCancellationPolicy($foodBeverage, $request->input('cancellation_rules', []), (string) ($foodBeverage->name ?? ''));

        return redirect()->route('food-beverages.index')->with('success', ui_phrase('F&B service updated successfully.'));
    }

    public function destroy(FoodBeverage $foodBeverage)
    {
        $this->deleteGalleryImages($foodBeverage->gallery_images ?? []);
        $foodBeverage->delete();

        return redirect()->route('food-beverages.index')->with('success', ui_phrase('F&B service deactivated successfully.'));
    }

    public function toggleStatus($foodBeverage)
    {
        abort_unless(auth()->user()?->canManageActivationActions(), 403);
        $foodBeverage = FoodBeverage::withTrashed()->findOrFail($foodBeverage);
        if ($foodBeverage->trashed()) {
            $foodBeverage->restore();
            $foodBeverage->update(['is_active' => true]);

            return redirect()
                ->route('food-beverages.index')
                ->with('success', ui_phrase('F&B service activated successfully.'));
        }

        $foodBeverage->update(['is_active' => false]);
        $foodBeverage->delete();

        return redirect()
            ->route('food-beverages.index')
            ->with('success', ui_phrase('F&B service deactivated successfully.'));
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
        if (! $request->has('adult_contract_rate') && $request->has('contract_rate')) {
            $request->merge(['adult_contract_rate' => $request->input('contract_rate')]);
        }
        if (! $request->has('adult_markup_type') && $request->has('markup_type')) {
            $request->merge(['adult_markup_type' => $request->input('markup_type')]);
        }
        if (! $request->has('adult_markup') && $request->has('markup')) {
            $request->merge(['adult_markup' => $request->input('markup')]);
        }
        if (! $request->has('adult_publish_rate') && $request->has('publish_rate')) {
            $request->merge(['adult_publish_rate' => $request->input('publish_rate')]);
        }
        if (! $request->has('meal_periods') && $request->has('meal_period')) {
            $request->merge([
                'meal_periods' => $this->normalizeMealPeriodSelections($request->input('meal_period')),
            ]);
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
            'adult_contract_rate' => ['nullable', 'numeric', 'min:0'],
            'child_contract_rate' => ['nullable', 'numeric', 'min:0'],
            'adult_markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'adult_markup' => ['nullable', 'numeric', 'min:0'],
            'child_markup_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'child_markup' => ['nullable', 'numeric', 'min:0'],
            'adult_publish_rate' => ['nullable', 'numeric', 'min:0'],
            'child_publish_rate' => ['nullable', 'numeric', 'min:0'],
            'meal_periods' => ['nullable', 'array'],
            'meal_periods.*' => ['string', Rule::in(FoodBeverage::mealPeriodKeys())],
            'menu_highlights' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image'],
            'removed_gallery_images' => ['nullable', 'array'],
            'removed_gallery_images.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['adult_markup_type'] = (($validated['adult_markup_type'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
        $validated['child_markup_type'] = (($validated['child_markup_type'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
        $validated['adult_contract_rate'] = max(0, (float) ($validated['adult_contract_rate'] ?? 0));
        $validated['child_contract_rate'] = max(0, (float) ($validated['child_contract_rate'] ?? 0));
        $validated['adult_markup'] = max(0, (float) ($validated['adult_markup'] ?? 0));
        $validated['child_markup'] = max(0, (float) ($validated['child_markup'] ?? 0));
        $validated['meal_period'] = $this->formatMealPeriodForStorage($validated['meal_periods'] ?? []);
        unset($validated['meal_periods']);

        // Persist service master pricing in canonical IDR.
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

        // Backward compatibility: keep legacy single-rate columns aligned with adult pricing.
        $validated['contract_rate'] = $validated['adult_contract_rate'];
        $validated['markup_type'] = $validated['adult_markup_type'];
        $validated['markup'] = $validated['adult_markup'];
        $validated['publish_rate'] = $validated['adult_publish_rate'];

        return $validated;
    }

    private function buildFoodBeverageIndexRows($foodBeverages): array
    {
        return $foodBeverages->getCollection()->values()->map(function (FoodBeverage $foodBeverage, int $index) use ($foodBeverages): array {
            $galleryImages = is_array($foodBeverage->gallery_images ?? null) ? $foodBeverage->gallery_images : [];
            $hasGalleryImages = count($galleryImages) > 0;
            $hasDestination = (int) ($foodBeverage->vendor?->destination_id ?? 0) > 0;
            $hasServiceName = trim((string) ($foodBeverage->name ?? '')) !== '';
            $hasServiceType = trim((string) ($foodBeverage->service_type ?? '')) !== '';
            $hasActivityType = trim((string) ($foodBeverage->activity_type ?? $foodBeverage->service_type ?? '')) !== '';
            $isActive = ! $foodBeverage->trashed();

            return [
                'food_beverage' => $foodBeverage,
                'row_number' => (int) ($foodBeverages->firstItem() ?? 1) + $index,
                'is_active' => $isActive,
                'vendor_name' => trim((string) ($foodBeverage->vendor?->name ?? '')) !== ''
                    ? (string) $foodBeverage->vendor->name
                    : '-',
                'service_type_label' => ucwords(str_replace('_', ' ', (string) ($foodBeverage->service_type ?? ''))),
                'duration_label' => (int) ($foodBeverage->duration_minutes ?? 0) . ' min',
                'meal_sessions' => $this->resolveMealSessionBadges($foodBeverage->meal_period),
                'needs_data_attention' => ! $hasGalleryImages || ! $hasDestination || ! $hasServiceName || ! $hasServiceType || ! $hasActivityType,
                'adult_contract_rate' => (float) ($foodBeverage->adult_contract_rate ?? $foodBeverage->contract_rate ?? 0),
                'adult_markup_display' => $this->formatMarkupDisplay(
                    (string) ($foodBeverage->adult_markup_type ?? $foodBeverage->markup_type ?? 'fixed'),
                    (float) ($foodBeverage->adult_markup ?? $foodBeverage->markup ?? 0)
                ),
                'adult_publish_rate' => (float) ($foodBeverage->adult_publish_rate ?? $foodBeverage->publish_rate ?? 0),
                'child_contract_rate' => (float) ($foodBeverage->child_contract_rate ?? 0),
                'child_markup_display' => $this->formatMarkupDisplay(
                    (string) ($foodBeverage->child_markup_type ?? 'fixed'),
                    (float) ($foodBeverage->child_markup ?? 0)
                ),
                'child_publish_rate' => (float) ($foodBeverage->child_publish_rate ?? 0),
                'edit_url' => route('food-beverages.edit', $foodBeverage),
                'copy_url' => route('food-beverages.create', ['copy' => $foodBeverage->id]),
                'toggle_url' => route('food-beverages.toggle-status', $foodBeverage->id),
                'toggle_title' => $isActive ? ui_phrase('Deactivate') . ' F&B' : ui_phrase('Activate') . ' F&B',
                'toggle_message_desktop' => $isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate'),
                'toggle_message_mobile' => $isActive ? ui_phrase('Deactivate this F&B service?') : ui_phrase('Activate this F&B service?'),
                'toggle_label' => $isActive ? ui_phrase('Deactivate') : ui_phrase('Activate'),
                'toggle_icon' => $isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4',
                'toggle_class' => $isActive
                    ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20'
                    : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20',
            ];
        })->all();
    }

    private function resolveMealSessionBadges(?string $mealPeriod): array
    {
        $tokens = FoodBeverage::normalizeMealPeriodTokens($mealPeriod);

        $sessions = [];
        foreach (FoodBeverage::mealPeriodOptions() as $key => $label) {
            if (in_array($key, $tokens, true)) {
                $sessions[] = ['key' => $key, 'label' => $label];
            }
        }

        return $sessions;
    }

    private function formatMarkupDisplay(string $markupType, float $markup): string
    {
        if ($markupType === 'percent') {
            return rtrim(rtrim(number_format($markup, 2, '.', ''), '0'), '.') . '%';
        }

        return Currency::format($markup, 'IDR');
    }

    /**
     * @param  mixed  $source
     * @return array<int, string>
     */
    private function normalizeMealPeriodSelections($source): array
    {
        return FoodBeverage::normalizeMealPeriodTokens($source);
    }

    /**
     * @param  array<int, string>  $selections
     */
    private function formatMealPeriodForStorage(array $selections): ?string
    {
        return FoodBeverage::formatMealPeriodForStorage($selections);
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
