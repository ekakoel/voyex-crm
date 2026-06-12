@props([
    'status' => '',
    'label' => null,
    'color' => null,
    'size' => 'sm',
])

@php
    $statusKey = strtolower(trim((string) $status));
    $sizeClass = match ($size) {
        'xs' => 'px-2 py-0 text-[10px]',
        'lg' => 'px-3.5 py-1 text-sm',
        default => 'px-3 py-0 text-xs',
    };

    $colorClass = match ($color) {
        'gray' => 'border-gray-300 bg-gray-100 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200',
        'yellow' => 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
        'blue' => 'border-sky-300 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
        'green' => 'border-green-300 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300',
        'indigo' => 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300',
        'emerald' => 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
        'red' => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
        'purple' => 'border-violet-300 bg-violet-50 text-violet-700 dark:border-violet-700 dark:bg-violet-900/20 dark:text-violet-300',
        default => \App\Support\StatusCatalog::badgeClass($statusKey),
    };

    $fallbackLabel = $statusKey !== '' ? ui_phrase((string) $status) : ui_phrase('Unknown');
    if ($fallbackLabel === (string) $status && $statusKey !== '') {
        $fallbackLabel = ui_phrase(\Illuminate\Support\Str::headline(str_replace('_', ' ', $statusKey)));
    }
    $text = filled($label) ? (string) $label : $fallbackLabel;
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border font-semibold {$sizeClass} {$colorClass}"]) }}>
    {{ $text }}
</span>
