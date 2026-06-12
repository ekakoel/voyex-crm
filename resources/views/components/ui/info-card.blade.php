@props([
    'title' => null,
])

@php
    $hasAction = isset($action) && trim((string) $action) !== '';
@endphp

<section {{ $attributes->merge(['class' => 'app-card p-5']) }}>
    @if (filled($title) || $hasAction)
        <div class="mb-3 flex items-start justify-between gap-3">
            @if (filled($title))
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h3>
            @endif
            @if ($hasAction)
                <div class="shrink-0">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif
    {{ $slot }}
</section>
