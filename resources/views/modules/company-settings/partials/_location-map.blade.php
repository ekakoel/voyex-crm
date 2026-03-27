<div class="space-y-2">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Location on Map (open map)</h3>
    <div class="h-[320px] overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-company-map></div>
    <p class="text-xs text-gray-500 dark:text-gray-400" data-company-map-hint></p>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            function initCompanyLocationMap(root = document) {
                const scope = root instanceof Element || root instanceof Document ? root : document;
                const mapElements = scope.matches?.('[data-company-map]')
                    ? [scope]
                    : Array.from(scope.querySelectorAll('[data-company-map]'));

                mapElements.forEach((mapElement) => {
                    if (mapElement.dataset.companyMapBound === '1' || typeof window.L === 'undefined') {
                        return;
                    }

                    const container = mapElement.closest('[data-location-autofill]') || document;
                    const latitudeInput = container.querySelector('[data-location-field="latitude"]');
                    const longitudeInput = container.querySelector('[data-location-field="longitude"]');
                    const hintNode = mapElement.parentElement?.querySelector('[data-company-map-hint]') || null;
                    if (! latitudeInput || ! longitudeInput) {
                        return;
                    }

                    const defaultCenter = [-2.5489, 118.0149];
                    const map = L.map(mapElement, { zoomControl: true }).setView(defaultCenter, 5);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    let marker = null;
                    const icon = L.divIcon({
                        className: 'company-map-pin',
                        html: '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-teal-700 text-white"><i class="fa-solid fa-building" aria-hidden="true"></i></span>',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    });

                    const setHint = (text, type = 'neutral') => {
                        if (! hintNode) return;
                        hintNode.textContent = text;
                        hintNode.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-rose-600', 'dark:text-rose-400', 'text-emerald-600', 'dark:text-emerald-400');
                        if (type === 'error') {
                            hintNode.classList.add('text-rose-600', 'dark:text-rose-400');
                            return;
                        }
                        if (type === 'success') {
                            hintNode.classList.add('text-emerald-600', 'dark:text-emerald-400');
                            return;
                        }
                        hintNode.classList.add('text-gray-500', 'dark:text-gray-400');
                    };

                    const parseCoordinate = (value) => {
                        const parsed = Number.parseFloat(String(value || '').trim());
                        return Number.isFinite(parsed) ? parsed : null;
                    };

                    const syncMarker = () => {
                        const latitude = parseCoordinate(latitudeInput.value);
                        const longitude = parseCoordinate(longitudeInput.value);

                        if (latitude === null || longitude === null || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
                            if (marker) {
                                map.removeLayer(marker);
                                marker = null;
                            }
                            map.setView(defaultCenter, 5);
                            setHint('Koordinat belum valid. Isi dari Map URL atau klik pada map.', 'error');
                            return;
                        }

                        const latLng = [latitude, longitude];
                        if (! marker) {
                            marker = L.marker(latLng, { icon }).addTo(map);
                        } else {
                            marker.setLatLng(latLng);
                        }
                        map.setView(latLng, 15);
                        setHint('Lokasi berhasil ditampilkan pada map.', 'success');
                    };

                    ['input', 'change', 'blur'].forEach((eventName) => {
                        latitudeInput.addEventListener(eventName, syncMarker);
                        longitudeInput.addEventListener(eventName, syncMarker);
                    });

                    map.on('click', (event) => {
                        latitudeInput.value = event.latlng.lat.toFixed(7);
                        longitudeInput.value = event.latlng.lng.toFixed(7);
                        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                        longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    });

                    mapElement.dataset.companyMapBound = '1';
                    window.setTimeout(() => map.invalidateSize(), 120);
                    syncMarker();
                });
            }

            document.addEventListener('DOMContentLoaded', () => initCompanyLocationMap(document));
        </script>
    @endpush
@endonce
