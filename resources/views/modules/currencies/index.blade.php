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
                    <form method="GET" action="{{ route('currencies.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}" class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
                        <select name="status" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Status') }}</option>
                            <option value="active" @selected((string) request('status') === 'active')>{{ ui_phrase('Active') }}</option>
                            <option value="inactive" @selected((string) request('status') === 'inactive')>{{ ui_phrase('Inactive') }}</option>
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                            <a href="{{ route('currencies.index') }}" class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
        @if ($errors->has('currency'))
            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ $errors->first('currency') }}</div>
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
                        @foreach (($bulkCurrencies ?? collect()) as $index => $currency)
                            <div class="rounded-lg mb-6 border border-gray-200 p-3 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $currency->code }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $currency->name }}</div>
                                </div>
                                <input type="hidden" name="rates[{{ $index }}][id]" value="{{ $currency->id }}">
                                <div class="mt-2">
                                    <x-money-input
                                        :label="ui_phrase('Rate to IDR')"
                                        name="rates[{{ $index }}][rate_to_idr]"
                                        :value="old('rates.' . $index . '.rate_to_idr', $currency->rate_to_idr)"
                                        min="0"
                                        step="0.000001"
                                        badge="IDR"
                                        compact
                                    />
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs text-gray-500">{{ ui_phrase('Decimals') }}</label>
                                    <input
                                        name="rates[{{ $index }}][decimal_places]"
                                        type="number"
                                        min="0"
                                        max="6"
                                        value="{{ old('rates.' . $index . '.decimal_places', $currency->decimal_places) }}"
                                        class="mt-1 dark:border-gray-600 app-input"
                                    >
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2">
                        <button  class="btn-primary">{{ ui_phrase('Save Rates') }}</button>
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
                    @forelse ($currencies as $index => $currency)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ($currencies->firstItem() ?? 1) + $index }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $currency->code }}
                                @if ($currency->is_default)
                                    <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ ui_phrase('Default') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $currency->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $currency->symbol ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">{{ number_format((float) $currency->rate_to_idr, (int) ($currency->decimal_places ?? 0), '.', ',') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">{{ $currency->decimal_places }}</td>
                            <td class="px-4 py-3 text-sm text-center">
                                <x-ui.status-badge :status="$currency->is_active ? 'active' : 'inactive'" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ route('currencies.edit', $currency) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                    @can('module.currencies.delete')
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action
                                            :action="route('currencies.destroy', $currency)"
                                            method="DELETE"
                                            :modal-name="'currencies-index-delete-desktop-' . $currency->id"
                                            :title="ui_phrase('Delete') . ' ' . ui_phrase('Currency')"
                                            :message="ui_phrase('confirm delete')"
                                            :impact-title="__('confirm.important_warning')"
                                            :impact-items="[
                                                __('confirm.delete_itinerary_info_1'),
                                                __('confirm.delete_itinerary_info_2'),
                                            ]"
                                            :notice-message="__('confirm.notification_after_action')"
                                            notice-tone="danger"
                                            :confirm-label="ui_phrase('Delete')"
                                            :trigger-label="ui_phrase('Delete')"
                                            trigger-icon="fa-solid fa-trash w-4"
                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                            confirm-class="btn-danger-sm"
                                        />
                                    @endcan
                                </x-ui.table-action-dropdown>
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
                <div class="md:hidden space-y-3">
            @forelse ($currencies as $index => $currency)
                <div class="app-card relative p-4 pt-5">
                    <div class="absolute right-3 top-3 z-10">
                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                            <a href="{{ route('currencies.edit', $currency) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Edit') }}</span>
                            </a>
                            @can('module.currencies.delete')
                                <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                <x-ui.confirm-action
                                    :action="route('currencies.destroy', $currency)"
                                    method="DELETE"
                                    :modal-name="'currencies-index-delete-mobile-' . $currency->id"
                                    :title="ui_phrase('Delete') . ' ' . ui_phrase('Currency')"
                                    :message="ui_phrase('confirm delete')"
                                    :impact-title="__('confirm.important_warning')"
                                    :impact-items="[
                                        __('confirm.delete_itinerary_info_1'),
                                        __('confirm.delete_itinerary_info_2'),
                                    ]"
                                    :notice-message="__('confirm.notification_after_action')"
                                    notice-tone="danger"
                                    :confirm-label="ui_phrase('Delete')"
                                    :trigger-label="ui_phrase('Delete')"
                                    trigger-icon="fa-solid fa-trash w-4"
                                    trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                    confirm-class="btn-danger-sm"
                                />
                            @endcan
                        </x-ui.table-action-dropdown>
                    </div>
                    <div class="pr-12">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $currency->code }}
                            @if ($currency->is_default)
                                <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ ui_phrase('Default') }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $currency->name }}</p>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ ui_phrase('Rate to IDR') }}</div>
                        <div>{{ number_format((float) $currency->rate_to_idr, (int) ($currency->decimal_places ?? 0), '.', ',') }}</div>
                        <div>{{ ui_phrase('Decimals') }}</div>
                        <div>{{ $currency->decimal_places }}</div>
                        <div>{{ ui_phrase('Symbol') }}</div>
                        <div>{{ $currency->symbol ?? '-' }}</div>
                        <div>{{ ui_phrase('Status') }}</div>
                        <div>
                            <x-ui.status-badge :status="$currency->is_active ? 'active' : 'inactive'" size="xs" />
                        </div>
                    </div>
                </div>
            @empty
                <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Currencies')])" :message="ui_phrase('Try changing filter criteria or add a new currency.')" />
            @endforelse
        </div>
<div>{{ $currencies->links() }}</div>
        </div>
    </div>
@endsection






























