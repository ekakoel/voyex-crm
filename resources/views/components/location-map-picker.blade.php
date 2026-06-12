@php
    $mapTitle = $mapTitle ?? ui_phrase('Location on Map (open map)');
    $mapHeightClass = $mapHeightClass ?? 'h-[360px]';
    $latValue = $latValue ?? null;
    $lngValue = $lngValue ?? null;
    $interactive = $interactive ?? true;
    $iconHtml = $iconHtml ?? '<span class="location-map-pin__inner"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>';
    $emptyHint = $emptyHint ?? ui_phrase('Coordinates are empty. The map is shown without a pin.');
    $invalidHint = $invalidHint ?? ui_phrase('Invalid coordinates. Please ensure latitude and longitude are correct.');
    $successHint = $successHint ?? ui_phrase('Location displayed successfully on the map.');
    $movedHint = $movedHint ?? ui_phrase('Marker moved. Coordinates updated successfully.');
@endphp

<div class="space-y-2">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $mapTitle }}</h3>
    <div
        class="{{ $mapHeightClass }} overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
        data-location-map
        data-location-map-interactive="{{ $interactive ? '1' : '0' }}"
        data-location-map-empty-hint="{{ e($emptyHint) }}"
        data-location-map-invalid-hint="{{ e($invalidHint) }}"
        data-location-map-success-hint="{{ e($successHint) }}"
        data-location-map-moved-hint="{{ e($movedHint) }}"
        @if (! is_null($latValue)) data-location-map-lat="{{ $latValue }}" @endif
        @if (! is_null($lngValue)) data-location-map-lng="{{ $lngValue }}" @endif
    ></div>
    <template data-location-map-icon-template>{!! $iconHtml !!}</template>
    <p class="text-xs text-gray-500 dark:text-gray-400" data-location-map-hint></p>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <style>
            .location-map-pin {
                background: transparent;
                border: 0;
            }
            .location-map-pin__inner {
                align-items: center;
                background: #0f766e;
                border: 2px solid #ffffff;
                border-radius: 999px;
                box-shadow: 0 10px 25px -12px rgba(15, 118, 110, 0.9);
                color: #ffffff;
                display: inline-flex;
                font-size: 12px;
                height: 28px;
                justify-content: center;
                width: 28px;
            }
            .dark .location-map-pin__inner {
                border-color: #0f172a;
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            function initLocationMapPicker(root = document) {
                const scope = root instanceof Element || root instanceof Document ? root : document;
                const mapElements = scope.matches?.('[data-location-map]')
                    ? [scope]
                    : Array.from(scope.querySelectorAll('[data-location-map]'));

                mapElements.forEach((mapElement) => {
                    if (mapElement.dataset.locationMapBound === '1' || typeof window.L === 'undefined') {
                        return;
                    }

                    const container = mapElement.closest('[data-location-autofill]') || document;
                    const latitudeInput = container.querySelector('[data-location-field="latitude"]');
                    const longitudeInput = container.querySelector('[data-location-field="longitude"]');
                    const mapUrlInput = container.querySelector('[data-location-map-url-input]');
                    const hintNode = mapElement.parentElement?.querySelector('[data-location-map-hint]') || null;
                    const interactive = mapElement.dataset.locationMapInteractive === '1' && latitudeInput && longitudeInput;
                    const defaultCenter = [-2.5489, 118.0149];

                    const map = L.map(mapElement, { zoomControl: true }).setView(defaultCenter, 5);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const iconTemplate = mapElement.parentElement?.querySelector('[data-location-map-icon-template]');
                    const pinHtml = String(iconTemplate?.innerHTML || '').trim()
                        || '<span class="location-map-pin__inner"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>';

                    const markerIcon = L.divIcon({
                        className: 'location-map-pin',
                        html: pinHtml,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    });

                    let marker = null;

                    const setHint = (message, tone = 'neutral') => {
                        if (!hintNode) {
                            return;
                        }

                        hintNode.textContent = String(message || '').trim();
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

                    const parseCoordinate = (value) => {
                        if (value === null || value === undefined || String(value).trim() === '') {
                            return null;
                        }
                        const parsed = Number.parseFloat(String(value));
                        return Number.isFinite(parsed) ? parsed : null;
                    };

                    const formatCoordinate = (value) => Number(value).toFixed(7);
                    const isCoordinateValid = (latitude, longitude) =>
                        latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;

                    const clearMarker = () => {
                        if (marker) {
                            map.removeLayer(marker);
                            marker = null;
                        }
                        map.setView(defaultCenter, 5);
                    };

                    const readLatitude = () => interactive
                        ? String(latitudeInput.value || '').trim()
                        : String(mapElement.dataset.locationMapLat || '').trim();

                    const readLongitude = () => interactive
                        ? String(longitudeInput.value || '').trim()
                        : String(mapElement.dataset.locationMapLng || '').trim();

                    const syncMapUrlFromCoordinates = (latitude, longitude) => {
                        if (!interactive || !(mapUrlInput instanceof HTMLInputElement) || !isCoordinateValid(latitude, longitude)) {
                            return;
                        }

                        const nextUrl = `https://maps.google.com/?q=${formatCoordinate(latitude)},${formatCoordinate(longitude)}`;
                        if (String(mapUrlInput.value || '').trim() === nextUrl) {
                            return;
                        }

                        mapUrlInput.value = nextUrl;
                        mapUrlInput.dispatchEvent(new Event('input', { bubbles: true }));
                        mapUrlInput.dispatchEvent(new Event('change', { bubbles: true }));
                    };

                    const updateCoordinateInputs = (latitude, longitude) => {
                        if (!interactive) {
                            return;
                        }

                        latitudeInput.value = formatCoordinate(latitude);
                        longitudeInput.value = formatCoordinate(longitude);
                        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                        latitudeInput.dispatchEvent(new Event('change', { bubbles: true }));
                        longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                        longitudeInput.dispatchEvent(new Event('change', { bubbles: true }));
                    };

                    const syncMapMarker = () => {
                        const rawLatitude = readLatitude();
                        const rawLongitude = readLongitude();
                        const latitude = parseCoordinate(rawLatitude);
                        const longitude = parseCoordinate(rawLongitude);

                        if (rawLatitude === '' && rawLongitude === '') {
                            clearMarker();
                            setHint(mapElement.dataset.locationMapEmptyHint || 'Coordinates are empty. The map is shown without a pin.');
                            return;
                        }

                        if (latitude === null || longitude === null || !isCoordinateValid(latitude, longitude)) {
                            clearMarker();
                            setHint(mapElement.dataset.locationMapInvalidHint || 'Invalid coordinates. Please ensure latitude and longitude are correct.', 'error');
                            return;
                        }

                        const latLng = [latitude, longitude];
                        if (!marker) {
                            marker = L.marker(latLng, {
                                icon: markerIcon,
                                draggable: interactive,
                            }).addTo(map);
                        } else {
                            marker.setLatLng(latLng);
                            if (marker.dragging) {
                                if (interactive) {
                                    marker.dragging.enable();
                                } else {
                                    marker.dragging.disable();
                                }
                            }
                        }

                        bindMarkerDragEnd();
                        map.setView(latLng, 15);
                        syncMapUrlFromCoordinates(latitude, longitude);
                        setHint(mapElement.dataset.locationMapSuccessHint || 'Location displayed successfully on the map.', 'success');
                    };

                    if (interactive) {
                        ['input', 'change', 'blur'].forEach((eventName) => {
                            latitudeInput.addEventListener(eventName, syncMapMarker);
                            longitudeInput.addEventListener(eventName, syncMapMarker);
                        });

                        map.on('click', (event) => {
                            updateCoordinateInputs(event.latlng.lat, event.latlng.lng);
                            syncMapUrlFromCoordinates(event.latlng.lat, event.latlng.lng);
                        });
                    }

                    const bindMarkerDragEnd = () => {
                        if (!interactive || !marker || marker.__locationMapDragBound) {
                            return;
                        }

                        marker.on('dragend', () => {
                            const latLng = marker.getLatLng();
                            updateCoordinateInputs(latLng.lat, latLng.lng);
                            syncMapUrlFromCoordinates(latLng.lat, latLng.lng);
                            setHint(mapElement.dataset.locationMapMovedHint || 'Marker moved. Coordinates updated successfully.', 'success');
                        });

                        marker.__locationMapDragBound = true;
                    };

                    mapElement.dataset.locationMapBound = '1';
                    window.setTimeout(() => map.invalidateSize(), 150);
                    syncMapMarker();
                    bindMarkerDragEnd();
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                initLocationMapPicker(document);
            });
        </script>
    @endpush
@endonce
