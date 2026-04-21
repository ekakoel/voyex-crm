@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $touristAttraction = $touristAttraction ?? null;
    $destinations = $destinations ?? collect();
    $activeCurrencyCode = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
    $toDisplayMoney = static function ($amount) use ($activeCurrencyCode): float {
        return round(\App\Support\Currency::convert((float) ($amount ?? 0), 'IDR', $activeCurrencyCode), 0);
    };
    $defaultMarkupType = old('markup_type', $touristAttraction->markup_type ?? 'fixed');
    $defaultMarkup = old('markup');
    if ($defaultMarkup === null) {
        $defaultMarkup = $touristAttraction->markup ?? null;
    }
    if ($defaultMarkup === null) {
        $defaultMarkup = max(0, (float) (($touristAttraction->publish_rate_per_pax ?? 0) - ($touristAttraction->contract_rate_per_pax ?? 0)));
    }
    if (old('markup') === null && $defaultMarkupType !== 'percent') {
        $defaultMarkup = $toDisplayMoney($defaultMarkup);
    }
    $contractRateValue = old('contract_rate_per_pax');
    if ($contractRateValue === null) {
        $contractRateValue = $toDisplayMoney($touristAttraction->contract_rate_per_pax ?? 0);
    }
    $publishRateValue = old('publish_rate_per_pax');
    if ($publishRateValue === null) {
        $publishRateValue = $toDisplayMoney($touristAttraction->publish_rate_per_pax ?? 0);
    }
@endphp

<div class="space-y-4" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images</label>
            <div id="tourist-attraction-gallery-preview"
                class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                data-remove-endpoint-template="{{ isset($touristAttraction) ? route('tourist-attractions.gallery-images.remove', $touristAttraction) : '' }}"
                data-csrf-token="{{ csrf_token() }}">
                @if (!empty($touristAttraction?->gallery_images))
                    @foreach ($touristAttraction->gallery_images as $image)
                        @php
                            $primarySrc = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($image);
                            $fallbackSrc = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($image);
                        @endphp
                        <div class="tourist-gallery-item tourist-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                            <button
                                type="button"
                                class="tourist-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
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
                                @if ($primarySrc)
                                    <img
                                        src="{{ $primarySrc }}"
                                        data-fallback-src="{{ $fallbackSrc ?? '' }}"
                                        onload="this.classList.add('image-loaded');var p=this.closest('.image-preview');if(p){p.classList.add('has-image');}"
                                        onerror="var fallback=this.dataset.fallbackSrc||'';if(this.dataset.fallbackApplied||fallback===''){var p=this.closest('.image-preview');if(p){p.classList.remove('has-image');}this.remove();}else{this.dataset.fallbackApplied='1';this.src=fallback;}"
                                        alt="Tourist attraction gallery"
                                        class="h-full w-full object-cover">
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="tourist-gallery-empty">
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
                @endif
            </div>
            <input id="tourist-attraction-gallery-input" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('removed_gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
            <input name="name" value="{{ old('name', $touristAttraction->name ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Ideal Visit Duration (minutes)</label>
            <input
                name="ideal_visit_minutes"
                type="number"
                min="15"
                max="1440"
                step="5"
                value="{{ old('ideal_visit_minutes', $touristAttraction->ideal_visit_minutes ?? 120) }}"
                class="mt-1 dark:border-gray-600 app-input"
                required
            >
            @error('ideal_visit_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    
    @include('components.map-standard-section', [
        'title' => 'Map & Location Standard',
        'mapPartial' => 'modules.hotels.partials._location-map',
        'mapValue' => old('google_maps_url', $touristAttraction->google_maps_url ?? ''),
        'latitudeValue' => old('latitude', $touristAttraction->latitude ?? ''),
        'longitudeValue' => old('longitude', $touristAttraction->longitude ?? ''),
        'addressValue' => old('address', $touristAttraction->address ?? ''),
        'cityValue' => old('city', $touristAttraction->city ?? ''),
        'provinceValue' => old('province', $touristAttraction->province ?? ''),
        'countryValue' => old('country', $touristAttraction->country ?? ''),
        'destinationValue' => old('destination_id', $touristAttraction->destination_id ?? ''),
        'destinations' => $destinations,
    ])
    <input type="hidden" id="location" data-location-field="location" name="location" value="{{ old('location', $touristAttraction->location ?? '') }}">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Timezone</label>
        <input data-location-field="timezone" name="timezone" value="{{ old('timezone', $touristAttraction->timezone ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <x-money-input
                label="Contract Rate (per pax)"
                name="contract_rate_per_pax"
                :value="$contractRateValue"
                id="tourist-contract-rate"
                min="0"
                step="1"
            />
            @error('contract_rate_per_pax') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Markup Type</label>
            <select name="markup_type" id="tourist-markup-type" class="mt-1 dark:border-gray-600 app-input">
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
                id="tourist-markup"
                min="0"
                step="1"
            />
            @error('markup') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <x-money-input
                label="Publish Rate (Auto / per pax)"
                name="publish_rate_per_pax"
                id="tourist-publish-rate"
                :value="$publishRateValue"
                min="0"
                step="1"
                readonly
            />
            @error('publish_rate_per_pax') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
        <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $touristAttraction->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $touristAttraction->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button  class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('tourist-attractions.index') }}"  class="btn-secondary">Cancel</a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const input = document.getElementById('tourist-attraction-gallery-input');
                const preview = document.getElementById('tourist-attraction-gallery-preview');
                const contractRateInput = document.getElementById('tourist-contract-rate');
                const markupTypeSelect = document.getElementById('tourist-markup-type');
                const markupInput = document.getElementById('tourist-markup');
                const publishRateInput = document.getElementById('tourist-publish-rate');
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
                    const hasItems = preview.querySelector('.tourist-gallery-item');
                    const empty = preview.querySelector('.tourist-gallery-empty');
                    if (!hasItems && !empty) {
                        const node = document.createElement('div');
                        node.className = 'tourist-gallery-empty';
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
                    preview.querySelectorAll('.tourist-gallery-new-item').forEach((node) => node.remove());

                    const files = Array.from(input.files || []);
                    files.forEach((file) => {
                        if (!String(file.type || '').startsWith('image/')) {
                            return;
                        }
                        const url = URL.createObjectURL(file);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'tourist-gallery-item tourist-gallery-new-item relative overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
                        const media = document.createElement('div');
                        media.className = 'room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border-0 bg-gray-50 dark:bg-gray-800/40';
                        media.innerHTML = buildPreviewPlaceholder();
                        const image = document.createElement('img');
                        image.alt = 'Attraction gallery preview';
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
                contractRateInput?.addEventListener('input', recalcPublishRate);
                markupInput?.addEventListener('input', recalcPublishRate);
                markupTypeSelect?.addEventListener('change', recalcPublishRate);

                preview.addEventListener('click', async (event) => {
                    const button = event.target.closest('.tourist-gallery-remove-btn');
                    if (!button) return;
                    const wrapper = button.closest('.tourist-gallery-existing-item');
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
                recalcPublishRate();
            })();
        </script>
    @endpush
@endonce
