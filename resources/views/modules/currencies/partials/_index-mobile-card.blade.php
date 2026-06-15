<div class="app-card relative p-4 pt-5">
    <div class="absolute right-3 top-3 z-10">
        @include('modules.currencies.partials._index-actions', [
            'row' => $row,
            'modalName' => $row['delete_modal_name_mobile'],
        ])
    </div>

    <div class="pr-12">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
            {{ $row['code'] }}
            @if ($row['is_default'])
                <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ ui_phrase('Default') }}</span>
            @endif
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['name'] }}</p>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
        <div>{{ ui_phrase('Rate to IDR') }}</div>
        <div>{{ $row['formatted_rate_to_idr'] }}</div>
        <div>{{ ui_phrase('Decimals') }}</div>
        <div>{{ $row['decimal_places'] }}</div>
        <div>{{ ui_phrase('Symbol') }}</div>
        <div>{{ $row['symbol_label'] }}</div>
        <div>{{ ui_phrase('Status') }}</div>
        <div>
            <x-ui.status-badge :status="$row['status_badge']" size="xs" />
        </div>
    </div>
</div>
