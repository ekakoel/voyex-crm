@props([
    'title' => null,
    'value' => null,
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'app-card p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            @if (filled($title))
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $title }}</p>
            @endif
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $value }}
            </div>
            @if (filled($description))
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
        @if (filled($icon))
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                <i class="{{ $icon }}"></i>
            </span>
        @endif
    </div>
</div>
