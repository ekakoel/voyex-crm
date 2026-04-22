@php
    $buttonLabel = $buttonLabel ?? __('ui.modules.island_transfers.save_transfer');
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
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.general_information') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.vendor') }}</label>
            <select name="vendor_id" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">{{ __('ui.modules.island_transfers.select_vendor') }}</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $islandTransfer->vendor_id ?? 0) === (int) $vendor->id)>
                        {{ $vendor->name }}{{ ($vendor->city || $vendor->province) ? ' ('.trim(($vendor->city ?? '-').' / '.($vendor->province ?? '-')).')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('vendor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.transfer_name') }}</label>
            <input name="name" value="{{ old('name', $islandTransfer->name ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.transfer_type') }}</label>
            <select name="transfer_type" class="mt-1 dark:border-gray-600 app-input" required>
                @foreach (['fastboat', 'ferry', 'speedboat', 'boat'] as $type)
                    <option value="{{ $type }}" @selected(old('transfer_type', $islandTransfer->transfer_type ?? 'fastboat') === $type)>
                        {{ __('ui.modules.island_transfers.types.' . $type) }}
                    </option>
                @endforeach
            </select>
            @error('transfer_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.duration_minutes') }}</label>
            <input name="duration_minutes" type="number" min="10" max="1440"
                value="{{ old('duration_minutes', $islandTransfer->duration_minutes ?? 60) }}"
                class="mt-1 dark:border-gray-600 app-input" required>
            @error('duration_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.distance') }} (km)</label>
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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.capacity_min') }}</label>
            <input name="capacity_min" type="number" min="1" value="{{ old('capacity_min', $islandTransfer->capacity_min ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('capacity_min') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.capacity_max') }}</label>
            <input name="capacity_max" type="number" min="1" value="{{ old('capacity_max', $islandTransfer->capacity_max ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('capacity_max') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <x-money-input
                :label="__('ui.modules.island_transfers.contract_rate')"
                name="contract_rate"
                id="island-transfer-contract-rate"
                :value="$contractRateValue"
                min="0"
                step="1"
            />
            @error('contract_rate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.markup_type') }}</label>
            <select name="markup_type" id="island-transfer-markup-type" class="mt-1 dark:border-gray-600 app-input">
                <option value="fixed" @selected($defaultMarkupType === 'fixed')>{{ __('ui.common.fixed') }}</option>
                <option value="percent" @selected($defaultMarkupType === 'percent')>{{ __('ui.common.percent') }}</option>
            </select>
            @error('markup_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <x-money-input
                :label="__('ui.modules.island_transfers.markup')"
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
                :label="__('ui.modules.island_transfers.publish_rate')"
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
        <p class="mb-3 text-sm font-semibold text-sky-800 dark:text-sky-200">{{ __('ui.modules.island_transfers.departure_point') }}</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.departure_google_maps_url') }}</label>
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
                        {{ __('ui.modules.island_transfers.auto_fill_coordinates') }}
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.google_maps_url_helper') }}</p>
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.departure_name') }}</label>
                <input name="departure_point_name" value="{{ old('departure_point_name', $islandTransfer->departure_point_name ?? '') }}"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('departure_point_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.departure_latitude') }}</label>
                <input name="departure_latitude" value="{{ old('departure_latitude', $islandTransfer->departure_latitude ?? '') }}" data-coordinate-target="departure-latitude"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('departure_latitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.departure_longitude') }}</label>
                <input name="departure_longitude" value="{{ old('departure_longitude', $islandTransfer->departure_longitude ?? '') }}" data-coordinate-target="departure-longitude"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('departure_longitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
        <p class="mb-3 text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ __('ui.modules.island_transfers.arrival_point') }}</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.arrival_google_maps_url') }}</label>
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
                        {{ __('ui.modules.island_transfers.auto_fill_coordinates') }}
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.google_maps_url_helper') }}</p>
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.arrival_name') }}</label>
                <input name="arrival_point_name" value="{{ old('arrival_point_name', $islandTransfer->arrival_point_name ?? '') }}"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('arrival_point_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.arrival_latitude') }}</label>
                <input name="arrival_latitude" value="{{ old('arrival_latitude', $islandTransfer->arrival_latitude ?? '') }}" data-coordinate-target="arrival-latitude"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('arrival_latitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.arrival_longitude') }}</label>
                <input name="arrival_longitude" value="{{ old('arrival_longitude', $islandTransfer->arrival_longitude ?? '') }}" data-coordinate-target="arrival-longitude"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('arrival_longitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.route_geojson_optional') }}</label>
        <textarea name="route_geojson" rows="6" data-wysiwyg="false"
            class="mt-1 app-input font-mono"
            placeholder='{"type":"LineString","coordinates":[[115.2634,-8.6901],[115.4501,-8.7348]]}'>{{ $routeGeoJsonValue }}</textarea>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ __('ui.modules.island_transfers.route_geojson_helper') }}
        </p>
        @error('route_geojson') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.notes') }}</label>
        <textarea name="notes" rows="3" class="mt-1 app-input">{{ old('notes', $islandTransfer->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $islandTransfer->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.active') }}</span>
    </div>

    <div class="flex items-center gap-2">
        <button class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('island-transfers.index') }}" class="btn-secondary">{{ __('ui.modules.island_transfers.cancel') }}</a>
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

                const bindAutoFill = (prefix) => {
                    const elements = getElementsByPrefix(prefix);
                    if (!elements.source || !elements.trigger || !elements.latitude || !elements.longitude) return;

                    elements.trigger.addEventListener('click', () => {
                        const coordinates = extractCoordinatesFromGoogleMapsUrl(elements.source.value);
                        if (!coordinates) {
                            window.alert(@json(__('ui.modules.island_transfers.invalid_google_maps_url')));
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
                departureLatInput?.addEventListener('input', recalcDistanceKm);
                departureLngInput?.addEventListener('input', recalcDistanceKm);
                arrivalLatInput?.addEventListener('input', recalcDistanceKm);
                arrivalLngInput?.addEventListener('input', recalcDistanceKm);
                contractRateInput?.addEventListener('input', recalcPublishRate);
                markupInput?.addEventListener('input', recalcPublishRate);
                markupTypeSelect?.addEventListener('change', recalcPublishRate);
                recalcDistanceKm();
                recalcPublishRate();
            })();
        </script>
    @endpush
@endonce
