@props([
    'status' => '',
    'label' => null,
    'size' => 'sm',
])

@php
    $statusKey = strtolower((string) $status);
    $sizeClass = $size === 'xs' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm';

    $statusClasses = [
        'new' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
        'follow_up' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'quoted' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        'converted' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'closed' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        'draft' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        'sent' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'rejected' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'confirmed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'completed' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
        'cancelled' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
        'issued' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'void' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    ];

    $colorClass = $statusClasses[$statusKey] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200';
    $text = $label ?? ucfirst(str_replace('_', ' ', (string) $status));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full font-medium {$sizeClass} {$colorClass}"]) }}>
    {{ $text }}
</span>
