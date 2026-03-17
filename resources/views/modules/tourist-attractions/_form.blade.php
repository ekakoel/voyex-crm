@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $touristAttraction = $touristAttraction ?? null;
    $destinations = $destinations ?? collect();
@endphp

<div class="space-y-4" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
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
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Contoh: 120 berarti estimasi kunjungan 2 jam.</p>
        @error('ideal_visit_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div>
            <x-money-input
                label="Entrance Fee (per pax)"
                name="entrance_fee_per_pax"
                :value="old('entrance_fee_per_pax', $touristAttraction->entrance_fee_per_pax ?? '')"
                min="0"
                step="0.01"
            />
            @error('entrance_fee_per_pax') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Other Fee Label</label>
            <input
                name="other_fee_label"
                maxlength="100"
                value="{{ old('other_fee_label', $touristAttraction->other_fee_label ?? '') }}"
                placeholder="Contoh: Camera Fee / Guide Local"
                class="mt-1 dark:border-gray-600 app-input"
            >
            @error('other_fee_label') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <x-money-input
                label="Other Fee (per pax)"
                name="other_fee_per_pax"
                :value="old('other_fee_per_pax', $touristAttraction->other_fee_per_pax ?? '')"
                min="0"
                step="0.01"
            />
            @error('other_fee_per_pax') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Currency</label>
            <input
                name="currency"
                maxlength="3"
                value="{{ old('currency', $touristAttraction->currency ?? 'IDR') }}"
                class="mt-1 uppercase dark:border-gray-600 app-input"
                required
            >
            @error('currency') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Destination</label>
            <select id="destination_id" data-location-field="destination_id" name="destination_id" class="mt-1 dark:border-gray-600 app-input">
                <option value="">Select destination</option>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}"
                        data-city="{{ $destination->city ?? '' }}"
                        data-province="{{ $destination->province ?? '' }}"
                        @selected((string) old('destination_id', $touristAttraction->destination_id ?? '') === (string) $destination->id)>
                        {{ $destination->province ?: $destination->name }}
                    </option>
                @endforeach
            </select>
            @error('destination_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location</label>
            <input id="location" data-location-field="location" name="location" value="{{ old('location', $touristAttraction->location ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('location') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City</label>
            <input id="city" data-location-field="city" name="city" value="{{ old('city', $touristAttraction->city ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('city') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province</label>
            <input id="province" data-location-field="province" name="province" value="{{ old('province', $touristAttraction->province ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('province') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Country</label>
            <input data-location-field="country" name="country" value="{{ old('country', $touristAttraction->country ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Timezone</label>
            <input data-location-field="timezone" name="timezone" value="{{ old('timezone', $touristAttraction->timezone ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
            <input data-location-field="address" name="address" value="{{ old('address', $touristAttraction->address ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Google Maps URL</label>
        <div class="mt-1 space-y-2">
            <input id="google_maps_url" data-location-field="google_maps_url" name="google_maps_url" type="url" value="{{ old('google_maps_url', $touristAttraction->google_maps_url ?? '') }}" placeholder="https://maps.google.com/..." class="app-input">
            <div class="flex justify-end">
                <button type="button" data-location-autofill-trigger class="btn-outline-sm">Auto Fill</button>
            </div>
        </div>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Paste Google Maps link to auto-fill all location fields.</p>
        @error('google_maps_url') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Latitude</label>
            <input id="latitude" data-location-field="latitude" name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $touristAttraction->latitude ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('latitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Longitude</label>
            <input id="longitude" data-location-field="longitude" name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $touristAttraction->longitude ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('longitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <p data-location-status class="hidden text-xs"></p>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
        <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $touristAttraction->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images</label>
        <div id="tourist-attraction-gallery-preview"
            class="mt-2 grid grid-cols-3 gap-2"
            data-remove-endpoint-template="{{ isset($touristAttraction) ? route('tourist-attractions.gallery-images.remove', $touristAttraction) : '' }}"
            data-csrf-token="{{ csrf_token() }}">
            @if (!empty($touristAttraction?->gallery_images))
                @foreach ($touristAttraction->gallery_images as $image)
                    <div class="tourist-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                        <button
                            type="button"
                             class="tourist-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                            title="Remove image"
                            aria-label="Remove image">
                            X
                        </button>
                        <div class="w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="aspect-ratio: 4 / 3;">
                            <img
                                src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                alt="Attraction gallery"
                                class="h-full w-full object-cover">
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <input id="tourist-attraction-gallery-input" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p id="tourist-attraction-gallery-limit-note" class="mt-1 hidden text-xs text-amber-600"></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload gambar tanpa batas jenis/ukuran. Saat edit, klik X untuk menghapus per gambar, dan upload baru untuk menambah/mengganti. Semua gambar diproses crop rasio 3:2 dan dibuat thumbnail.</p>
        @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('removed_gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
                const limitNote = document.getElementById('tourist-attraction-gallery-limit-note');
                if (!input || !preview) return;

                const renderNewUploads = () => {
                    preview.querySelectorAll('.tourist-gallery-new-item').forEach((node) => node.remove());
                    if (limitNote) {
                        limitNote.classList.add('hidden');
                        limitNote.textContent = '';
                    }

                    const files = Array.from(input.files || []);
                    const filesToRender = files;

                    filesToRender.forEach((file) => {
                        if (!String(file.type || '').startsWith('image/')) {
                            return;
                        }
                        const url = URL.createObjectURL(file);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'tourist-gallery-new-item overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
                        const media = document.createElement('div');
                        media.className = 'w-full overflow-hidden bg-gray-100 dark:bg-gray-800';
                        media.style.aspectRatio = '4 / 3';
                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = 'Attraction gallery preview';
                        image.className = 'h-full w-full object-cover';
                        image.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
                        media.appendChild(image);
                        wrapper.appendChild(media);
                        const badge = document.createElement('div');
                        badge.className = 'border-t border-indigo-200 px-2 py-1 text-[11px] font-medium text-indigo-700 dark:border-indigo-700/60 dark:text-indigo-300';
                        badge.textContent = 'New upload';
                        wrapper.appendChild(badge);
                        preview.appendChild(wrapper);
                    });
                };

                input.addEventListener('change', renderNewUploads);

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
                    } catch (_) {
                        button.disabled = false;
                        button.classList.remove('opacity-70');
                        alert('Gagal menghapus image. Silakan coba lagi.');
                    }
                });
            })();
        </script>
    @endpush
@endonce



