@props([
    'label' => 'Google Maps URL',
    'name' => 'google_maps_url',
    'id' => null,
    'value' => '',
    'placeholder' => 'https://maps.google.com/...',
    'errorKey' => null,
])

@php
    $resolvedErrorKey = $errorKey ?? $name;
@endphp

<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
    <div class="mt-1 flex flex-nowrap items-center gap-3">
        <input
            @if ($id) id="{{ $id }}" @endif
            name="{{ $name }}"
            data-location-field="google_maps_url"
            type="url"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            class="app-input min-w-0 flex-1">
        <button
            type="button"
            data-location-autofill-trigger
            class="btn-outline-sm h-[var(--app-form-control-h)] shrink-0 px-3">
            Auto Fill
        </button>
    </div>
    @error($resolvedErrorKey) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
</div>
