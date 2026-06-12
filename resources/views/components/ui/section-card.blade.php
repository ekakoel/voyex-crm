@props([
    'title' => null,
    'description' => null,
])

@php
    $hasActions = isset($actions) && trim((string) $actions) !== '';
@endphp

<section {{ $attributes->merge(['class' => 'app-card p-5']) }}>
    @if (filled($title) || filled($description) || $hasActions)
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
                @if (filled($title))
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h3>
                @endif
                @if (filled($description))
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
                @endif
            </div>
            @if ($hasActions)
                <div class="shrink-0">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
