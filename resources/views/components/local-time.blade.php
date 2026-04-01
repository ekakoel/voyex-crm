@props([
    'value' => null,
    'placeholder' => '-',
    'class' => '',
    'showTimezone' => false,
])

@php
    $dt = null;
    if ($value instanceof \DateTimeInterface) {
        $dt = \Carbon\CarbonImmutable::instance($value)->utc();
    } elseif (is_string($value) && trim($value) !== '') {
        try {
            $dt = \Carbon\CarbonImmutable::parse($value)->utc();
        } catch (\Throwable $e) {
            $dt = null;
        }
    }

    $iso = $dt?->toIso8601String();
    $fallback = $dt ? $dt->format('Y-m-d H:i') . ' UTC' : $placeholder;
@endphp

@if ($iso)
    <time
        datetime="{{ $iso }}"
        data-local-time="1"
        @if ($showTimezone) data-local-timezone="1" @endif
        @class([$class])
    >
        {{ $fallback }}
    </time>
@else
    <span @class([$class])>{{ $placeholder }}</span>
@endif

