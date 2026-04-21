@props([
    'latitudeName' => 'latitude',
    'longitudeName' => 'longitude',
    'latitudeId' => null,
    'longitudeId' => null,
    'latitudeValue' => '',
    'longitudeValue' => '',
    'latitudePlaceholder' => '',
    'longitudePlaceholder' => '',
    'latitudeRequired' => false,
    'longitudeRequired' => false,
    'latitudeErrorKey' => null,
    'longitudeErrorKey' => null,
])

@php
    $resolvedLatitudeErrorKey = $latitudeErrorKey ?? $latitudeName;
    $resolvedLongitudeErrorKey = $longitudeErrorKey ?? $longitudeName;
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Latitude') }}</label>
        <input
            @if ($latitudeId) id="{{ $latitudeId }}" @endif
            name="{{ $latitudeName }}"
            data-location-field="latitude"
            value="{{ $latitudeValue }}"
            class="mt-1 app-input"
            type="number"
            step="0.0000001"
            placeholder="{{ $latitudePlaceholder }}"
            @required($latitudeRequired)>
        @error($resolvedLatitudeErrorKey) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Longitude') }}</label>
        <input
            @if ($longitudeId) id="{{ $longitudeId }}" @endif
            name="{{ $longitudeName }}"
            data-location-field="longitude"
            value="{{ $longitudeValue }}"
            class="mt-1 app-input"
            type="number"
            step="0.0000001"
            placeholder="{{ $longitudePlaceholder }}"
            @required($longitudeRequired)>
        @error($resolvedLongitudeErrorKey) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
</div>
