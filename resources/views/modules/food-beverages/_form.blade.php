@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $foodBeverage = $foodBeverage ?? null;
    $serviceTypes = $serviceTypes ?? [];
    $standardServiceTypes = $standardServiceTypes ?? [];
    $selectedServiceType = (string) old('service_type', $foodBeverage->service_type ?? '');
    $isLegacyServiceType = $selectedServiceType !== '' && ! in_array($selectedServiceType, $standardServiceTypes, true);
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Vendor</label>
            <select name="vendor_id" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">Select vendor</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $foodBeverage->vendor_id ?? 0) === (int) $vendor->id)>
                        {{ $vendor->name }}{{ ($vendor->city || $vendor->province) ? ' ('.trim(($vendor->city ?? '-').' / '.($vendor->province ?? '-')).')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('vendor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Service Name</label>
            <input name="name" value="{{ old('name', $foodBeverage->name ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Service Type</label>
            <select name="service_type" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">Select service type</option>
                @foreach ($serviceTypes as $type)
                    @php
                        $isLegacyOption = ! in_array($type, $standardServiceTypes, true);
                    @endphp
                    <option value="{{ $type }}" @selected($selectedServiceType === $type)>
                        {{ ucwords(str_replace('_', ' ', $type)) }}{{ $isLegacyOption ? ' (Legacy)' : '' }}
                    </option>
                @endforeach
            </select>
            @if ($isLegacyServiceType)
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    Legacy type detected: "{{ ucwords(str_replace('_', ' ', $selectedServiceType)) }}". Please switch to a standard type if possible.
                </p>
            @endif
            @error('service_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Duration (minutes)</label>
            <input name="duration_minutes" type="number" min="15" max="1440" value="{{ old('duration_minutes', $foodBeverage->duration_minutes ?? 60) }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('duration_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Meal Period</label>
            <input name="meal_period" maxlength="50" value="{{ old('meal_period', $foodBeverage->meal_period ?? '') }}" class="mt-1 dark:border-gray-600 app-input" placeholder="Breakfast / Lunch / Dinner">
            @error('meal_period') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Currency</label>
            <input name="currency" maxlength="3" value="{{ old('currency', $foodBeverage->currency ?? 'IDR') }}" class="mt-1 uppercase dark:border-gray-600 app-input" required>
            @error('currency') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <x-money-input
                label="Contract Price (per pax)"
                name="contract_price"
                :value="old('contract_price', $foodBeverage->contract_price ?? '')"
                min="0"
                step="0.01"
            />
            @error('contract_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <x-money-input
                label="Agent Price (per pax)"
                name="agent_price"
                :value="old('agent_price', $foodBeverage->agent_price ?? '')"
                min="0"
                step="0.01"
            />
            @error('agent_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Menu Highlights</label>
        <textarea name="menu_highlights" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('menu_highlights', $foodBeverage->menu_highlights ?? '') }}</textarea>
        @error('menu_highlights') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $foodBeverage->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images</label>
        <div id="food-beverage-gallery-preview"
            class="mt-2 grid grid-cols-3 gap-2"
            data-remove-endpoint-template="{{ isset($foodBeverage) ? route('food-beverages.gallery-images.remove', $foodBeverage) : '' }}"
            data-csrf-token="{{ csrf_token() }}">
            @if (!empty($foodBeverage?->gallery_images))
                @foreach ($foodBeverage->gallery_images as $image)
                    <div class="food-beverage-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                        <button
                            type="button"
                             class="food-beverage-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                            title="Remove image"
                            aria-label="Remove image">
                            X
                        </button>
                        <div class="w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="aspect-ratio: 4 / 3;">
                            <img
                                src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                alt="F&B gallery"
                                class="h-full w-full object-cover">
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <input id="food-beverage-gallery-input" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p id="food-beverage-gallery-limit-note" class="mt-1 hidden text-xs text-amber-600"></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload gambar tanpa batas jenis/ukuran. Saat edit, klik X untuk hapus per gambar dan upload baru akan ditambahkan ke gallery. Semua gambar diproses crop rasio 3:2 dan dibuat thumbnail.</p>
        @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('removed_gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $foodBeverage->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button  class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('food-beverages.index') }}"  class="btn-secondary">Cancel</a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const input = document.getElementById('food-beverage-gallery-input');
                const preview = document.getElementById('food-beverage-gallery-preview');
                const limitNote = document.getElementById('food-beverage-gallery-limit-note');
                if (!input || !preview) return;

                const renderNewUploads = () => {
                    preview.querySelectorAll('.food-beverage-gallery-new-item').forEach((node) => node.remove());
                    if (limitNote) {
                        limitNote.classList.add('hidden');
                        limitNote.textContent = '';
                    }

                    const files = Array.from(input.files || []);
                    const filesToRender = files;

                    filesToRender.forEach((file) => {
                        if (!String(file.type || '').startsWith('image/')) return;
                        const url = URL.createObjectURL(file);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'food-beverage-gallery-new-item overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
                        const media = document.createElement('div');
                        media.className = 'w-full overflow-hidden bg-gray-100 dark:bg-gray-800';
                        media.style.aspectRatio = '4 / 3';
                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = 'F&B gallery preview';
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
                    const button = event.target.closest('.food-beverage-gallery-remove-btn');
                    if (!button) return;
                    const wrapper = button.closest('.food-beverage-gallery-existing-item');
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



