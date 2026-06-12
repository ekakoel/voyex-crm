@props([
    'title' => null,
    'subtitle' => null,
])

@php
    $hasBreadcrumb = isset($breadcrumb) && trim((string) $breadcrumb) !== '';
    $hasActions = isset($actions) && trim((string) $actions) !== '';
    $hasMeta = isset($meta) && trim((string) $meta) !== '';
@endphp

<header {{ $attributes->merge(['class' => 'app-card p-5']) }}>
    @if ($hasBreadcrumb)
        <div class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            {{ $breadcrumb }}
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            @if (filled($title))
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h1>
            @endif
            @if (filled($subtitle))
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
            @endif
            @if ($hasMeta)
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ $meta }}
                </div>
            @endif
        </div>
        @if ($hasActions)
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</header>
