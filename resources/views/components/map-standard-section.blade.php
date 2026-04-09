@php
    $title = $title ?? 'Map & Location Standard';
    $mapPartial = $mapPartial ?? null;
    $mapTitle = $mapTitle ?? 'Location on Map (open map)';
    $mapHeightClass = $mapHeightClass ?? 'h-[320px]';
    $interactive = $interactive ?? true;

    $mapFieldName = $mapFieldName ?? 'google_maps_url';
    $mapFieldErrorKey = $mapFieldErrorKey ?? $mapFieldName;
    $mapValue = $mapValue ?? '';

    $latitudeValue = $latitudeValue ?? '';
    $longitudeValue = $longitudeValue ?? '';
    $latitudePlaceholder = $latitudePlaceholder ?? '';
    $longitudePlaceholder = $longitudePlaceholder ?? '';
    $latitudeRequired = $latitudeRequired ?? false;
    $longitudeRequired = $longitudeRequired ?? false;

    $addressName = $addressName ?? 'address';
    $addressValue = $addressValue ?? '';
    $addressRequired = $addressRequired ?? false;

    $cityName = $cityName ?? 'city';
    $cityValue = $cityValue ?? '';

    $provinceName = $provinceName ?? 'province';
    $provinceValue = $provinceValue ?? '';

    $countryName = $countryName ?? 'country';
    $countryValue = $countryValue ?? '';

    $destinationName = $destinationName ?? 'destination_id';
    $destinationValue = $destinationValue ?? '';
    $destinations = $destinations ?? collect();

    $showLocationStatus = $showLocationStatus ?? true;
@endphp

<div class="space-y-4 rounded-lg border border-slate-200 p-4 dark:border-slate-700">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h3>

    @if ($mapPartial)
        @include($mapPartial, [
            'mapTitle' => $mapTitle,
            'mapHeightClass' => $mapHeightClass,
            'interactive' => $interactive,
        ])
    @endif

    <x-google-maps-autofill-row
        label="Map URL (Google Maps)"
        :name="$mapFieldName"
        :error-key="$mapFieldErrorKey"
        :value="$mapValue"
    />

    <x-location-coordinate-row
        :latitude-value="$latitudeValue"
        :longitude-value="$longitudeValue"
        :latitude-placeholder="$latitudePlaceholder"
        :longitude-placeholder="$longitudePlaceholder"
        :latitude-required="$latitudeRequired"
        :longitude-required="$longitudeRequired"
    />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
            <input
                name="{{ $addressName }}"
                data-location-field="address"
                value="{{ $addressValue }}"
                class="mt-1 app-input"
                @required($addressRequired)>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City/Region</label>
            <input name="{{ $cityName }}" data-location-field="city" value="{{ $cityValue }}" class="mt-1 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province</label>
            <input name="{{ $provinceName }}" data-location-field="province" value="{{ $provinceValue }}" class="mt-1 app-input">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Country</label>
            <input name="{{ $countryName }}" data-location-field="country" value="{{ $countryValue }}" class="mt-1 app-input">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Destination</label>
            <select name="{{ $destinationName }}" data-location-field="destination_id" class="mt-1 app-input">
                <option value="">Select destination</option>
                @foreach ($destinations as $destination)
                    <option
                        value="{{ $destination->id }}"
                        data-city="{{ $destination->city ?? '' }}"
                        data-province="{{ $destination->province ?? '' }}"
                        @selected((string) $destinationValue === (string) $destination->id)>
                        {{ $destination->province ?: $destination->name }}
                    </option>
                @endforeach
            </select>
            @error($destinationName) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    @if ($showLocationStatus)
        <p data-location-status class="mt-1 hidden text-xs"></p>
    @endif
</div>
