@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $vendor = $vendor ?? null;
    $destinations = $destinations ?? collect();
@endphp

<div class="space-y-4" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Vendor Name</label>
            <input name="name" value="{{ old('name', $vendor->name ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-1">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Google Maps URL</label>
            <div class="mt-1 flex items-center gap-3">
                <input id="google_maps_url" data-location-field="google_maps_url" name="google_maps_url" type="url" value="{{ old('google_maps_url', $vendor->google_maps_url ?? '') }}" placeholder="https://maps.google.com/..." class="app-input">
                <button type="button" data-location-autofill-trigger class="btn-outline-sm shrink-0">Auto Fill</button>
            </div>
            @error('google_maps_url') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Latitude</label>
            <input id="latitude" data-location-field="latitude" name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $vendor->latitude ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('latitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Longitude</label>
            <input id="longitude" data-location-field="longitude" name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $vendor->longitude ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('longitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Destination</label>
            <select id="destination_id" data-location-field="destination_id" name="destination_id" class="mt-1 dark:border-gray-600 app-input">
                <option value="">Select destination</option>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}"
                        data-city="{{ $destination->city ?? '' }}"
                        data-province="{{ $destination->province ?? '' }}"
                        @selected((string) old('destination_id', $vendor->destination_id ?? '') === (string) $destination->id)>
                        {{ $destination->province ?: $destination->name }}
                    </option>
                @endforeach
            </select>
            @error('destination_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location</label>
            <input id="location" data-location-field="location" name="location" value="{{ old('location', $vendor->location ?? '') }}" placeholder="contoh: Uluwatu, Badung" class="mt-1 dark:border-gray-600 app-input">
            @error('location') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City</label>
            <input id="city" data-location-field="city" name="city" value="{{ old('city', $vendor->city ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('city') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province</label>
            <input id="province" data-location-field="province" name="province" value="{{ old('province', $vendor->province ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('province') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Country</label>
            <input name="country" data-location-field="country" value="{{ old('country', $vendor->country ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('country') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Timezone</label>
            <input name="timezone" data-location-field="timezone" value="{{ old('timezone', $vendor->timezone ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
            @error('timezone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Name</label>
            <input name="contact_name" value="{{ old('contact_name', $vendor->contact_name ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Email</label>
            <input name="contact_email" type="email" value="{{ old('contact_email', $vendor->contact_email ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $vendor->contact_phone ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div class="flex items-center gap-2 mt-6">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
                @checked(old('is_active', $vendor->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
        <textarea name="address" data-location-field="address" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('address', $vendor->address ?? '') }}</textarea>
    </div>
    <p data-location-status class="hidden text-xs"></p>
    <div class="flex items-center gap-2">
        <button  class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('vendors.index') }}"  class="btn-secondary">Cancel</a>
    </div>
</div>






