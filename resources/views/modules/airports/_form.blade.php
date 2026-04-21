@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $airport = $airport ?? null;
    $destinations = $destinations ?? collect();
@endphp

<div class="space-y-4" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Basic Information') }}</p>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Code') }}</label>
            <input name="code" value="{{ old('code', $airport->code ?? '') }}"
                class="mt-1 uppercase dark:border-gray-600 app-input"
                required>
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Airport Name') }}</label>
            <input name="name" value="{{ old('name', $airport->name ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input"
                required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    @include('components.map-standard-section', [
        'title' => 'Map & Location Standard',
        'mapPartial' => 'modules.airports.partials._location-map',
        'mapValue' => old('google_maps_url', $airport->google_maps_url ?? ''),
        'latitudeValue' => old('latitude', $airport->latitude ?? ''),
        'longitudeValue' => old('longitude', $airport->longitude ?? ''),
        'addressValue' => old('address', $airport->address ?? ''),
        'cityValue' => old('city', $airport->city ?? ''),
        'provinceValue' => old('province', $airport->province ?? ''),
        'countryValue' => old('country', $airport->country ?? ''),
        'destinationValue' => old('destination_id', $airport->destination_id ?? ''),
        'destinations' => $destinations,
    ])
    <input type="hidden" name="location" data-location-field="location" value="{{ old('location', $airport->location ?? '') }}">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Timezone') }}</label>
        <input name="timezone" data-location-field="timezone" value="{{ old('timezone', $airport->timezone ?? '') }}"
            class="mt-1 dark:border-gray-600 app-input">
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Additional Notes') }}</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Notes') }}</label>
        <textarea name="notes" rows="3"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $airport->notes ?? '') }}</textarea>
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $airport->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">{{ __('Active') }}</span>
    </div>

    <div class="flex items-center gap-2">
        <button  class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('airports.index') }}"
             class="btn-secondary">Cancel</a>
    </div>
</div>



