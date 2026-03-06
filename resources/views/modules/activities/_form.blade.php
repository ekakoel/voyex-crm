@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $activity = $activity ?? null;
    $activityTypes = $activityTypes ?? [];
    $standardActivityTypes = $standardActivityTypes ?? [];
    $selectedActivityType = (string) old('activity_type', $activity->activity_type ?? '');
    $isLegacyActivityType = $selectedActivityType !== '' && ! in_array($selectedActivityType, $standardActivityTypes, true);
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Vendor</label>
            <select name="vendor_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                <option value="">Select vendor</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $activity->vendor_id ?? 0) === (int) $vendor->id)>
                        {{ $vendor->name }}{{ ($vendor->city || $vendor->province) ? ' ('.trim(($vendor->city ?? '-').' / '.($vendor->province ?? '-')).')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('vendor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Activity Name</label>
            <input name="name" value="{{ old('name', $activity->name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Activity Type</label>
            <select name="activity_type" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                <option value="">Select activity type</option>
                @foreach ($activityTypes as $type)
                    @php
                        $isLegacyOption = ! in_array($type, $standardActivityTypes, true);
                    @endphp
                    <option value="{{ $type }}" @selected($selectedActivityType === $type)>
                        {{ ucwords(str_replace('_', ' ', $type)) }}{{ $isLegacyOption ? ' (Legacy)' : '' }}
                    </option>
                @endforeach
            </select>
            @if ($isLegacyActivityType)
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    Legacy type detected: "{{ ucwords(str_replace('_', ' ', $selectedActivityType)) }}". Please switch to a standard type if possible.
                </p>
            @endif
            @error('activity_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Duration (minutes)</label>
            <input name="duration_minutes" type="number" min="15" max="1440" value="{{ old('duration_minutes', $activity->duration_minutes ?? 120) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            @error('duration_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Capacity Min</label>
            <input name="capacity_min" type="number" min="1" value="{{ old('capacity_min', $activity->capacity_min ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('capacity_min') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Capacity Max</label>
            <input name="capacity_max" type="number" min="1" value="{{ old('capacity_max', $activity->capacity_max ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('capacity_max') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contract Price (per pax)</label>
            <input name="contract_price" type="number" min="0" step="0.01" value="{{ old('contract_price', $activity->contract_price ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('contract_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Agent Price (per pax)</label>
            <input name="agent_price" type="number" min="0" step="0.01" value="{{ old('agent_price', $activity->agent_price ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('agent_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Currency</label>
            <input name="currency" maxlength="3" value="{{ old('currency', $activity->currency ?? 'IDR') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 uppercase text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            @error('currency') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Benefits</label>
        <textarea name="benefits" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('benefits', $activity->benefits ?? '') }}</textarea>
        @error('benefits') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Descriptions</label>
        <textarea name="descriptions" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('descriptions', $activity->descriptions ?? '') }}</textarea>
        @error('descriptions') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Includes</label>
            <textarea name="includes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('includes', $activity->includes ?? '') }}</textarea>
            @error('includes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Excludes</label>
            <textarea name="excludes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('excludes', $activity->excludes ?? '') }}</textarea>
            @error('excludes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Cancellation Policy</label>
        <textarea name="cancellation_policy" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('cancellation_policy', $activity->cancellation_policy ?? '') }}</textarea>
        @error('cancellation_policy') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $activity->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images (1-3)</label>
        <div id="activity-gallery-preview"
            class="mt-2 grid grid-cols-3 gap-2"
            data-remove-endpoint-template="{{ isset($activity) ? route('activities.gallery-images.remove', $activity) : '' }}"
            data-csrf-token="{{ csrf_token() }}">
            @if (!empty($activity?->gallery_images))
                @foreach ($activity->gallery_images as $image)
                    <div class="activity-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                        <button
                            type="button"
                            class="activity-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                            title="Remove image"
                            aria-label="Remove image">
                            X
                        </button>
                        <div class="w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="aspect-ratio: 4 / 3;">
                            <img
                                src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                alt="Activity gallery"
                                class="h-full w-full object-cover">
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <input id="activity-gallery-input" type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p id="activity-gallery-limit-note" class="mt-1 hidden text-xs text-amber-600"></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload 1 sampai 3 gambar. Saat edit, klik X untuk hapus per gambar dan upload baru akan ditambahkan ke gallery.</p>
        @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('removed_gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $activity->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $buttonLabel }}</button>
        <a href="{{ route('activities.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const input = document.getElementById('activity-gallery-input');
                const preview = document.getElementById('activity-gallery-preview');
                const limitNote = document.getElementById('activity-gallery-limit-note');
                if (!input || !preview) return;

                const renderNewUploads = () => {
                    preview.querySelectorAll('.activity-gallery-new-item').forEach((node) => node.remove());
                    if (limitNote) {
                        limitNote.classList.add('hidden');
                        limitNote.textContent = '';
                    }

                    const existingCount = preview.querySelectorAll('.activity-gallery-existing-item').length;
                    const maxNewAllowed = Math.max(0, 3 - existingCount);
                    const files = Array.from(input.files || []);
                    const filesToRender = files.slice(0, maxNewAllowed);

                    if (files.length > filesToRender.length && limitNote) {
                        limitNote.textContent = `Maksimal total 3 gambar. Hanya ${filesToRender.length} gambar baru yang dipreview berdasarkan slot tersedia.`;
                        limitNote.classList.remove('hidden');
                    }

                    filesToRender.forEach((file) => {
                        if (!String(file.type || '').startsWith('image/')) return;
                        const url = URL.createObjectURL(file);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'activity-gallery-new-item overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
                        const media = document.createElement('div');
                        media.className = 'w-full overflow-hidden bg-gray-100 dark:bg-gray-800';
                        media.style.aspectRatio = '4 / 3';
                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = 'Activity gallery preview';
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
                    const button = event.target.closest('.activity-gallery-remove-btn');
                    if (!button) return;
                    const wrapper = button.closest('.activity-gallery-existing-item');
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
