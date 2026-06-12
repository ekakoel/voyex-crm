@props([
    'title' => null,
    'description' => null,
    'icon' => null,
])

@php
    $hasAction = isset($action) && trim((string) $action) !== '';
@endphp

<div {{ $attributes->merge(['class' => 'app-card p-6 text-center']) }}>
    <div class="mx-auto max-w-md space-y-2">
        @if (filled($icon))
            <div class="mx-auto inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                <i class="{{ $icon }}"></i>
            </div>
        @endif
        @if (filled($title))
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</p>
        @endif
        @if (filled($description))
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif
        @if ($hasAction)
            <div class="pt-2">
                {{ $action }}
            </div>
        @endif
    </div>
</div>
