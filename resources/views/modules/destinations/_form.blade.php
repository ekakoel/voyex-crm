@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $destination = $destination ?? null;
@endphp

<div class="space-y-4" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Code') }}</label>
            <input name="code" value="{{ old('code', $destination->code ?? '') }}"
                class="mt-1 uppercase dark:border-gray-600 app-input"
                required>
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Province (Destination Basis)') }}</label>
            <input name="province" data-location-field="province" value="{{ old('province', $destination->province ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input"
                required>
            
            @error('province') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Slug') }}</label>
            <input name="slug" value="{{ old('slug', $destination->slug ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input"
                placeholder="{{ ui_phrase('auto-generated if empty') }}">
            @error('slug') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Timezone') }}</label>
            <input name="timezone" data-location-field="timezone" value="{{ old('timezone', $destination->timezone ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input">
        </div>
    </div>

    @include('components.map-standard-section', [
        'title' => ui_phrase('Map & Location Standard'),
        'mapPartial' => 'modules.destinations.partials._location-map',
        'mapValue' => old('google_maps_url', $destination->google_maps_url ?? ''),
        'latitudeValue' => old('latitude', $destination->latitude ?? ''),
        'longitudeValue' => old('longitude', $destination->longitude ?? ''),
        'addressValue' => old('address', $destination->address ?? ''),
        'cityValue' => old('city', $destination->city ?? ''),
        'provinceValue' => old('province', $destination->province ?? ''),
        'countryValue' => old('country', $destination->country ?? ''),
        'showDestinationField' => false,
    ])
    <input type="hidden" name="location" data-location-field="location" value="{{ old('location', $destination->location ?? '') }}">

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Description') }}</label>
        <textarea name="description" rows="3"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $destination->description ?? '') }}</textarea>
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $destination->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">{{ ui_phrase('Active') }}</span>
    </div>

    <div class="flex items-center gap-2">
        <button class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('destinations.index') }}"
            class="btn-secondary">{{ ui_phrase('Cancel') }}</a>
    </div>
</div>
