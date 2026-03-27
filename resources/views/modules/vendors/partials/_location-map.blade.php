<div class="space-y-2">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Location on Map (open map)</h3>
    <div class="h-[360px] overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-vendor-map></div>
    <p class="text-xs text-gray-500 dark:text-gray-400" data-vendor-map-hint></p>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <style>
            .vendor-map-pin {
                background: transparent;
                border: 0;
            }
            .vendor-map-pin__inner {
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
            .dark .vendor-map-pin__inner {
                border-color: #0f172a;
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            function initVendorLocationMap(root = document) {
                const scope = root instanceof Element || root instanceof Document ? root : document;
                const mapElements = scope.matches?.('[data-vendor-map]')
                    ? [scope]
                    : Array.from(scope.querySelectorAll('[data-vendor-map]'));

                mapElements.forEach((mapElement) => {
                    if (mapElement.dataset.vendorMapBound === '1' || typeof window.L === 'undefined') {
                        return;
                    }

                    const container = mapElement.closest('.module-page--vendors') || document;
                    const latitudeInput = container.querySelector('[data-location-field="latitude"]');
                    const longitudeInput = container.querySelector('[data-location-field="longitude"]');
                    const hintNode = mapElement.parentElement?.querySelector('[data-vendor-map-hint]') || null;
                    if (!latitudeInput || !longitudeInput) {
                        return;
                    }

                    const defaultCenter = [-2.5489, 118.0149];
                    const map = L.map(mapElement, { zoomControl: true }).setView(defaultCenter, 5);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const vendorIcon = L.divIcon({
                        className: 'vendor-map-pin',
                        html: '<span class="vendor-map-pin__inner"><i class="fa-solid fa-store" aria-hidden="true"></i></span>',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    });

                    let marker = null;

                    const setHint = (message, tone = 'neutral') => {
                        if (!hintNode) {
                            return;
                        }
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
                        if (value === null || value === undefined || String(value).trim() === '') {
                            return null;
                        }
                        const parsed = Number.parseFloat(String(value));
                        return Number.isFinite(parsed) ? parsed : null;
                    };

                    const isCoordinateValid = (latitude, longitude) => latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;

                    const clearMarker = () => {
                        if (marker) {
                            map.removeLayer(marker);
                            marker = null;
                        }
                        map.setView(defaultCenter, 5);
                    };

                    const syncMapMarker = () => {
                        const rawLatitude = String(latitudeInput.value || '').trim();
                        const rawLongitude = String(longitudeInput.value || '').trim();
                        const latitude = parseCoordinate(rawLatitude);
                        const longitude = parseCoordinate(rawLongitude);

                        if (rawLatitude === '' && rawLongitude === '') {
                            clearMarker();
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
                            marker = L.marker(latLng, { icon: vendorIcon }).addTo(map);
                        } else {
                            marker.setLatLng(latLng);
                        }

                        map.setView(latLng, 15);
                        setHint('Lokasi vendor berhasil ditampilkan pada map.', 'success');
                    };

                    const bindCoordinateEvents = (input) => {
                        ['input', 'change', 'blur'].forEach((eventName) => {
                            input.addEventListener(eventName, syncMapMarker);
                        });
                    };

                    bindCoordinateEvents(latitudeInput);
                    bindCoordinateEvents(longitudeInput);

                    map.on('click', (event) => {
                        latitudeInput.value = event.latlng.lat.toFixed(7);
                        longitudeInput.value = event.latlng.lng.toFixed(7);
                        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                        longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    });

                    mapElement.dataset.vendorMapBound = '1';
                    window.setTimeout(() => map.invalidateSize(), 150);
                    syncMapMarker();
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                initVendorLocationMap(document);
            });
        </script>
    @endpush
@endonce
