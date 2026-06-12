@props([
    'title' => null,
    'message' => null,
    'type' => 'warning',
])

@php
    $variant = match ($type) {
        'danger' => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
        'info' => 'border-sky-300 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
        default => 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 text-sm {$variant}"]) }}>
    @if (filled($title))
        <p class="font-semibold">{{ $title }}</p>
    @endif
    @if (filled($message))
        <p class="@if(filled($title)) mt-1 @endif">{{ $message }}</p>
    @endif
</div>
