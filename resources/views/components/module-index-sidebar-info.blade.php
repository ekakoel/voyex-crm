
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
    $sidebarInfo = $sidebarInfo ?? null;
    $useCustomSidebarInfo = is_array($sidebarInfo) && isset($sidebarInfo['rows']) && is_iterable($sidebarInfo['rows']);
@endphp

<div class="app-card p-5 space-y-3">
    <div>
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
            {{ $useCustomSidebarInfo ? ($sidebarInfo['title'] ?? ui_phrase('Module Information')) : ui_phrase('Module Information') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $useCustomSidebarInfo ? ($sidebarInfo['subtitle'] ?? ui_phrase('Quick context for current list view.')) : ui_phrase('Quick context for current list view.') }}
        </p>
    </div>

    <dl class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
        @if ($useCustomSidebarInfo)
            @foreach ($sidebarInfo['rows'] as $row)
                <div class="flex items-start justify-between gap-2 min-w-0">
                    <dt class="shrink-0">{{ $row['label'] ?? '-' }}</dt>
                    <dd class="max-w-[60%] text-right font-semibold break-all {{ $row['valueClass'] ?? 'text-gray-800 dark:text-gray-100' }}">{{ $row['value'] ?? '-' }}</dd>
                </div>
            @endforeach
        @else
            <div class="flex items-start justify-between gap-2 min-w-0">
                <dt class="shrink-0">{{ ui_phrase('Active Filters') }}</dt>
                <dd class="max-w-[60%] text-right font-semibold break-all text-gray-800 dark:text-gray-100">{{ $activeFilterCount }}</dd>
            </div>
            <div class="flex items-start justify-between gap-2 min-w-0">
                <dt class="shrink-0">{{ ui_phrase('Items Per Page') }}</dt>
                <dd class="max-w-[60%] text-right font-semibold break-all text-gray-800 dark:text-gray-100">{{ request('per_page', 10) }}</dd>
            </div>
            <div class="flex items-start justify-between gap-2 min-w-0">
                <dt class="shrink-0">{{ ui_phrase('Current Page') }}</dt>
                <dd class="max-w-[60%] text-right font-semibold break-all text-gray-800 dark:text-gray-100">{{ request('page', 1) }}</dd>
            </div>
        @endif
    </dl>
</div>
