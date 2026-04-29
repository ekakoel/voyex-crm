@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $transport = $transport ?? null;
    $vendors = $vendors ?? collect();
    $destinations = $destinations ?? collect();
    $selectedDestinationId = (int) old('destination_filter_id', $transport->vendor->destination_id ?? 0);

    $transportTypes = ['car', 'van', 'bus', 'boat', 'ferry', 'train', 'helicopter', 'other'];
    $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid', 'other'];
    $transmissions = ['manual', 'automatic'];
    $activeCurrencyCode = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
    $toDisplayMoney = static function ($amount) use ($activeCurrencyCode): float {
        return round(\App\Support\Currency::convert((float) ($amount ?? 0), 'IDR', $activeCurrencyCode), 0);
    };
    $defaultMarkupType = old('markup_type', $transport->markup_type ?? 'fixed');
    $defaultMarkup = old('markup');
    if ($defaultMarkup === null) {
        $defaultMarkup = $transport->markup ?? null;
    }
    if ($defaultMarkup === null) {
        $defaultMarkup = max(0, (float) (($transport->publish_rate ?? 0) - ($transport->contract_rate ?? 0)));
    }
    if (old('markup') === null && $defaultMarkupType !== 'percent') {
        $defaultMarkup = $toDisplayMoney($defaultMarkup);
    }
    $contractRateValue = old('contract_rate');
    if ($contractRateValue === null) {
        $contractRateValue = $toDisplayMoney($transport->contract_rate ?? 0);
    }
    $publishRateValue = old('publish_rate');
    if ($publishRateValue === null) {
        $publishRateValue = $toDisplayMoney($transport->publish_rate ?? 0);
    }
    $overtimeRateValue = old('overtime_rate');
    if ($overtimeRateValue === null) {
        $overtimeRateValue = $toDisplayMoney($transport->overtime_rate ?? 0);
    }
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Unit Images (max 2)') }}</label>
            <div id="transport-gallery-preview"
                class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2"
                data-remove-endpoint-template="{{ isset($transport) ? route('transports.gallery-images.remove', $transport) : '' }}"
                data-csrf-token="{{ csrf_token() }}">
                @if (!empty($transport?->images))
                    @foreach ($transport->images as $image)
                        @php($thumbUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($image))
                        @php($fullUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($image))
                        <div class="transport-gallery-item transport-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                            <button
                                type="button"
                                class="transport-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                                title="{{ ui_phrase('Remove image') }}"
                                aria-label="Remove image">
                                X
                            </button>
                            <div class="room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border-0 bg-gray-50 dark:bg-gray-800/40">
                                <div class="image-preview-placeholder">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                                        <circle cx="12" cy="13" r="4"></circle>
                                    </svg>
                                    <span>{{ ui_phrase('Select image to preview') }}</span>
                                </div>
                                @if ($thumbUrl)
                                    <img
                                        src="{{ $thumbUrl }}"
                                        onload="this.classList.add('image-loaded');var p=this.closest('.image-preview');if(p){p.classList.add('has-image');}"
                                        onerror="if(this.dataset.fallbackApplied){var p=this.closest('.image-preview');if(p){p.classList.remove('has-image');}this.remove();}else{this.dataset.fallbackApplied='1';this.src='{{ $fullUrl ?? '' }}';}"
                                        alt="Transport unit image"
                                        class="h-full w-full object-cover">
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="transport-gallery-empty">
                        <div class="room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40">
                            <div class="image-preview-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>{{ ui_phrase('Select image to preview') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <input id="transport-gallery-input" type="file" name="images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('removed_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Transport Unit Name') }}</label>
            <input name="name" value="{{ old('name', $transport->name ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Transport Type') }}</label>
            <select name="transport_type" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">{{ ui_phrase('Select type') }}</option>
                @foreach ($transportTypes as $type)
                    <option value="{{ $type }}" @selected(old('transport_type', $transport->transport_type ?? '') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                @endforeach
            </select>
            @error('transport_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Destination (Filter Vendor)') }}</label>
            <select id="transport-destination-filter" name="destination_filter_id" class="mt-1 dark:border-gray-600 app-input">
                <option value="">{{ ui_phrase('All destinations') }}</option>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}" @selected($selectedDestinationId === (int) $destination->id)>
                        {{ $destination->name ?: ($destination->province ?: 'Destination') }}
                        @if ($destination->city || $destination->province)
                            ({{ trim(($destination->city ?? '-') . ' / ' . ($destination->province ?? '-')) }})
                        @endif
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Filter only, not stored in transport database.') }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Vendor') }}</label>
            <select id="transport-vendor-select" name="vendor_id" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">{{ ui_phrase('Select vendor') }}</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}"
                        data-destination-id="{{ (int) ($vendor->destination_id ?? 0) }}"
                        @selected((string) old('vendor_id', $transport->vendor_id ?? '') === (string) $vendor->id)>
                        {{ $vendor->name }}{{ ($vendor->city || $vendor->province) ? ' ('.trim(($vendor->city ?? '-').' / '.($vendor->province ?? '-')).')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('vendor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Brand / Model') }}</label>
            <input name="brand_model" value="{{ old('brand_model', $transport->brand_model ?? '') }}" class="mt-1 dark:border-gray-600 app-input" placeholder="{{ ui_phrase('Toyota Innova') }}">
            @error('brand_model') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Seats') }}</label>
            <input name="seat_capacity" type="number" min="1" value="{{ old('seat_capacity', $transport->seat_capacity ?? 4) }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('seat_capacity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Luggage') }}</label>
            <input name="luggage_capacity" type="number" min="0" value="{{ old('luggage_capacity', $transport->luggage_capacity ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('luggage_capacity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <x-money-input
            label="Contract Rate"
            label-class="block text-sm font-medium text-gray-700 dark:text-gray-200"
            name="contract_rate"
            :value="$contractRateValue"
            id="transport-contract-rate"
            min="0"
            step="0.01"
            required
        />
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Markup Type') }}</label>
            <select name="markup_type" id="transport-markup-type" class="mt-1 dark:border-gray-600 app-input">
                <option value="fixed" @selected($defaultMarkupType === 'fixed')>{{ ui_phrase('Fixed') }}</option>
                <option value="percent" @selected($defaultMarkupType === 'percent')>{{ ui_phrase('Percent') }}</option>
            </select>
            @error('markup_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <x-money-input
            label="Markup"
            label-class="block text-sm font-medium text-gray-700 dark:text-gray-200"
            name="markup"
            :value="$defaultMarkup"
            id="transport-markup"
            min="0"
            step="0.01"
        />
        <x-money-input
            label="Publish Rate (Auto)"
            label-class="block text-sm font-medium text-gray-700 dark:text-gray-200"
            name="publish_rate"
            :value="$publishRateValue"
            id="transport-publish-rate"
            min="0"
            step="0.01"
            readonly
        />
        <x-money-input
            label="Overtime Rate"
            label-class="block text-sm font-medium text-gray-700 dark:text-gray-200"
            name="overtime_rate"
            :value="$overtimeRateValue"
            min="0"
            step="0.01"
        />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Fuel Type') }}</label>
            <select name="fuel_type" class="mt-1 dark:border-gray-600 app-input">
                <option value="">{{ ui_phrase('Select fuel') }}</option>
                @foreach ($fuelTypes as $fuelType)
                    <option value="{{ $fuelType }}" @selected(old('fuel_type', $transport->fuel_type ?? '') === $fuelType)>{{ ucfirst($fuelType) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Transmission') }}</label>
            <select name="transmission" class="mt-1 dark:border-gray-600 app-input">
                <option value="">{{ ui_phrase('Select transmission') }}</option>
                @foreach ($transmissions as $transmission)
                    <option value="{{ $transmission }}" @selected(old('transmission', $transport->transmission ?? '') === $transmission)>{{ ucfirst($transmission) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Air Conditioned') }}</label>
            <select name="air_conditioned" class="mt-1 dark:border-gray-600 app-input">
                <option value="1" @selected((string) old('air_conditioned', (int) ($transport->air_conditioned ?? true)) === '1')>{{ ui_phrase('Yes') }}</option>
                <option value="0" @selected((string) old('air_conditioned', (int) ($transport->air_conditioned ?? true)) === '0')>{{ ui_phrase('No') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('With Driver') }}</label>
            <select name="with_driver" class="mt-1 dark:border-gray-600 app-input">
                <option value="1" @selected((string) old('with_driver', (int) ($transport->with_driver ?? true)) === '1')>{{ ui_phrase('Yes') }}</option>
                <option value="0" @selected((string) old('with_driver', (int) ($transport->with_driver ?? true)) === '0')>{{ ui_phrase('No') }}</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Description') }}</label>
            <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $transport->description ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Notes') }}</label>
            <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $transport->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Inclusions') }}</label>
            <textarea name="inclusions" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('inclusions', $transport->inclusions ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Exclusions') }}</label>
            <textarea name="exclusions" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('exclusions', $transport->exclusions ?? '') }}</textarea>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Cancellation Policy') }}</label>
        <textarea name="cancellation_policy" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('cancellation_policy', $transport->cancellation_policy ?? '') }}</textarea>
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_active', $transport->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">{{ ui_phrase('Active') }}</span>
    </div>

    <div class="flex items-center gap-2">
        <button class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('transports.index') }}" class="btn-secondary">{{ ui_phrase('Cancel') }}</a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const destinationFilter = document.getElementById('transport-destination-filter');
                const vendorSelect = document.getElementById('transport-vendor-select');
                const input = document.getElementById('transport-gallery-input');
                const preview = document.getElementById('transport-gallery-preview');
                const contractRateInput = document.getElementById('transport-contract-rate');
                const markupTypeSelect = document.getElementById('transport-markup-type');
                const markupInput = document.getElementById('transport-markup');
                const publishRateInput = document.getElementById('transport-publish-rate');
                if (!input || !preview) return;

                const parseMoney = (value) => {
                    const raw = String(value ?? '').trim();
                    if (raw === '') return 0;

                    // Decimal style from backend (example: "2800000.00")
                    if (/^\d+([.,]\d{1,2})?$/.test(raw) && !raw.includes(' ')) {
                        const numeric = Number(raw.replace(',', '.'));
                        return Number.isFinite(numeric) ? Math.round(numeric) : 0;
                    }

                    // Grouped style from UI (example: "2.800.000")
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
                    const hasItems = preview.querySelector('.transport-gallery-item');
                    const empty = preview.querySelector('.transport-gallery-empty');
                    if (!hasItems && !empty) {
                        const node = document.createElement('div');
                        node.className = 'transport-gallery-empty';
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
                    preview.querySelectorAll('.transport-gallery-new-item').forEach((node) => node.remove());

                    const files = Array.from(input.files || []).slice(0, 2);
                    files.forEach((file) => {
                        if (!String(file.type || '').startsWith('image/')) return;
                        const url = URL.createObjectURL(file);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'transport-gallery-item transport-gallery-new-item relative overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
                        const media = document.createElement('div');
                        media.className = 'room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border-0 bg-gray-50 dark:bg-gray-800/40';
                        media.innerHTML = buildPreviewPlaceholder();
                        const image = document.createElement('img');
                        image.alt = 'Transport unit image preview';
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
                    const button = event.target.closest('.transport-gallery-remove-btn');
                    if (!button) return;
                    const wrapper = button.closest('.transport-gallery-existing-item');
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
