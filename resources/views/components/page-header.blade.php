@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

@php
    $hasActions = trim((string) $slot) !== '';
    $hasBreadcrumbs = ! empty($breadcrumbs);
@endphp

<div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/60">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
            @endif
        </div>
        @if ($hasActions)
            <div class="flex items-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
    @if ($hasBreadcrumbs)
        <div class="mt-4 border-t border-slate-200/70 pt-3 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
            <nav class="flex flex-wrap items-center gap-1">
                @foreach ($breadcrumbs as $index => $crumb)
                    @if ($index > 0)
                        <span class="mx-1 text-slate-300 dark:text-slate-600">></span>
                    @endif
                    @if (!empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}" class="transition hover:text-slate-700 dark:hover:text-slate-200">{{ $crumb['label'] }}</a>
                    @else
                        <span class="text-slate-700 dark:text-slate-200">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        </div>
    @endif
</div>
