@php
    $buttonLabel = $buttonLabel ?? ui_phrase('modules_island_transfers_save_transfer');
    $islandTransfer = $islandTransfer ?? null;
    $defaultDepartureMapUrl = '';
    if (($islandTransfer?->departure_latitude ?? null) !== null && ($islandTransfer?->departure_longitude ?? null) !== null) {
        $defaultDepartureMapUrl = 'https://maps.google.com/?q=' . $islandTransfer->departure_latitude . ',' . $islandTransfer->departure_longitude;
    }
    $defaultArrivalMapUrl = '';
    if (($islandTransfer?->arrival_latitude ?? null) !== null && ($islandTransfer?->arrival_longitude ?? null) !== null) {
        $defaultArrivalMapUrl = 'https://maps.google.com/?q=' . $islandTransfer->arrival_latitude . ',' . $islandTransfer->arrival_longitude;
    }
    $departureGoogleMapsUrl = old('departure_google_maps_url', $defaultDepartureMapUrl);
    $arrivalGoogleMapsUrl = old('arrival_google_maps_url', $defaultArrivalMapUrl);
    $routeGeoJsonValue = old(
        'route_geojson',
        $islandTransfer?->route_geojson ? json_encode($islandTransfer->route_geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
    );
    $existingGalleryImages = is_array($islandTransfer?->gallery_images ?? null) ? $islandTransfer->gallery_images : [];
    $distanceKmValue = old('distance_km', $islandTransfer?->distance_km);
    if ($distanceKmValue !== null && $distanceKmValue !== '') {
        $distanceKmValue = number_format((float) $distanceKmValue, 2, '.', '');
    }
    $activeCurrencyCode = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
    $toDisplayMoney = static function ($amount) use ($activeCurrencyCode): float {
        return round(\App\Support\Currency::convert((float) ($amount ?? 0), 'IDR', $activeCurrencyCode), 0);
    };
    $defaultMarkupType = old('markup_type', $islandTransfer->markup_type ?? 'fixed');
    $defaultMarkup = old('markup');
    if ($defaultMarkup === null) {
        $defaultMarkup = $islandTransfer->markup ?? null;
    }
    if ($defaultMarkup === null) {
        $defaultMarkup = max(0, (float) (($islandTransfer->publish_rate ?? 0) - ($islandTransfer->contract_rate ?? 0)));
    }
    if (old('markup') === null && $defaultMarkupType !== 'percent') {
        $defaultMarkup = $toDisplayMoney($defaultMarkup);
    }
    $contractRateValue = old('contract_rate');
    if ($contractRateValue === null) {
        $contractRateValue = $toDisplayMoney($islandTransfer->contract_rate ?? 0);
    }
    $publishRateValue = old('publish_rate');
    if ($publishRateValue === null) {
        $publishRateValue = $toDisplayMoney($islandTransfer->publish_rate ?? 0);
    }
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('common_gallery') }}</label>
            <div id="island-transfer-gallery-preview"
                class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                data-remove-endpoint-template="{{ isset($islandTransfer) ? route('island-transfers.gallery-images.remove', $islandTransfer) : '' }}"
                data-csrf-token="{{ csrf_token() }}">
                @forelse ($existingGalleryImages as $image)
                    @php($thumbUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($image))
                    @php($fullUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($image))
                    <div class="island-transfer-gallery-item island-transfer-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                        <button
                            type="button"
                            class="island-transfer-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                            title="{{ __('Remove image') }}"
                            aria-label="Remove image">
                            X
                        </button>
                        <div class="room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border-0 bg-gray-50 dark:bg-gray-800/40">
                            <div class="image-preview-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>{{ __('Select image to preview') }}</span>
                            </div>
                            @if ($thumbUrl)
                                <img
                                    src="{{ $thumbUrl }}"
                                    onload="this.classList.add('image-loaded');var p=this.closest('.image-preview');if(p){p.classList.add('has-image');}"
                                    onerror="if(this.dataset.fallbackApplied){var p=this.closest('.image-preview');if(p){p.classList.remove('has-image');}this.remove();}else{this.dataset.fallbackApplied='1';this.src='{{ $fullUrl ?? '' }}';}"
                                    alt="Island transfer gallery"
                                    class="h-full w-full object-cover">
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="island-transfer-gallery-empty">
                        <div class="room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40">
                            <div class="image-preview-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>{{ __('Select image to preview') }}</span>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
            <input id="island-transfer-gallery-input" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('removed_gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_island_transfers_general_information') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_vendor') }}</label>
            <select name="vendor_id" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">{{ ui_phrase('modules_island_transfers_select_vendor') }}</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $islandTransfer->vendor_id ?? 0) === (int) $vendor->id)>
                        {{ $vendor->name }}{{ ($vendor->city || $vendor->province) ? ' ('.trim(($vendor->city ?? '-').' / '.($vendor->province ?? '-')).')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('vendor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_transfer_name') }}</label>
            <input name="name" value="{{ old('name', $islandTransfer->name ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_transfer_type') }}</label>
            <select name="transfer_type" class="mt-1 dark:border-gray-600 app-input" required>
                @foreach (['fastboat', 'ferry', 'speedboat', 'boat'] as $type)
                    <option value="{{ $type }}" @selected(old('transfer_type', $islandTransfer->transfer_type ?? 'fastboat') === $type)>
                        {{ ui_phrase('modules_island_transfers_types' . $type) }}
                    </option>
                @endforeach
            </select>
            @error('transfer_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_duration_minutes') }}</label>
            <input name="duration_minutes" type="number" min="10" max="1440"
                value="{{ old('duration_minutes', $islandTransfer->duration_minutes ?? 60) }}"
                class="mt-1 dark:border-gray-600 app-input" required>
            @error('duration_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_distance') }} (km)</label>
            <input
                name="distance_km"
                type="number"
                min="0"
                step="0.01"
                value="{{ $distanceKmValue }}"
                class="mt-1 dark:border-gray-600 app-input"
                id="island-transfer-distance-km"
                readonly
            >
            @error('distance_km') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_capacity_min') }}</label>
            <input name="capacity_min" type="number" min="1" value="{{ old('capacity_min', $islandTransfer->capacity_min ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('capacity_min') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_capacity_max') }}</label>
            <input name="capacity_max" type="number" min="1" value="{{ old('capacity_max', $islandTransfer->capacity_max ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('capacity_max') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <x-money-input
                :label="ui_phrase('modules_island_transfers_contract_rate')"
                name="contract_rate"
                id="island-transfer-contract-rate"
                :value="$contractRateValue"
                min="0"
                step="1"
            />
            @error('contract_rate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_markup_type') }}</label>
            <select name="markup_type" id="island-transfer-markup-type" class="mt-1 dark:border-gray-600 app-input">
                <option value="fixed" @selected($defaultMarkupType === 'fixed')>{{ ui_phrase('common_fixed') }}</option>
                <option value="percent" @selected($defaultMarkupType === 'percent')>{{ ui_phrase('common_percent') }}</option>
            </select>
            @error('markup_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <x-money-input
                :label="ui_phrase('modules_island_transfers_markup')"
                name="markup"
                id="island-transfer-markup"
                :value="$defaultMarkup"
                min="0"
                step="1"
            />
            @error('markup') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <x-money-input
                :label="ui_phrase('modules_island_transfers_publish_rate')"
                name="publish_rate"
                id="island-transfer-publish-rate"
                :value="$publishRateValue"
                min="0"
                step="1"
                readonly
            />
            @error('publish_rate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="rounded-lg border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-700 dark:bg-sky-900/20">
        <p class="mb-3 text-sm font-semibold text-sky-800 dark:text-sky-200">{{ ui_phrase('modules_island_transfers_departure_point') }}</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_departure_google_maps_url') }}</label>
                <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input
                        type="url"
                        name="departure_google_maps_url"
                        value="{{ $departureGoogleMapsUrl }}"
                        placeholder="{{ __('https://maps.google.com/?q=-8.6901,115.2634') }}"
                        class="app-input"
                        data-map-url-source="departure"
                    >
                    <button
                        type="button"
                        class="btn-outline-sm w-full justify-center sm:w-auto"
                        data-map-url-autofill="departure"
                    >
                        {{ ui_phrase('modules_island_transfers_auto_fill_coordinates') }}
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_island_transfers_google_maps_url_helper') }}</p>
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_departure_name') }}</label>
                <input name="departure_point_name" value="{{ old('departure_point_name', $islandTransfer->departure_point_name ?? '') }}"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('departure_point_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_departure_latitude') }}</label>
                <input name="departure_latitude" value="{{ old('departure_latitude', $islandTransfer->departure_latitude ?? '') }}" data-coordinate-target="departure-latitude"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('departure_latitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_departure_longitude') }}</label>
                <input name="departure_longitude" value="{{ old('departure_longitude', $islandTransfer->departure_longitude ?? '') }}" data-coordinate-target="departure-longitude"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('departure_longitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
        <p class="mb-3 text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ ui_phrase('modules_island_transfers_arrival_point') }}</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_arrival_google_maps_url') }}</label>
                <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input
                        type="url"
                        name="arrival_google_maps_url"
                        value="{{ $arrivalGoogleMapsUrl }}"
                        placeholder="{{ __('https://maps.google.com/?q=-8.7278,115.5444') }}"
                        class="app-input"
                        data-map-url-source="arrival"
                    >
                    <button
                        type="button"
                        class="btn-outline-sm w-full justify-center sm:w-auto"
                        data-map-url-autofill="arrival"
                    >
                        {{ ui_phrase('modules_island_transfers_auto_fill_coordinates') }}
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_island_transfers_google_maps_url_helper') }}</p>
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_arrival_name') }}</label>
                <input name="arrival_point_name" value="{{ old('arrival_point_name', $islandTransfer->arrival_point_name ?? '') }}"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('arrival_point_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_arrival_latitude') }}</label>
                <input name="arrival_latitude" value="{{ old('arrival_latitude', $islandTransfer->arrival_latitude ?? '') }}" data-coordinate-target="arrival-latitude"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('arrival_latitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_arrival_longitude') }}</label>
                <input name="arrival_longitude" value="{{ old('arrival_longitude', $islandTransfer->arrival_longitude ?? '') }}" data-coordinate-target="arrival-longitude"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('arrival_longitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_route_geojson_optional') }}</label>
        <textarea name="route_geojson" rows="6" data-wysiwyg="false"
            class="mt-1 app-input font-mono"
            placeholder='{"type":"LineString","coordinates":[[115.2634,-8.6901],[115.4501,-8.7348]]}'>{{ $routeGeoJsonValue }}</textarea>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ ui_phrase('modules_island_transfers_route_geojson_helper') }}
        </p>
        @error('route_geojson') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_notes') }}</label>
        <textarea name="notes" rows="3" class="mt-1 app-input">{{ old('notes', $islandTransfer->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $islandTransfer->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_island_transfers_active') }}</span>
    </div>

    <div class="flex items-center gap-2">
        <button class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('island-transfers.index') }}" class="btn-secondary">{{ ui_phrase('modules_island_transfers_cancel') }}</a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const parseCoordinate = (value) => {
                    const normalized = String(value ?? '').trim().replace(',', '.');
                    const parsed = Number(normalized);
                    return Number.isFinite(parsed) ? parsed : null;
                };

                const isValidCoordinate = (latitude, longitude) => {
                    return Number.isFinite(latitude) && Number.isFinite(longitude)
                        && latitude >= -90 && latitude <= 90
                        && longitude >= -180 && longitude <= 180;
                };

                const normalizeMapUrl = (value) => String(value ?? '').trim();

                const extractFromQueryValue = (queryValue) => {
                    const raw = String(queryValue ?? '').trim();
                    if (!raw) return null;
                    const match = raw.match(/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/);
                    if (!match) return null;
                    const latitude = parseCoordinate(match[1]);
                    const longitude = parseCoordinate(match[2]);
                    if (!isValidCoordinate(latitude, longitude)) return null;
                    return { latitude, longitude };
                };

                const extractCoordinatesFromGoogleMapsUrl = (urlValue) => {
                    const rawUrl = normalizeMapUrl(urlValue);
                    if (!rawUrl) return null;

                    try {
                        const parsed = new URL(rawUrl);

                        const queryCandidates = [
                            parsed.searchParams.get('q'),
                            parsed.searchParams.get('query'),
                            parsed.searchParams.get('ll'),
                            parsed.searchParams.get('sll'),
                            parsed.searchParams.get('center'),
                        ];
                        for (const candidate of queryCandidates) {
                            const fromQuery = extractFromQueryValue(candidate);
                            if (fromQuery) return fromQuery;
                        }

                        const fullUrlText = `${parsed.pathname}${parsed.search}${parsed.hash}`;

                        const atMatch = fullUrlText.match(/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/);
                        if (atMatch) {
                            const latitude = parseCoordinate(atMatch[1]);
                            const longitude = parseCoordinate(atMatch[2]);
                            if (isValidCoordinate(latitude, longitude)) {
                                return { latitude, longitude };
                            }
                        }

                        const dMatch = fullUrlText.match(/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/);
                        if (dMatch) {
                            const latitude = parseCoordinate(dMatch[1]);
                            const longitude = parseCoordinate(dMatch[2]);
                            if (isValidCoordinate(latitude, longitude)) {
                                return { latitude, longitude };
                            }
                        }
                    } catch (_) {
                        return null;
                    }

                    return null;
                };

                const formatCoordinate = (value) => {
                    return String(Math.round(Number(value) * 1000000) / 1000000);
                };

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

                const contractRateInput = document.getElementById('island-transfer-contract-rate');
                const markupTypeSelect = document.getElementById('island-transfer-markup-type');
                const markupInput = document.getElementById('island-transfer-markup');
                const publishRateInput = document.getElementById('island-transfer-publish-rate');

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

                const getElementsByPrefix = (prefix) => {
                    return {
                        source: document.querySelector(`[data-map-url-source="${prefix}"]`),
                        trigger: document.querySelector(`[data-map-url-autofill="${prefix}"]`),
                        latitude: document.querySelector(`[data-coordinate-target="${prefix}-latitude"]`),
                        longitude: document.querySelector(`[data-coordinate-target="${prefix}-longitude"]`),
                    };
                };

                const distanceInput = document.getElementById('island-transfer-distance-km');
                const galleryInput = document.getElementById('island-transfer-gallery-input');
                const galleryPreview = document.getElementById('island-transfer-gallery-preview');
                const departureLatInput = document.querySelector('[data-coordinate-target="departure-latitude"]');
                const departureLngInput = document.querySelector('[data-coordinate-target="departure-longitude"]');
                const arrivalLatInput = document.querySelector('[data-coordinate-target="arrival-latitude"]');
                const arrivalLngInput = document.querySelector('[data-coordinate-target="arrival-longitude"]');

                const toRadians = (value) => (value * Math.PI) / 180;
                const computeHaversineKm = (lat1, lng1, lat2, lng2) => {
                    const earthRadiusKm = 6371;
                    const dLat = toRadians(lat2 - lat1);
                    const dLng = toRadians(lng2 - lng1);
                    const a = Math.sin(dLat / 2) ** 2
                        + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(dLng / 2) ** 2;
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(Math.max(0, 1 - a)));

                    return earthRadiusKm * c;
                };

                const recalcDistanceKm = () => {
                    if (!distanceInput) return;
                    const depLat = parseCoordinate(departureLatInput?.value);
                    const depLng = parseCoordinate(departureLngInput?.value);
                    const arrLat = parseCoordinate(arrivalLatInput?.value);
                    const arrLng = parseCoordinate(arrivalLngInput?.value);

                    if (!isValidCoordinate(depLat, depLng) || !isValidCoordinate(arrLat, arrLng)) {
                        distanceInput.value = '';
                        return;
                    }

                    const distance = computeHaversineKm(depLat, depLng, arrLat, arrLng);
                    distanceInput.value = String(Math.round(distance * 100) / 100);
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

                const ensureGalleryEmptyState = () => {
                    if (!galleryPreview) return;
                    const hasItems = galleryPreview.querySelector('.island-transfer-gallery-item');
                    const empty = galleryPreview.querySelector('.island-transfer-gallery-empty');
                    if (!hasItems && !empty) {
                        const node = document.createElement('div');
                        node.className = 'island-transfer-gallery-empty';
                        node.innerHTML = `
                            <div class="room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40">
                                ${buildPreviewPlaceholder()}
                            </div>
                        `;
                        galleryPreview.appendChild(node);
                    }
                    if (hasItems && empty) {
                        empty.remove();
                    }
                };

                const renderNewUploads = () => {
                    if (!galleryInput || !galleryPreview) return;
                    galleryPreview.querySelectorAll('.island-transfer-gallery-new-item').forEach((node) => node.remove());

                    const files = Array.from(galleryInput.files || []);
                    files.forEach((file) => {
                        if (!String(file.type || '').startsWith('image/')) return;
                        const url = URL.createObjectURL(file);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'island-transfer-gallery-item island-transfer-gallery-new-item relative overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
                        const media = document.createElement('div');
                        media.className = 'room-cover-preview image-preview flex w-full items-center justify-center overflow-hidden rounded-lg border-0 bg-gray-50 dark:bg-gray-800/40';
                        media.innerHTML = buildPreviewPlaceholder();
                        const image = document.createElement('img');
                        image.alt = 'Island transfer gallery preview';
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
                        galleryPreview.appendChild(wrapper);
                    });

                    ensureGalleryEmptyState();
                };

                const bindAutoFill = (prefix) => {
                    const elements = getElementsByPrefix(prefix);
                    if (!elements.source || !elements.trigger || !elements.latitude || !elements.longitude) return;

                    elements.trigger.addEventListener('click', () => {
                        const coordinates = extractCoordinatesFromGoogleMapsUrl(elements.source.value);
                        if (!coordinates) {
                            window.alert(@json(ui_phrase('modules_island_transfers_invalid_google_maps_url')));
                            elements.source.focus();
                            return;
                        }

                        elements.latitude.value = formatCoordinate(coordinates.latitude);
                        elements.longitude.value = formatCoordinate(coordinates.longitude);
                        elements.latitude.dispatchEvent(new Event('input', { bubbles: true }));
                        elements.longitude.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                };

                bindAutoFill('departure');
                bindAutoFill('arrival');
                galleryInput?.addEventListener('change', renderNewUploads);
                departureLatInput?.addEventListener('input', recalcDistanceKm);
                departureLngInput?.addEventListener('input', recalcDistanceKm);
                arrivalLatInput?.addEventListener('input', recalcDistanceKm);
                arrivalLngInput?.addEventListener('input', recalcDistanceKm);
                contractRateInput?.addEventListener('input', recalcPublishRate);
                markupInput?.addEventListener('input', recalcPublishRate);
                markupTypeSelect?.addEventListener('change', recalcPublishRate);

                galleryPreview?.addEventListener('click', async (event) => {
                    const button = event.target.closest('.island-transfer-gallery-remove-btn');
                    if (!button) return;
                    const wrapper = button.closest('.island-transfer-gallery-existing-item');
                    const imagePath = String(wrapper?.dataset.imagePath || '');
                    if (!wrapper || imagePath === '') return;

                    const endpoint = String(galleryPreview.dataset.removeEndpointTemplate || '');
                    const csrfToken = String(galleryPreview.dataset.csrfToken || '');
                    if (endpoint === '' || csrfToken === '') {
                        wrapper.remove();
                        ensureGalleryEmptyState();
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
                        ensureGalleryEmptyState();
                    } catch (_) {
                        button.disabled = false;
                        button.classList.remove('opacity-70');
                        window.alert('Failed to delete image. Please try again.');
                    }
                });

                ensureGalleryEmptyState();
                recalcDistanceKm();
                recalcPublishRate();
            })();
        </script>
    @endpush
@endonce
