@props([
    'content' => null,
    'empty' => '-',
    'class' => '',
])

@php
    $sanitized = \App\Support\SafeRichText::sanitize(is_string($content) ? $content : null);
    $classes = trim('rich-text ' . (string) $class);
@endphp

@if ($sanitized === '')
    <span class="{{ $classes }}">{{ $empty }}</span>
@else
    <div class="{{ $classes }}">{!! $sanitized !!}</div>
@endif

