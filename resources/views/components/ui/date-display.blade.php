@props([
    'date' => null,
    'format' => null,
    'placeholder' => '-',
])

@php
    $dt = null;
    if ($date instanceof \DateTimeInterface) {
        $dt = \Carbon\CarbonImmutable::instance($date);
    } elseif (is_string($date) && trim($date) !== '') {
        try {
            $dt = \Carbon\CarbonImmutable::parse($date);
        } catch (\Throwable $e) {
            $dt = null;
        }
    }
@endphp

@if (!$dt)
    <span {{ $attributes }}>{{ $placeholder }}</span>
@elseif (filled($format))
    <time datetime="{{ $dt->toIso8601String() }}" {{ $attributes }}>
        {{ $dt->format((string) $format) }}
    </time>
@else
    <x-local-time :value="$dt" :placeholder="$placeholder" {{ $attributes }} />
@endif
