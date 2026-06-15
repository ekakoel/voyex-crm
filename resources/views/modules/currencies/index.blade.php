@extends('layouts.master')
@section('page_title', ui_phrase('Currencies'))
@section('page_subtitle', ui_phrase('Manage active currencies and exchange rates.'))
@section('page_actions')
    <a href="{{ route('currencies.create') }}" class="btn-primary">{{ ui_phrase('Add Currency') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--currencies" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
            <div class="app-card p-5">
                <form method="GET" action="{{ route('currencies.index') }}"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form
                    data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                    <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}"
                        class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input
                        data-filter-min-text="3">
                    <select name="status" class="app-input" data-service-filter-input>
                        <option value="">{{ ui_phrase('Status') }}</option>
                        @foreach ($statusFilterOptions as $option)
                            <option value="{{ $option['value'] }}" @selected((string) request('status') === (string) $option['value'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <select name="per_page" class="app-input" data-service-filter-input>
                        @foreach ($perPageOptions as $size)
                            <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                {{ ui_phrase(':size/page', ['size' => $size]) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                        <a href="{{ route('currencies.index') }}"
                            class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4"
                            data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                    </div>
                </form>
            </div>

            @if ($errors->has('currency'))
                <div
                    class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                    {{ $errors->first('currency') }}
                </div>
            @endif

            @can('module.currencies.update')
            <div class="app-card p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Bulk Update Rates') }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('bulk update caption') }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('currencies.bulk-update') }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($bulkCurrencyRows as $row)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $row['code'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['name'] }}</div>
                                </div>
                                <input type="hidden" name="{{ $row['id_input_name'] }}" value="{{ $row['id'] }}">
                                <div class="mt-2">
                                    <x-money-input
                                        :label="ui_phrase('Rate to IDR')"
                                        :name="$row['rate_input_name']"
                                        :value="$row['rate_input_value']"
                                        min="0"
                                        step="0.000001"
                                        badge="IDR"
                                        compact
                                    />
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs text-gray-500">{{ ui_phrase('Decimals') }}</label>
                                    <input
                                        name="{{ $row['decimal_input_name'] }}"
                                        type="number"
                                        min="0"
                                        max="6"
                                        value="{{ $row['decimal_input_value'] }}"
                                        class="mt-1 dark:border-gray-600 app-input"
                                    >
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="btn-primary">{{ ui_phrase('Save Rates') }}</button>
                    </div>
                </form>
            </div>
            @endcan

            <div class="hidden md:block app-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="table-header">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Code') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Symbol') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Rate to IDR') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Decimals') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($currencyRows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row['row_number'] }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $row['code'] }}
                                        @if ($row['is_default'])
                                            <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ ui_phrase('Default') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['symbol_label'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-200">{{ $row['formatted_rate_to_idr'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-200">{{ $row['decimal_places'] }}</td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        <x-ui.status-badge :status="$row['status_badge']" size="xs" />
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm actions-compact">
                                        @include('modules.currencies.partials._index-actions', [
                                            'row' => $row,
                                            'modalName' => $row['delete_modal_name_desktop'],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6">
                                        <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Currencies')])" :message="ui_phrase('Try changing filter criteria or add a new currency.')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-3 md:hidden">
                @forelse ($currencyRows as $row)
                    @include('modules.currencies.partials._index-mobile-card', [
                        'row' => $row,
                    ])
                @empty
                    <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Currencies')])" :message="ui_phrase('Try changing filter criteria or add a new currency.')" />
                @endforelse
            </div>

            <div>{{ $currencies->links() }}</div>
        </div>
    </div>
@endsection












