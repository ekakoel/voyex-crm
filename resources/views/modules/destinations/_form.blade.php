@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $destination = $destination ?? null;
@endphp

<div class="space-y-4" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Code</label>
            <input name="code" value="{{ old('code', $destination->code ?? '') }}"
                class="mt-1 uppercase dark:border-gray-600 app-input"
                required>
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province (Destination Basis)</label>
            <input name="province" data-location-field="province" value="{{ old('province', $destination->province ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input"
                required>
            
            @error('province') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-1">
        <div>
            <x-google-maps-autofill-row
                name="google_maps_url"
                :value="old('google_maps_url', $destination->google_maps_url ?? '')"
            />
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Slug</label>
            <input name="slug" value="{{ old('slug', $destination->slug ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input"
                placeholder="auto-generated if empty">
            @error('slug') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location</label>
            <input name="location" data-location-field="location" value="{{ old('location', $destination->location ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City/Region (Optional)</label>
            <input name="city" data-location-field="city" value="{{ old('city', $destination->city ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
            <input name="address" data-location-field="address" value="{{ old('address', $destination->address ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Country</label>
            <input name="country" data-location-field="country" value="{{ old('country', $destination->country ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Timezone</label>
            <input name="timezone" data-location-field="timezone" value="{{ old('timezone', $destination->timezone ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input">
        </div>
    </div>
    <x-location-coordinate-row
        :latitude-value="old('latitude', $destination->latitude ?? '')"
        :longitude-value="old('longitude', $destination->longitude ?? '')"
    />
    <p data-location-status class="hidden text-xs"></p>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
        <textarea name="description" rows="3"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $destination->description ?? '') }}</textarea>
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('is_active', $destination->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button  class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('destinations.index') }}"
             class="btn-secondary">Cancel</a>
    </div>
</div>
