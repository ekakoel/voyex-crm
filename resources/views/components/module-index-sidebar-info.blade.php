@php
    $queryParams = request()->query();
    $activeFilterCount = collect($queryParams)
        ->except(['page'])
        ->filter(function ($value) {
            if (is_array($value)) {
                return count(array_filter($value, fn ($v) => $v !== null && $v !== '')) > 0;
            }

            return $value !== null && $value !== '';
        })
        ->count();
@endphp

<div class="app-card p-5 space-y-3">
    <div>
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Module Information') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('Quick context for current list view.') }}</p>
    </div>

    <dl class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
        <div class="flex items-center justify-between gap-2">
            <dt>{{ ui_phrase('Active Filters') }}</dt>
            <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ $activeFilterCount }}</dd>
        </div>
        <div class="flex items-center justify-between gap-2">
            <dt>{{ ui_phrase('Items Per Page') }}</dt>
            <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ request('per_page', 10) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-2">
            <dt>{{ ui_phrase('Current Page') }}</dt>
            <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ request('page', 1) }}</dd>
        </div>
    </dl>
</div>
