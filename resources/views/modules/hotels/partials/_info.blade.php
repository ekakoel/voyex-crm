@php
    $hotel = $hotel ?? null;
    $destinations = $destinations ?? collect();
    $buttonLabel = $buttonLabel ?? 'Save';
    $showActions = $showActions ?? true;
    $statusValue = strtolower((string) old('status', $hotel->status ?? 'active'));
@endphp

<div class="space-y-6 hotel-form" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div class="app-card p-5 space-y-5">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                @php
                    $coverValue = (string) old('existing_cover', $hotel->cover ?? '');
                    $coverStoredPath = trim(str_replace('\\', '/', $coverValue), '/');
                    if (\Illuminate\Support\Str::startsWith($coverStoredPath, 'storage/')) {
                        $coverStoredPath = \Illuminate\Support\Str::after($coverStoredPath, 'storage/');
                    }
                    if (
                        $coverStoredPath !== '' &&
                        ! \Illuminate\Support\Str::startsWith($coverStoredPath, ['http://', 'https://', 'hotels/cover/', 'hotels/covers/']) &&
                        ! \Illuminate\Support\Str::contains($coverStoredPath, '/')
                    ) {
                        $coverCandidate = 'hotels/cover/' . $coverStoredPath;
                        $coversCandidate = 'hotels/covers/' . $coverStoredPath;
                        $coverStoredPath = \Illuminate\Support\Facades\Storage::disk('public')->exists($coverCandidate)
                            ? $coverCandidate
                            : (\Illuminate\Support\Facades\Storage::disk('public')->exists($coversCandidate) ? $coversCandidate : $coverCandidate);
                    }
                    $coverIsExternal = \Illuminate\Support\Str::startsWith($coverStoredPath, ['http://', 'https://']);
                    $coverThumb = $coverStoredPath !== '' && ! $coverIsExternal
                        ? \App\Support\ImageThumbnailGenerator::resolvePublicUrl($coverStoredPath)
                        : $coverStoredPath;
                    $coverFull = $coverStoredPath !== '' && ! $coverIsExternal
                        ? \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($coverStoredPath)
                        : $coverStoredPath;
                @endphp
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Cover (Upload Image)</label>
                <div class="hotel-cover-preview room-cover-preview image-preview mt-2 flex w-full items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40">
                    <div class="image-preview-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                        <span>Select image to preview</span>
                    </div>
                    @if ($coverStoredPath !== '')
                        <img src="{{ $coverThumb }}" onload="this.classList.add('image-loaded');var p=this.closest('.image-preview');if(p){p.classList.add('has-image');}" onerror="if(this.dataset.fallbackApplied){var p=this.closest('.image-preview');if(p){p.classList.remove('has-image');}this.remove();}else{this.dataset.fallbackApplied='1';this.src='{{ $coverFull }}';}" alt="Hotel cover preview" class="h-full w-full object-cover">
                    @endif
                </div>
                <div data-hotel-info-cover class="mt-2 space-y-2">
                    <input type="file" name="cover_file" accept="image/*" class="hotel-cover-input w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <input type="hidden" name="existing_cover" value="{{ $coverStoredPath }}">
                </div>
                @error('cover_file') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                @error('cover') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
                <select name="status" class="mt-1 app-input" required>
                    @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Hotel Name</label>
                <input name="name" value="{{ old('name', $hotel->name ?? '') }}" class="mt-1 app-input" required>
            </div>
            <input type="hidden" name="region" value="{{ old('region', $hotel->region ?? '') }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Person</label>
                <input name="contact_person" value="{{ old('contact_person', $hotel->contact_person ?? '') }}" class="mt-1 app-input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Phone</label>
                <input name="phone" value="{{ old('phone', $hotel->phone ?? '') }}" class="mt-1 app-input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Website</label>
                <input name="web" value="{{ old('web', $hotel->web ?? '') }}" class="mt-1 app-input" type="url" placeholder="https://...">
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Check-in Time</label>
                    <input name="check_in_time" type="time" value="{{ old('check_in_time', isset($hotel->check_in_time) ? substr((string) $hotel->check_in_time, 0, 5) : '') }}" class="mt-1 app-input" placeholder="14:00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Check-out Time</label>
                    <input name="check_out_time" type="time" value="{{ old('check_out_time', isset($hotel->check_out_time) ? substr((string) $hotel->check_out_time, 0, 5) : '') }}" class="mt-1 app-input" placeholder="12:00">
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Min Stay (days)</label>
                    <input name="min_stay" value="{{ old('min_stay', $hotel->min_stay ?? '') }}" class="mt-1 app-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Max Stay (days)</label>
                    <input name="max_stay" value="{{ old('max_stay', $hotel->max_stay ?? '') }}" class="mt-1 app-input">
                </div>
             </div>
             <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Airport Distance (km)</label>
                    <input name="airport_distance" value="{{ old('airport_distance', $hotel->airport_distance ?? '') }}" class="mt-1 app-input" type="number" min="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Airport Duration (minutes)</label>
                    <input name="airport_duration" value="{{ old('airport_duration', $hotel->airport_duration ?? '') }}" class="mt-1 app-input" type="number" min="0">
                </div>
            </div>
        </div>
        @include('components.map-standard-section', [
            'title' => 'Map & Location Standard',
            'mapPartial' => 'modules.hotels.partials._location-map',
            'mapFieldName' => 'map',
            'mapFieldErrorKey' => 'map',
            'mapValue' => old('map', $hotel->map ?? ''),
            'latitudeValue' => old('latitude', $hotel->latitude ?? ''),
            'longitudeValue' => old('longitude', $hotel->longitude ?? ''),
            'latitudePlaceholder' => '-8.409518',
            'longitudePlaceholder' => '115.188919',
            'addressValue' => old('address', $hotel->address ?? ''),
            'addressRequired' => true,
            'cityValue' => old('city', $hotel->city ?? ''),
            'provinceValue' => old('province', $hotel->province ?? ''),
            'countryValue' => old('country', $hotel->country ?? ''),
            'destinationValue' => old('destination_id', $hotel->destination_id ?? ''),
            'destinations' => $destinations,
        ])

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
                <textarea name="description" rows="3" class="mt-1 app-input">{{ old('description', $hotel->description ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Facility</label>
                <textarea name="facility" rows="3" class="mt-1 app-input">{{ old('facility', $hotel->facility ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Additional Info</label>
                <textarea name="additional_info" rows="3" class="mt-1 app-input">{{ old('additional_info', $hotel->additional_info ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Cancellation Policy</label>
                <textarea name="cancellation_policy" rows="3" class="mt-1 app-input">{{ old('cancellation_policy', $hotel->cancellation_policy ?? '') }}</textarea>
            </div>
        </div>
    </div>

    @if ($showActions)
        <div class="flex justify-end">
            <button type="submit" class="btn-primary">{{ $buttonLabel }}</button>
        </div>
    @endif
</div>












