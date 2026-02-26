@props([
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => '',
    'required' => false,
    'listId' => null,
    'errorKey' => null,
])

@php
    $computedListId = $listId ?: ('datalist-' . \Illuminate\Support\Str::slug($name, '-'));
    $selectedValue = old($name, $value);
    $validationKey = $errorKey ?: $name;
@endphp

<div>
    <input
        name="{{ $name }}"
        type="text"
        list="{{ $computedListId }}"
        value="{{ $selectedValue }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        {{ $attributes->merge([
            'class' => 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100',
        ]) }}
    >
    <datalist id="{{ $computedListId }}">
        @foreach ($options as $optionValue)
            <option value="{{ $optionValue }}"></option>
        @endforeach
    </datalist>
    @error($validationKey)
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
