@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'outline',
    'icon' => null,
    'label' => '',
])

@php
    $baseClass = match ((string) $variant) {
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        'danger' => 'btn-danger',
        default => 'btn-outline',
    };
@endphp

@if (!empty($href))
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClass . ' inline-flex items-center gap-2']) }}>
        @if (!empty($icon))
            <i class="fa-solid {{ $icon }} w-4 text-center"></i>
        @endif
        <span>{{ ui_phrase((string) $label) }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $baseClass . ' inline-flex items-center gap-2']) }}>
        @if (!empty($icon))
            <i class="fa-solid {{ $icon }} w-4 text-center"></i>
        @endif
        <span>{{ ui_phrase((string) $label) }}</span>
    </button>
@endif
