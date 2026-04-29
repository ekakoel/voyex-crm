@props([
    'status' => '',
    'label' => null,
    'size' => 'sm',
])

@php
    $statusKey = strtolower((string) $status);
    $sizeClass = $size === 'xs' ? 'px-2.5 py-0 text-[11px]' : 'px-3 py-0 text-xs';

    $statusClasses = [
        'draft' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
        'processed' => 'bg-sky-100 text-sky-700 border-sky-200 dark:bg-sky-900/40 dark:text-sky-200 dark:border-sky-800',
        'pending' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:border-amber-800',
        'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:border-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-200 dark:border-rose-800',
        'final' => 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-200 dark:border-indigo-800',
        'upcoming' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:border-amber-800',
        'expired' => 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-200 dark:border-rose-800',
        'active' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:border-emerald-800',
        'inactive' => 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-200 dark:border-rose-800',
    ];

    $colorClass = $statusClasses[$statusKey] ?? 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700';
    $text = $label ?? ui_phrase((string) $status);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border font-semibold {$sizeClass} {$colorClass}"]) }}>
    {{ $text }}
</span>
