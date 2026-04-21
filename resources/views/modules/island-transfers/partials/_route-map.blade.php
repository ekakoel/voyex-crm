@php
    $mapTitle = $mapTitle ?? 'Island Transfer Route Map';
    $mapHeightClass = $mapHeightClass ?? 'h-[360px]';
    $interactive = $interactive ?? true;
    $departureLat = $departureLat ?? null;
    $departureLng = $departureLng ?? null;
    $arrivalLat = $arrivalLat ?? null;
    $arrivalLng = $arrivalLng ?? null;
    $routeGeoJson = $routeGeoJson ?? null;
@endphp

<div class="app-card p-5 space-y-2">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $mapTitle }}</h3>
    @if ($interactive)
        <div class="flex items-center gap-2 text-xs" data-island-transfer-map-mode-group>
            <span class="text-gray-600 dark:text-gray-300">{{ __('Pin mode:') }}</span>
            <button type="button" class="btn-outline-sm !h-8 !px-2.5" data-island-transfer-pin-mode="departure">{{ __('Departure') }}</button>
            <button type="button" class="btn-secondary-sm !h-8 !px-2.5" data-island-transfer-pin-mode="arrival">{{ __('Arrival') }}</button>
        </div>
    @endif
    <div
        class="{{ $mapHeightClass }} overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
        data-island-transfer-map
        data-island-transfer-map-interactive="{{ $interactive ? '1' : '0' }}"
        @if (! is_null($departureLat)) data-island-transfer-departure-lat="{{ $departureLat }}" @endif
        @if (! is_null($departureLng)) data-island-transfer-departure-lng="{{ $departureLng }}" @endif
        @if (! is_null($arrivalLat)) data-island-transfer-arrival-lat="{{ $arrivalLat }}" @endif
        @if (! is_null($arrivalLng)) data-island-transfer-arrival-lng="{{ $arrivalLng }}" @endif
        @if (! is_null($routeGeoJson)) data-island-transfer-route="{{ e(json_encode($routeGeoJson, JSON_UNESCAPED_SLASHES)) }}" @endif
    ></div>
    <p class="text-xs text-gray-500 dark:text-gray-400" data-island-transfer-map-hint></p>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <style>
            .island-transfer-map-pin {
                background: transparent;
                border: 0;
            }
            .island-transfer-map-pin__inner {
                align-items: center;
                border: 2px solid #ffffff;
                border-radius: 999px;
                color: #ffffff;
                display: inline-flex;
                font-size: 12px;
                height: 28px;
                justify-content: center;
                width: 28px;
                box-shadow: 0 10px 25px -12px rgba(15, 23, 42, 0.7);
            }
            .island-transfer-map-pin__inner--departure {
                background: #0284c7;
            }
            .island-transfer-map-pin__inner--arrival {
                background: #059669;
            }
            .dark .island-transfer-map-pin__inner {
                border-color: #0f172a;
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            function initIslandTransferRouteMap(root = document) {
                const scope = root instanceof Element || root instanceof Document ? root : document;
                const mapElements = scope.matches?.('[data-island-transfer-map]')
                    ? [scope]
                    : Array.from(scope.querySelectorAll('[data-island-transfer-map]'));

                const parseCoordinate = (value) => {
                    if (value === null || value === undefined || String(value).trim() === '') {
                        return null;
                    }
                    const parsed = Number.parseFloat(String(value).trim().replace(',', '.'));
                    return Number.isFinite(parsed) ? parsed : null;
                };

                const isValidCoordinate = (latitude, longitude) =>
                    Number.isFinite(latitude) &&
                    Number.isFinite(longitude) &&
                    latitude >= -90 &&
                    latitude <= 90 &&
                    longitude >= -180 &&
                    longitude <= 180;

                const normalizeRouteCoordinates = (raw) => {
                    if (!raw) return [];
                    let parsed = raw;
                    if (typeof parsed === 'string') {
                        const trimmed = parsed.trim();
                        if (!trimmed) return [];
                        try {
                            parsed = JSON.parse(trimmed);
                            if (typeof parsed === 'string') {
                                parsed = JSON.parse(parsed);
                            }
                        } catch (_) {
                            return [];
                        }
                    }

                    let coordinates = [];
                    const type = String(parsed?.type || '').toLowerCase();
                    if (type === 'linestring' && Array.isArray(parsed.coordinates)) {
                        coordinates = parsed.coordinates;
                    } else if (type === 'feature' && parsed.geometry && String(parsed.geometry.type || '').toLowerCase() === 'linestring' && Array.isArray(parsed.geometry.coordinates)) {
                        coordinates = parsed.geometry.coordinates;
                    } else if (type === 'featurecollection' && Array.isArray(parsed.features)) {
                        const lineFeature = parsed.features.find((feature) =>
                            feature &&
                            feature.geometry &&
                            String(feature.geometry.type || '').toLowerCase() === 'linestring' &&
                            Array.isArray(feature.geometry.coordinates)
                        );
                        coordinates = lineFeature?.geometry?.coordinates || [];
                    } else if (Array.isArray(parsed)) {
                        coordinates = parsed;
                    }

                    return coordinates
                        .map((coord) => {
                            if (!Array.isArray(coord) || coord.length < 2) return null;
                            const lng = parseCoordinate(coord[0]);
                            const lat = parseCoordinate(coord[1]);
                            if (!isValidCoordinate(lat, lng)) return null;
                            return [lat, lng];
                        })
                        .filter((item) => Array.isArray(item));
                };

                mapElements.forEach((mapElement) => {
                    if (mapElement.dataset.islandTransferMapBound === '1' || typeof window.L === 'undefined') {
                        return;
                    }

                    const defaultCenter = [-2.5489, 118.0149];
                    const map = L.map(mapElement, { zoomControl: true }).setView(defaultCenter, 5);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const hintNode = mapElement.parentElement?.querySelector('[data-island-transfer-map-hint]') || null;
                    const interactive = mapElement.dataset.islandTransferMapInteractive === '1';
                    const host = mapElement.closest('.module-grid-8-4') || document;
                    const modeButtons = interactive
                        ? Array.from(host.querySelectorAll('[data-island-transfer-pin-mode]'))
                        : [];
                    let activePinMode = 'departure';

                    const departureLatInput = interactive ? host.querySelector('input[name="departure_latitude"]') : null;
                    const departureLngInput = interactive ? host.querySelector('input[name="departure_longitude"]') : null;
                    const arrivalLatInput = interactive ? host.querySelector('input[name="arrival_latitude"]') : null;
                    const arrivalLngInput = interactive ? host.querySelector('input[name="arrival_longitude"]') : null;
                    const routeGeoJsonInput = interactive ? host.querySelector('textarea[name="route_geojson"]') : null;
                    const departureMapUrlInput = interactive ? host.querySelector('input[name="departure_google_maps_url"]') : null;
                    const arrivalMapUrlInput = interactive ? host.querySelector('input[name="arrival_google_maps_url"]') : null;

                    const departureIcon = L.divIcon({
                        className: 'island-transfer-map-pin',
                        html: '<span class="island-transfer-map-pin__inner island-transfer-map-pin__inner--departure"><i class="fa-solid fa-ship" aria-hidden="true"></i></span>',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    });
                    const arrivalIcon = L.divIcon({
                        className: 'island-transfer-map-pin',
                        html: '<span class="island-transfer-map-pin__inner island-transfer-map-pin__inner--arrival"><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i></span>',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    });

                    let departureMarker = null;
                    let arrivalMarker = null;
                    let routeLine = null;

                    const setMapUrl = (type, latitude, longitude) => {
                        const target = type === 'arrival' ? arrivalMapUrlInput : departureMapUrlInput;
                        if (!target || !isValidCoordinate(latitude, longitude)) return;
                        const nextUrl = `https://maps.google.com/?q=${Number(latitude).toFixed(6)},${Number(longitude).toFixed(6)}`;
                        if (String(target.value || '').trim() === nextUrl) return;
                        target.value = nextUrl;
                        target.dispatchEvent(new Event('input', { bubbles: true }));
                        target.dispatchEvent(new Event('change', { bubbles: true }));
                    };

                    const updateCoordinateInputs = (type, latitude, longitude) => {
                        const latInput = type === 'arrival' ? arrivalLatInput : departureLatInput;
                        const lngInput = type === 'arrival' ? arrivalLngInput : departureLngInput;
                        if (!latInput || !lngInput) return;
                        latInput.value = Number(latitude).toFixed(6);
                        lngInput.value = Number(longitude).toFixed(6);
                        latInput.dispatchEvent(new Event('input', { bubbles: true }));
                        lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                        setMapUrl(type, latitude, longitude);
                    };

                    const refreshModeButtons = () => {
                        if (!interactive || modeButtons.length === 0) return;
                        modeButtons.forEach((button) => {
                            const mode = String(button.dataset.islandTransferPinMode || '').trim();
                            const isActive = mode === activePinMode;
                            button.classList.toggle('btn-outline-sm', isActive);
                            button.classList.toggle('btn-secondary-sm', !isActive);
                        });
                    };

                    const bindMarkerDrag = (marker, type) => {
                        if (!interactive || !marker || marker.__islandTransferDragBound) return;
                        marker.on('dragend', () => {
                            const latLng = marker.getLatLng();
                            updateCoordinateInputs(type, latLng.lat, latLng.lng);
                            setHint(`${type === 'arrival' ? 'Arrival' : 'Departure'} marker moved. Coordinates updated.`, 'success');
                        });
                        marker.__islandTransferDragBound = true;
                    };

                    const setHint = (message, tone = 'neutral') => {
                        if (!hintNode) return;
                        hintNode.textContent = message;
                        hintNode.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-rose-600', 'dark:text-rose-400', 'text-emerald-600', 'dark:text-emerald-400');
                        if (tone === 'error') {
                            hintNode.classList.add('text-rose-600', 'dark:text-rose-400');
                            return;
                        }
                        if (tone === 'success') {
                            hintNode.classList.add('text-emerald-600', 'dark:text-emerald-400');
                            return;
                        }
                        hintNode.classList.add('text-gray-500', 'dark:text-gray-400');
                    };

                    const readRawValue = (input, dataKey) => {
                        if (input) {
                            return String(input.value || '').trim();
                        }
                        return String(mapElement.dataset[dataKey] || '').trim();
                    };

                    const decodeHtmlEntities = (value) => {
                        const textarea = document.createElement('textarea');
                        textarea.innerHTML = String(value || '');
                        return textarea.value;
                    };

                    const readRouteRawValue = () => {
                        if (routeGeoJsonInput) {
                            return String(routeGeoJsonInput.value || '').trim();
                        }
                        const raw = String(mapElement.dataset.islandTransferRoute || '').trim();
                        return decodeHtmlEntities(raw);
                    };

                    const updateMarker = (currentMarker, lat, lng, icon, popupText, type) => {
                        if (!isValidCoordinate(lat, lng)) {
                            if (currentMarker) {
                                map.removeLayer(currentMarker);
                            }
                            return null;
                        }
                        if (!currentMarker) {
                            currentMarker = L.marker([lat, lng], {
                                icon,
                                draggable: interactive,
                            }).addTo(map);
                            bindMarkerDrag(currentMarker, type);
                        } else {
                            currentMarker.setLatLng([lat, lng]);
                            if (currentMarker.dragging) {
                                if (interactive) {
                                    currentMarker.dragging.enable();
                                } else {
                                    currentMarker.dragging.disable();
                                }
                            }
                        }
                        if (popupText) {
                            currentMarker.bindPopup(popupText);
                        }
                        return currentMarker;
                    };

                    const syncMap = () => {
                        const departureLat = parseCoordinate(readRawValue(departureLatInput, 'islandTransferDepartureLat'));
                        const departureLng = parseCoordinate(readRawValue(departureLngInput, 'islandTransferDepartureLng'));
                        const arrivalLat = parseCoordinate(readRawValue(arrivalLatInput, 'islandTransferArrivalLat'));
                        const arrivalLng = parseCoordinate(readRawValue(arrivalLngInput, 'islandTransferArrivalLng'));
                        const routeCoords = normalizeRouteCoordinates(readRouteRawValue());

                        departureMarker = updateMarker(
                            departureMarker,
                            departureLat,
                            departureLng,
                            departureIcon,
                            '<strong>Departure Point</strong>',
                            'departure'
                        );
                        arrivalMarker = updateMarker(
                            arrivalMarker,
                            arrivalLat,
                            arrivalLng,
                            arrivalIcon,
                            '<strong>Arrival Point</strong>',
                            'arrival'
                        );

                        if (routeLine) {
                            map.removeLayer(routeLine);
                            routeLine = null;
                        }
                        if (routeCoords.length >= 2) {
                            routeLine = L.polyline(routeCoords, {
                                color: '#7c3aed',
                                weight: 4,
                                opacity: 0.85,
                            }).addTo(map);
                        }

                        const fitTargets = [];
                        if (departureMarker) fitTargets.push(departureMarker.getLatLng());
                        if (arrivalMarker) fitTargets.push(arrivalMarker.getLatLng());
                        if (routeLine) {
                            routeLine.getLatLngs().forEach((point) => fitTargets.push(point));
                        }

                        if (!fitTargets.length) {
                            map.setView(defaultCenter, 5);
                            setHint('Map will update after departure/arrival coordinates or route are filled.');
                            return;
                        }

                        const bounds = L.latLngBounds(fitTargets);
                        map.fitBounds(bounds.pad(0.15));
                        setHint('Departure, arrival, and route are displayed on the map.', 'success');
                    };

                    if (interactive) {
                        if (modeButtons.length > 0) {
                            modeButtons.forEach((button) => {
                                button.addEventListener('click', () => {
                                    const mode = String(button.dataset.islandTransferPinMode || '').trim();
                                    if (mode !== 'arrival' && mode !== 'departure') return;
                                    activePinMode = mode;
                                    refreshModeButtons();
                                    setHint(`Pin mode set to ${mode}. Click map to place marker.`, 'success');
                                });
                            });
                            refreshModeButtons();
                        }

                        [departureLatInput, departureLngInput, arrivalLatInput, arrivalLngInput, routeGeoJsonInput]
                            .filter((input) => input)
                            .forEach((input) => {
                                ['input', 'change', 'blur'].forEach((eventName) => {
                                    input.addEventListener(eventName, syncMap);
                                });
                            });

                        map.on('click', (event) => {
                            const type = activePinMode === 'arrival' ? 'arrival' : 'departure';
                            updateCoordinateInputs(type, event.latlng.lat, event.latlng.lng);
                            setHint(`${type === 'arrival' ? 'Arrival' : 'Departure'} point set from map click.`, 'success');
                        });
                    }

                    mapElement.dataset.islandTransferMapBound = '1';
                    window.setTimeout(() => map.invalidateSize(), 150);
                    syncMap();
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                initIslandTransferRouteMap(document);
            });
        </script>
    @endpush
@endonce
