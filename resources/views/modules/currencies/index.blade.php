@extends('layouts.master')
@section('page_title', __('ui.modules.currencies.page_title'))
@section('page_subtitle', __('ui.modules.currencies.page_subtitle'))
@section('page_actions')
    <a href="{{ route('currencies.create') }}" class="btn-primary">{{ __('ui.modules.currencies.add_currency') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--currencies" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('currencies.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('ui.modules.currencies.search') }}" class="app-input sm:col-span-2" data-service-filter-input>
            <select name="per_page" class="app-input" data-service-filter-input>
                @foreach ([10,25,50,100] as $size)
                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ __('ui.index.per_page_option', ['size' => $size]) }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                <a href="{{ route('currencies.index') }}" class="btn-ghost" data-service-filter-reset>{{ __('ui.common.reset') }}</a>
            </div>
        </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9" data-service-filter-results>
                @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if ($errors->has('currency'))
            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ $errors->first('currency') }}</div>
        @endif
    @can('module.currencies.update')
            <div class="app-card p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.currencies.bulk_update_rates') }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.currencies.bulk_update_caption') }}</p>
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
                                        :label="__('ui.modules.currencies.rate_to_idr')"
                                        name="rates[{{ $index }}][rate_to_idr]"
                                        :value="old('rates.' . $index . '.rate_to_idr', $currency->rate_to_idr)"
                                        min="0"
                                        step="0.000001"
                                        badge="IDR"
                                        compact
                                    />
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs text-gray-500">{{ __('ui.modules.currencies.decimals') }}</label>
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
                        <button  class="btn-primary">{{ __('ui.modules.currencies.save_rates') }}</button>
                    </div>
                </form>
            </div>
        @endcan
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.code') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.symbol') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.currencies.rate_to_idr') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.currencies.decimals') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ __('ui.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($currencies as $index => $currency)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $currency->code }}
                                @if ($currency->is_default)
                                    <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('ui.common.default') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $currency->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $currency->symbol ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">{{ number_format((float) $currency->rate_to_idr, (int) ($currency->decimal_places ?? 0), '.', ',') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">{{ $currency->decimal_places }}</td>
                            <td class="px-4 py-3 text-sm text-center">
                                <x-status-badge :status="$currency->is_active ? 'active' : 'inactive'" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('currencies.edit', $currency) }}"  class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                                @can('module.currencies.delete')
                                <form action="{{ route('currencies.destroy', $currency) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('{{ __('ui.modules.currencies.confirm_delete') }}')"   class="btn-danger-sm">{{ __('ui.common.delete') }}</button>
                                </form>
                                @endcan
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.currencies')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
                <div class="md:hidden space-y-3">
            @forelse ($currencies as $index => $currency)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $currency->code }}
                                @if ($currency->is_default)
                                    <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('ui.common.default') }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $currency->name }}</p>
                        </div>
                        <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $currency->symbol ?? '-' }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ __('ui.modules.currencies.rate_to_idr') }}</div>
                        <div>{{ number_format((float) $currency->rate_to_idr, (int) ($currency->decimal_places ?? 0), '.', ',') }}</div>
                        <div>{{ __('ui.modules.currencies.decimals') }}</div>
                        <div>{{ $currency->decimal_places }}</div>
                        <div>{{ __('ui.common.status') }}</div>
                        <div>
                            <x-status-badge :status="$currency->is_active ? 'active' : 'inactive'" size="xs" />
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('currencies.edit', $currency) }}" class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                        @can('module.currencies.delete')
                        <form action="{{ route('currencies.destroy', $currency) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('{{ __('ui.modules.currencies.confirm_delete') }}')" class="btn-danger-sm">{{ __('ui.common.delete') }}</button>
                        </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.currencies')]) }}</div>
            @endforelse
        </div>
<div>{{ $currencies->links() }}</div>
            </div>
        </div>
</div>
@endsection

























