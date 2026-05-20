@props([
    'status' => '',
    'label' => null,
    'size' => 'sm',
])

@php
    $statusKey = strtolower((string) $status);
    $sizeClass = $size === 'xs' ? 'px-2.5 py-0 text-[11px]' : 'px-3 py-0 text-xs';
    $colorClass = \App\Support\StatusCatalog::badgeClass($statusKey);
    $text = $label ?? ui_phrase((string) $status);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border font-semibold {$sizeClass} {$colorClass}"]) }}>
    {{ $text }}
</span>
