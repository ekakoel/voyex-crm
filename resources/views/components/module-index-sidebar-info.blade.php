
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

    // Only for customer index: get $customers from view if available
    $customers = $customers ?? null;
    $isCustomerIndex = isset($customers) && $customers instanceof \Illuminate\Pagination\LengthAwarePaginator;
    if ($isCustomerIndex) {
        $total = $customers->total();
        $active = $customers->filter(fn($c) => !$c->trashed())->count();
        $inactive = $customers->filter(fn($c) => $c->trashed())->count();
        $typeDist = $customers->groupBy('customer_type')->map->count();
        $topCountry = $customers->groupBy('country')->sortByDesc(fn($g) => $g->count())->keys()->first();
    }
@endphp

<div class="app-card p-5 space-y-3">
    <div>
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
            {{ $isCustomerIndex ? ui_phrase('Customer/Agent Info') : ui_phrase('Module Information') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $isCustomerIndex ? ui_phrase('Summary of current customer/agent list.') : ui_phrase('Quick context for current list view.') }}
        </p>
    </div>

    <dl class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
        @if ($isCustomerIndex)
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Total Customers') }}</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ $total }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Active') }}</dt>
                <dd class="font-semibold text-emerald-700 dark:text-emerald-300">{{ $active }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Inactive') }}</dt>
                <dd class="font-semibold text-rose-700 dark:text-rose-300">{{ $inactive }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Type Distribution') }}</dt>
                <dd>
                    @foreach ($typeDist as $type => $count)
                        <span class="inline-block mr-2">
                            {{ ui_phrase('type ' . ($type ?: 'unknown')) }}: <span class="font-semibold">{{ $count }}</span>
                        </span>
                    @endforeach
                </dd>
            </div>
            <div class="flex items-center justify-between gap-2">
                <dt>{{ ui_phrase('Top Country') }}</dt>
                <dd class="font-semibold">{{ $topCountry ?: '-' }}</dd>
            </div>
        @else
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
        @endif
    </dl>
</div>
