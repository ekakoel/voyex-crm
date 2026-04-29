@props([
    'title' => ui_phrase('Loading...'),
    'compact' => false,
])

<div class="space-y-3">
    <div class="dashboard-skeleton-line h-4 w-48"></div>
    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ ui_phrase(':title is loading...', ['title' => $title]) }}</p>
    <div class="{{ $compact ? 'space-y-2' : 'grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4' }}">
        @for ($i = 0; $i < ($compact ? 3 : 4); $i++)
            <div class="dashboard-skeleton-card"></div>
        @endfor
    </div>
</div>

