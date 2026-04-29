@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $vendor = $vendor ?? null;
    $destinations = $destinations ?? collect();
@endphp

<div class="space-y-4" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Vendor / Provider Name') }}</label>
                <input name="name" value="{{ old('name', $vendor->name ?? '') }}"
                    class="mt-1 dark:border-gray-600 app-input" required>
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        @include('components.map-standard-section', [
            'title' => 'Map & Location Standard',
            'mapPartial' => 'modules.vendors.partials._location-map',
            'mapValue' => old('google_maps_url', $vendor->google_maps_url ?? ''),
            'latitudeValue' => old('latitude', $vendor->latitude ?? ''),
            'longitudeValue' => old('longitude', $vendor->longitude ?? ''),
            'latitudeRequired' => true,
            'longitudeRequired' => true,
            'addressValue' => old('address', $vendor->address ?? ''),
            'cityValue' => old('city', $vendor->city ?? ''),
            'provinceValue' => old('province', $vendor->province ?? ''),
            'countryValue' => old('country', $vendor->country ?? ''),
            'destinationValue' => old('destination_id', $vendor->destination_id ?? ''),
            'destinations' => $destinations,
        ])
        <input type="hidden" id="location" data-location-field="location" name="location"
            value="{{ old('location', $vendor->location ?? '') }}">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Timezone') }}</label>
            <input name="timezone" data-location-field="timezone"
                value="{{ old('timezone', $vendor->timezone ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('timezone')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Contact Name') }}</label>
                <input name="contact_name" value="{{ old('contact_name', $vendor->contact_name ?? '') }}"
                    class="mt-1 dark:border-gray-600 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Contact Email') }}</label>
                <input name="contact_email" type="email"
                    value="{{ old('contact_email', $vendor->contact_email ?? '') }}"
                    class="mt-1 dark:border-gray-600 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Contact Phone') }}</label>
                <input name="contact_phone" value="{{ old('contact_phone', $vendor->contact_phone ?? '') }}"
                    class="mt-1 dark:border-gray-600 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Website') }}</label>
                <input name="website" type="url" value="{{ old('website', $vendor->website ?? '') }}"
                    placeholder="{{ __('https://example.com') }}" class="mt-1 dark:border-gray-600 app-input">
                @error('website')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center gap-2 mt-6">
                <input type="checkbox" name="is_active" value="1"
                    class="rounded border-gray-300 text-indigo-600" @checked(old('is_active', $vendor->is_active ?? true))>
                <span class="text-sm text-gray-700 dark:text-gray-200">{{ ui_phrase('Active') }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button class="btn-primary">{{ $buttonLabel }}</button>
            <a href="{{ route('vendors.index') }}" class="btn-secondary">{{ ui_phrase('Cancel') }}</a>
        </div>
    </div>
</div>

