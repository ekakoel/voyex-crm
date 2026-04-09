@php
    $mapTitle = $mapTitle ?? 'Hotel Location Map';
    $mapHeightClass = $mapHeightClass ?? 'h-[320px]';
    $latValue = $latValue ?? null;
    $lngValue = $lngValue ?? null;
    $interactive = $interactive ?? true;
@endphp

<div class="space-y-2">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $mapTitle }}</h3>
    <div
        class="{{ $mapHeightClass }} overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
        data-hotel-map
        @if (! is_null($latValue)) data-hotel-map-lat="{{ $latValue }}" @endif
        @if (! is_null($lngValue)) data-hotel-map-lng="{{ $lngValue }}" @endif
        data-hotel-map-interactive="{{ $interactive ? '1' : '0' }}"
    ></div>
    <p class="text-xs text-gray-500 dark:text-gray-400" data-hotel-map-hint></p>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <style>
            .hotel-map-pin {
                background: transparent;
                border: 0;
            }
            .hotel-map-pin__inner {
                align-items: center;
                background: #0f766e;
                border: 2px solid #ffffff;
                border-radius: 999px;
                color: #ffffff;
                display: inline-flex;
                font-size: 12px;
                height: 28px;
                justify-content: center;
                width: 28px;
                box-shadow: 0 10px 25px -12px rgba(15, 118, 110, 0.9);
            }
            .dark .hotel-map-pin__inner {
                border-color: #0f172a;
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            function initHotelLocationMap(root = document) {
                const scope = root instanceof Element || root instanceof Document ? root : document;
                const mapElements = scope.matches?.('[data-hotel-map]')
                    ? [scope]
                    : Array.from(scope.querySelectorAll('[data-hotel-map]'));

                mapElements.forEach((mapElement) => {
                    if (mapElement.dataset.hotelMapBound === '1' || typeof window.L === 'undefined') {
                        return;
                    }

                    const container = mapElement.closest('[data-location-autofill]') || document;
                    const latitudeInput = container.querySelector('[data-location-field="latitude"]');
                    const longitudeInput = container.querySelector('[data-location-field="longitude"]');
                    const googleMapsUrlInput = container.querySelector('[data-location-field="google_maps_url"]');
                    const hintNode = mapElement.parentElement?.querySelector('[data-hotel-map-hint]') || null;
                    const interactive = mapElement.dataset.hotelMapInteractive === '1' && latitudeInput && longitudeInput;

                    const defaultCenter = [-2.5489, 118.0149];
                    const map = L.map(mapElement, { zoomControl: true }).setView(defaultCenter, 5);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const hotelIcon = L.divIcon({
                        className: 'hotel-map-pin',
                        html: '<span class="hotel-map-pin__inner"><i class="fa-solid fa-hotel" aria-hidden="true"></i></span>',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    });

                    let marker = null;

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

                    const parseCoordinate = (value) => {
                        if (value === null || value === undefined || String(value).trim() === '') return null;
                        const parsed = Number.parseFloat(String(value));
                        return Number.isFinite(parsed) ? parsed : null;
                    };
                    const formatCoordinate = (value) => Number(value).toFixed(7);

                    const isCoordinateValid = (latitude, longitude) => latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;

                    const clearMarker = () => {
                        if (marker) {
                            map.removeLayer(marker);
                            marker = null;
                        }
                        map.setView(defaultCenter, 5);
                    };

                    const readLatitude = () => interactive
                        ? String(latitudeInput.value || '').trim()
                        : String(mapElement.dataset.hotelMapLat || '').trim();

                    const readLongitude = () => interactive
                        ? String(longitudeInput.value || '').trim()
                        : String(mapElement.dataset.hotelMapLng || '').trim();

                    const syncGoogleMapsUrlFromCoordinates = (latitude, longitude) => {
                        if (!interactive || !googleMapsUrlInput || !isCoordinateValid(latitude, longitude)) {
                            return;
                        }

                        const nextUrl = `https://maps.google.com/?q=${formatCoordinate(latitude)},${formatCoordinate(longitude)}`;
                        if (String(googleMapsUrlInput.value || '').trim() === nextUrl) {
                            return;
                        }

                        googleMapsUrlInput.value = nextUrl;
                        googleMapsUrlInput.dispatchEvent(new Event('input', { bubbles: true }));
                        googleMapsUrlInput.dispatchEvent(new Event('change', { bubbles: true }));
                    };

                    const updateCoordinateInputs = (latitude, longitude) => {
                        if (!interactive) {
                            return;
                        }

                        latitudeInput.value = formatCoordinate(latitude);
                        longitudeInput.value = formatCoordinate(longitude);
                        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                        longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    };

                    const bindMarkerDragEnd = () => {
                        if (!interactive || !marker || marker.__hotelMapDragBound) {
                            return;
                        }

                        marker.on('dragend', () => {
                            const latLng = marker.getLatLng();
                            updateCoordinateInputs(latLng.lat, latLng.lng);
                            syncGoogleMapsUrlFromCoordinates(latLng.lat, latLng.lng);
                            setHint('Marker dipindahkan. Koordinat berhasil diperbarui.', 'success');
                        });

                        marker.__hotelMapDragBound = true;
                    };

                    const syncMapMarker = () => {
                        const rawLatitude = readLatitude();
                        const rawLongitude = readLongitude();
                        const latitude = parseCoordinate(rawLatitude);
                        const longitude = parseCoordinate(rawLongitude);

                        if (rawLatitude === '' && rawLongitude === '') {
                            clearMarker();
                            setHint('Koordinat belum diisi. Map tetap tampil tanpa pin.');
                            return;
                        }

                        if (latitude === null || longitude === null || !isCoordinateValid(latitude, longitude)) {
                            if (marker) {
                                map.removeLayer(marker);
                                marker = null;
                            }
                            map.setView(defaultCenter, 5);
                            setHint('Koordinat tidak valid. Pastikan latitude dan longitude benar.', 'error');
                            return;
                        }

                        const latLng = [latitude, longitude];
                        if (!marker) {
                            marker = L.marker(latLng, {
                                icon: hotelIcon,
                                draggable: interactive,
                            }).addTo(map);
                            bindMarkerDragEnd();
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

                        map.setView(latLng, 15);
                        syncGoogleMapsUrlFromCoordinates(latitude, longitude);
                        setHint('Lokasi hotel berhasil ditampilkan pada map.', 'success');
                    };

                    if (interactive) {
                        const bindCoordinateEvents = (input) => {
                            ['input', 'change', 'blur'].forEach((eventName) => {
                                input.addEventListener(eventName, syncMapMarker);
                            });
                        };
                        bindCoordinateEvents(latitudeInput);
                        bindCoordinateEvents(longitudeInput);

                        map.on('click', (event) => {
                            updateCoordinateInputs(event.latlng.lat, event.latlng.lng);
                            syncGoogleMapsUrlFromCoordinates(event.latlng.lat, event.latlng.lng);
                        });
                    }

                    mapElement.dataset.hotelMapBound = '1';
                    window.setTimeout(() => map.invalidateSize(), 150);
                    syncMapMarker();
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                initHotelLocationMap(document);
            });
        </script>
    @endpush
@endonce

