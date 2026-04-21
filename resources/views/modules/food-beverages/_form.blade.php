@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $foodBeverage = $foodBeverage ?? null;
    $prefill = $prefill ?? [];
    $destinations = $destinations ?? collect();
    $serviceTypes = $serviceTypes ?? [];
    $standardServiceTypes = $standardServiceTypes ?? [];
    $selectedServiceType = (string) old('service_type', $foodBeverage->service_type ?? ($prefill['service_type'] ?? ''));
    $isLegacyServiceType = $selectedServiceType !== '' && ! in_array($selectedServiceType, $standardServiceTypes, true);
    $selectedDestinationId = (int) old('destination_filter_id', $foodBeverage->vendor->destination_id ?? 0);
    $defaultMarkupType = old('markup_type', $foodBeverage->markup_type ?? ($prefill['markup_type'] ?? 'fixed'));
    $activeCurrencyCode = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
    $toDisplayMoney = static function ($amount) use ($activeCurrencyCode): float {
        return round(\App\Support\Currency::convert((float) ($amount ?? 0), 'IDR', $activeCurrencyCode), 0);
    };
    $defaultMarkup = old('markup');
    $existingGalleryImages = is_array($foodBeverage->gallery_images ?? null) ? $foodBeverage->gallery_images : [];
    if ($defaultMarkup === null) {
        $defaultMarkup = $foodBeverage->markup ?? ($prefill['markup'] ?? null);
    }
    if ($defaultMarkup === null) {
        $defaultMarkup = max(0, (float) (($foodBeverage->publish_rate ?? ($prefill['publish_rate'] ?? 0)) - ($foodBeverage->contract_rate ?? ($prefill['contract_rate'] ?? 0))));
    }
    if (old('markup') === null && $defaultMarkupType !== 'percent') {
        $defaultMarkup = $toDisplayMoney($defaultMarkup);
    }
    $contractRateValue = old('contract_rate');
    if ($contractRateValue === null) {
        $contractRateValue = $toDisplayMoney($foodBeverage->contract_rate ?? ($prefill['contract_rate'] ?? ($prefill['contract_price'] ?? 0)));
    }
    $publishRateValue = old('publish_rate');
    if ($publishRateValue === null) {
        $publishRateValue = $toDisplayMoney($foodBeverage->publish_rate ?? ($prefill['publish_rate'] ?? ($prefill['agent_price'] ?? 0)));
    }
    $mealPeriodSource = old('meal_periods');
    if ($mealPeriodSource === null) {
        $mealPeriodSource = $foodBeverage->meal_period ?? ($prefill['meal_period'] ?? '');
    }
    if (is_array($mealPeriodSource)) {
        $selectedMealPeriods = array_values(array_filter(array_map(
            static fn ($item) => strtolower(trim((string) $item)),
            $mealPeriodSource
        )));
    } else {
        $selectedMealPeriods = array_values(array_filter(array_map(
            static fn ($item) => strtolower(trim((string) $item)),
            preg_split('/[\s,;\/|]+/', (string) $mealPeriodSource) ?: []
        )));
    }
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images</label>
            <div id="food-beverage-gallery-preview"
                class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                data-remove-endpoint-template="{{ isset($foodBeverage) ? route('food-beverages.gallery-images.remove', $foodBeverage) : '' }}"
                data-csrf-token="{{ csrf_token() }}">
                @forelse ($existingGalleryImages as $image)
                    @php($thumbUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($image))
                    @php($fullUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($image))
                    <div class="food-beverage-gallery-item food-beverage-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                        <button
                            type="button"
                            class="food-beverage-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                            title="Remove image"
                            aria-label="Remove image">
                            X
                        </button>
                        <div class="room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border-0 bg-gray-50 dark:bg-gray-800/40">
                            <div class="image-preview-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>Select image to preview</span>
                            </div>
                            @if ($thumbUrl)
                                <img
                                    src="{{ $thumbUrl }}"
                                    onload="this.classList.add('image-loaded');var p=this.closest('.image-preview');if(p){p.classList.add('has-image');}"
                                    onerror="if(this.dataset.fallbackApplied){var p=this.closest('.image-preview');if(p){p.classList.remove('has-image');}this.remove();}else{this.dataset.fallbackApplied='1';this.src='{{ $fullUrl ?? '' }}';}"
                                    alt="F&B gallery"
                                    class="h-full w-full object-cover">
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="food-beverage-gallery-empty">
                        <div class="room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40">
                            <div class="image-preview-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>Select image to preview</span>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
            <input id="food-beverage-gallery-input" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('removed_gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Destination (Filter Vendor)</label>
            <select id="food-beverage-destination-filter" name="destination_filter_id" class="mt-1 dark:border-gray-600 app-input">
                <option value="">All destinations</option>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}" @selected($selectedDestinationId === (int) $destination->id)>
                        {{ $destination->name ?: ($destination->province ?: 'Destination') }}
                        @if ($destination->city || $destination->province)
                            ({{ trim(($destination->city ?? '-') . ' / ' . ($destination->province ?? '-')) }})
                        @endif
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Filter-only, tidak disimpan ke database service.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Vendor</label>
            <select id="food-beverage-vendor-select" name="vendor_id" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">Select vendor</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}"
                        data-destination-id="{{ (int) ($vendor->destination_id ?? 0) }}"
                        @selected((int) old('vendor_id', $foodBeverage->vendor_id ?? ($prefill['vendor_id'] ?? 0)) === (int) $vendor->id)>
                        {{ $vendor->name }}{{ ($vendor->city || $vendor->province) ? ' ('.trim(($vendor->city ?? '-').' / '.($vendor->province ?? '-')).')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('vendor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Service Name</label>
            <input name="name" value="{{ old('name', $foodBeverage->name ?? ($prefill['name'] ?? '')) }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Service Type</label>
            <select name="service_type" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">Select service type</option>
                @foreach ($serviceTypes as $serviceType)
                    <option value="{{ $serviceType }}" @selected($selectedServiceType === $serviceType)>
                        {{ ucwords(str_replace('_', ' ', $serviceType)) }}{{ ! in_array($serviceType, $standardServiceTypes, true) ? ' (Legacy)' : '' }}
                    </option>
                @endforeach
            </select>
            @if ($isLegacyServiceType)
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    Legacy type detected: "{{ ucwords(str_replace('_', ' ', $selectedServiceType)) }}". Please switch to a standard type if possible.
                </p>
            @endif
            @error('service_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Duration (minutes)</label>
            <input name="duration_minutes" type="number" min="15" max="1440" value="{{ old('duration_minutes', $foodBeverage->duration_minutes ?? ($prefill['duration_minutes'] ?? 60)) }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('duration_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Meal Period</label>
            <div class="mt-2 grid w-full grid-cols-3 gap-3">
                @foreach (['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'] as $value => $label)
                    <label class="inline-flex w-full items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" name="meal_periods[]" value="{{ $value }}" class="rounded border-gray-300 text-indigo-600"
                            @checked(in_array($value, $selectedMealPeriods, true))>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('meal_periods') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('meal_periods.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <x-money-input
                label="Contract Rate (per pax)"
                name="contract_rate"
                :value="$contractRateValue"
                id="food-beverage-contract-rate"
                min="0"
                step="1"
            />
            @error('contract_rate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Markup Type</label>
            <select name="markup_type" id="food-beverage-markup-type" class="mt-1 dark:border-gray-600 app-input">
                <option value="fixed" @selected($defaultMarkupType === 'fixed')>Fixed</option>
                <option value="percent" @selected($defaultMarkupType === 'percent')>Percent</option>
            </select>
            @error('markup_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <x-money-input
                label="Markup (per pax)"
                name="markup"
                :value="$defaultMarkup"
                id="food-beverage-markup"
                min="0"
                step="1"
            />
            @error('markup') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <x-money-input
                label="Publish Rate (Auto / per pax)"
                name="publish_rate"
                id="food-beverage-publish-rate"
                :value="$publishRateValue"
                min="0"
                step="1"
                readonly
            />
            @error('publish_rate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Menu Highlights</label>
        <textarea name="menu_highlights" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('menu_highlights', $foodBeverage->menu_highlights ?? ($prefill['menu_highlights'] ?? '')) }}</textarea>
        @error('menu_highlights') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $foodBeverage->notes ?? ($prefill['notes'] ?? '')) }}</textarea>
        @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $foodBeverage->is_active ?? ($prefill['is_active'] ?? true)))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button  class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('food-beverages.index') }}"  class="btn-secondary">Cancel</a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const destinationFilter = document.getElementById('food-beverage-destination-filter');
                const vendorSelect = document.getElementById('food-beverage-vendor-select');
                const input = document.getElementById('food-beverage-gallery-input');
                const preview = document.getElementById('food-beverage-gallery-preview');
                const contractRateInput = document.getElementById('food-beverage-contract-rate');
                const markupTypeSelect = document.getElementById('food-beverage-markup-type');
                const markupInput = document.getElementById('food-beverage-markup');
                const publishRateInput = document.getElementById('food-beverage-publish-rate');
                if (!input || !preview) return;

                const parseMoney = (value) => {
                    const raw = String(value ?? '').trim();
                    if (raw === '') return 0;

                    if (/^\d+([.,]\d{1,2})?$/.test(raw) && !raw.includes(' ')) {
                        const numeric = Number(raw.replace(',', '.'));
                        return Number.isFinite(numeric) ? Math.round(numeric) : 0;
                    }

                    const digits = raw.replace(/[^\d]/g, '');
                    if (digits === '') return 0;
                    const numeric = Number(digits);
                    return Number.isFinite(numeric) ? numeric : 0;
                };

                const syncMarkupBadge = () => {
                    if (!markupInput || !markupTypeSelect) return;
                    const badge = markupInput.parentElement?.querySelector('[data-money-badge="1"]');
                    if (!badge) return;
                    badge.textContent = markupTypeSelect.value === 'percent'
                        ? '%'
                        : (window.appCurrencySymbol || window.appCurrency || 'IDR');
                };

                const recalcPublishRate = () => {
                    if (!publishRateInput || !contractRateInput || !markupInput || !markupTypeSelect) return;

                    const contractRate = parseMoney(contractRateInput.value);
                    let markupValue = parseMoney(markupInput.value);
                    const markupType = markupTypeSelect.value === 'percent' ? 'percent' : 'fixed';

                    if (markupType === 'percent' && markupValue > 100) {
                        markupValue = 100;
                        markupInput.value = '100';
                    }

                    const publishRate = markupType === 'percent'
                        ? contractRate + (contractRate * (markupValue / 100))
                        : contractRate + markupValue;

                    publishRateInput.value = String(Math.max(0, Math.round(publishRate)));
                    syncMarkupBadge();
                };

                const applyVendorFilter = () => {
                    if (!destinationFilter || !vendorSelect) return;

                    const selectedDestination = String(destinationFilter.value || '').trim();
                    const currentVendor = String(vendorSelect.value || '');
                    let hasCurrentVendorVisible = false;

                    Array.from(vendorSelect.options).forEach((option, index) => {
                        if (index === 0) {
                            option.hidden = false;
                            return;
                        }

                        const optionDestination = String(option.dataset.destinationId || '').trim();
                        const shouldShow = selectedDestination === '' || optionDestination === selectedDestination;
                        option.hidden = !shouldShow;

                        if (shouldShow && option.value === currentVendor) {
                            hasCurrentVendorVisible = true;
                        }
                    });

                    if (currentVendor !== '' && !hasCurrentVendorVisible) {
                        vendorSelect.value = '';
                    }
                };

                const buildPreviewPlaceholder = () => `
                    <div class="image-preview-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                        <span>Select image to preview</span>
                    </div>
                `;

                const ensureEmptyState = () => {
                    const hasItems = preview.querySelector('.food-beverage-gallery-item');
                    const empty = preview.querySelector('.food-beverage-gallery-empty');
                    if (!hasItems && !empty) {
                        const node = document.createElement('div');
                        node.className = 'food-beverage-gallery-empty';
                        node.innerHTML = `
                            <div class="room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40">
                                ${buildPreviewPlaceholder()}
                            </div>
                        `;
                        preview.appendChild(node);
                    }
                    if (hasItems && empty) {
                        empty.remove();
                    }
                };

                const renderNewUploads = () => {
                    preview.querySelectorAll('.food-beverage-gallery-new-item').forEach((node) => node.remove());

                    const files = Array.from(input.files || []);
                    files.forEach((file) => {
                        if (!String(file.type || '').startsWith('image/')) return;
                        const url = URL.createObjectURL(file);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'food-beverage-gallery-item food-beverage-gallery-new-item relative overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
                        const media = document.createElement('div');
                        media.className = 'room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border-0 bg-gray-50 dark:bg-gray-800/40';
                        media.innerHTML = buildPreviewPlaceholder();
                        const image = document.createElement('img');
                        image.alt = 'F&B gallery preview';
                        image.className = 'h-full w-full object-cover';
                        image.addEventListener('load', () => {
                            image.classList.add('image-loaded');
                            media.classList.add('has-image');
                            URL.revokeObjectURL(url);
                        }, { once: true });
                        image.addEventListener('error', () => {
                            media.classList.remove('has-image');
                            image.remove();
                        }, { once: true });
                        image.src = url;
                        media.appendChild(image);
                        wrapper.appendChild(media);
                        const badge = document.createElement('div');
                        badge.className = 'border-t border-indigo-200 px-2 py-1 text-[11px] font-medium text-indigo-700 dark:border-indigo-700/60 dark:text-indigo-300';
                        badge.textContent = 'New upload';
                        wrapper.appendChild(badge);
                        preview.appendChild(wrapper);
                    });

                    ensureEmptyState();
                };

                input.addEventListener('change', renderNewUploads);
                destinationFilter?.addEventListener('change', applyVendorFilter);
                contractRateInput?.addEventListener('input', recalcPublishRate);
                markupInput?.addEventListener('input', recalcPublishRate);
                markupTypeSelect?.addEventListener('change', recalcPublishRate);

                preview.addEventListener('click', async (event) => {
                    const button = event.target.closest('.food-beverage-gallery-remove-btn');
                    if (!button) return;
                    const wrapper = button.closest('.food-beverage-gallery-existing-item');
                    const imagePath = String(wrapper?.dataset.imagePath || '');
                    if (!wrapper || imagePath === '') return;

                    const endpoint = String(preview.dataset.removeEndpointTemplate || '');
                    const csrfToken = String(preview.dataset.csrfToken || '');
                    if (endpoint === '' || csrfToken === '') {
                        wrapper.remove();
                        renderNewUploads();
                        return;
                    }

                    button.disabled = true;
                    button.classList.add('opacity-70');
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ image: imagePath }),
                        });
                        if (!response.ok) {
                            throw new Error('Request failed');
                        }
                        wrapper.remove();
                        renderNewUploads();
                        ensureEmptyState();
                    } catch (_) {
                        button.disabled = false;
                        button.classList.remove('opacity-70');
                        alert('Failed to delete image. Please try again.');
                    }
                });

                ensureEmptyState();
                applyVendorFilter();
                recalcPublishRate();
            })();
        </script>
    @endpush
@endonce
