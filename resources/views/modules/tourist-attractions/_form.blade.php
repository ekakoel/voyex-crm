@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $touristAttraction = $touristAttraction ?? null;
@endphp

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
        <input name="name" value="{{ old('name', $touristAttraction->name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
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
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            required
        >
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Contoh: 120 berarti estimasi kunjungan 2 jam.</p>
        @error('ideal_visit_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location</label>
            <input id="location" name="location" value="{{ old('location', $touristAttraction->location ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('location') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City</label>
            <input id="city" name="city" value="{{ old('city', $touristAttraction->city ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('city') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province</label>
            <input id="province" name="province" value="{{ old('province', $touristAttraction->province ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('province') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <p id="location-source-indicator" class="hidden text-xs text-emerald-600 dark:text-emerald-400">Auto-filled from map.</p>
    <div>
        <button type="button" id="toggle-manual-location" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            Edit manual
        </button>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Google Maps URL</label>
        <input id="google_maps_url" name="google_maps_url" type="url" value="{{ old('google_maps_url', $touristAttraction->google_maps_url ?? '') }}" placeholder="https://maps.google.com/..." class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Paste Google Maps link to auto-fill coordinates.</p>
        @error('google_maps_url') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Latitude</label>
            <input id="latitude" name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $touristAttraction->latitude ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('latitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Longitude</label>
            <input id="longitude" name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $touristAttraction->longitude ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('longitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="space-y-2">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pick Location on Map</p>
        <div id="tourist-attraction-map" class="h-72 w-full rounded-lg border border-gray-300"></div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Click on map to set latitude and longitude.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
        <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $touristAttraction->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images (1-3)</label>
        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload 1 sampai 3 gambar. Saat edit, upload ulang akan mengganti gallery lama.</p>
        @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

        @if (!empty($touristAttraction?->gallery_images))
            <div class="mt-2 grid grid-cols-3 gap-2">
                @foreach ($touristAttraction->gallery_images as $image)
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <img
                            src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                            onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                            alt="Attraction gallery"
                            class="h-20 w-full object-cover">
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $touristAttraction->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $buttonLabel }}</button>
        <a href="{{ route('tourist-attractions.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</a>
    </div>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function () {
                const mapElement = document.getElementById('tourist-attraction-map');
                if (!mapElement || typeof L === 'undefined') return;

                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const cityInput = document.getElementById('city');
                const provinceInput = document.getElementById('province');
                const locationInput = document.getElementById('location');
                const sourceIndicator = document.getElementById('location-source-indicator');
                const toggleManualButton = document.getElementById('toggle-manual-location');
                const googleMapsUrlInput = document.getElementById('google_maps_url');
                const initialLat = parseFloat(latInput.value);
                const initialLng = parseFloat(lngInput.value);
                const hasInitialPoint = Number.isFinite(initialLat) && Number.isFinite(initialLng);

                const map = L.map(mapElement).setView(hasInitialPoint ? [initialLat, initialLng] : [-6.2, 106.816666], hasInitialPoint ? 14 : 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                let marker = null;
                const locationFields = [locationInput, cityInput, provinceInput].filter(Boolean);
                const setLocationReadOnly = (locked) => {
                    locationFields.forEach((input) => {
                        input.readOnly = locked;
                        input.classList.toggle('bg-gray-100', locked);
                        input.classList.toggle('dark:bg-gray-700', locked);
                    });
                    if (toggleManualButton) {
                        toggleManualButton.dataset.locked = locked ? '1' : '0';
                        toggleManualButton.textContent = locked ? 'Edit manual' : 'Kunci kembali';
                    }
                    if (sourceIndicator) {
                        sourceIndicator.classList.toggle('hidden', !locked);
                    }
                };

                const setPoint = (lat, lng) => {
                    latInput.value = lat.toFixed(7);
                    lngInput.value = lng.toFixed(7);
                    if (!marker) {
                        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                        marker.on('dragend', (event) => {
                            const position = event.target.getLatLng();
                            setPoint(position.lat, position.lng);
                        });
                    } else {
                        marker.setLatLng([lat, lng]);
                    }
                };

                const reverseGeocode = async (lat, lng) => {
                    try {
                        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
                        const data = await response.json();
                        const address = data?.address || {};
                        const city = address.city || address.town || address.village || address.county || '';
                        const province = address.state || '';
                        if (cityInput && city) cityInput.value = city;
                        if (provinceInput && province) provinceInput.value = province;
                        if (locationInput) {
                            const parts = [cityInput?.value || '', provinceInput?.value || ''].filter(Boolean);
                            if (parts.length) {
                                locationInput.value = parts.join(', ');
                            } else if (data?.display_name) {
                                locationInput.value = String(data.display_name).slice(0, 255);
                            }
                        }
                        setLocationReadOnly(true);
                    } catch (_) {
                        // ignore reverse geocode failure
                    }
                };

                const extractCoordinatesFromGoogleMapsUrl = (url) => {
                    let match = url.match(/@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/);
                    if (match) return [parseFloat(match[1]), parseFloat(match[2])];

                    match = url.match(/!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/);
                    if (match) return [parseFloat(match[1]), parseFloat(match[2])];

                    try {
                        const parsed = new URL(url);
                        const q = parsed.searchParams.get('q') || parsed.searchParams.get('query');
                        if (!q) return null;
                        match = q.match(/(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/);
                        if (!match) return null;
                        return [parseFloat(match[1]), parseFloat(match[2])];
                    } catch (_) {
                        return null;
                    }
                };

                if (hasInitialPoint) {
                    setPoint(initialLat, initialLng);
                }

                map.on('click', async (event) => {
                    setPoint(event.latlng.lat, event.latlng.lng);
                    await reverseGeocode(event.latlng.lat, event.latlng.lng);
                });

                if (googleMapsUrlInput) {
                    googleMapsUrlInput.addEventListener('change', async () => {
                        const coordinates = extractCoordinatesFromGoogleMapsUrl(googleMapsUrlInput.value);
                        if (!coordinates) return;

                        const [lat, lng] = coordinates;
                        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                        setPoint(lat, lng);
                        await reverseGeocode(lat, lng);
                        map.setView([lat, lng], 14);
                    });
                }

                if (toggleManualButton) {
                    toggleManualButton.addEventListener('click', () => {
                        const isLocked = toggleManualButton.dataset.locked === '1';
                        setLocationReadOnly(!isLocked);
                    });
                }
            })();
        </script>
    @endpush
@endonce


