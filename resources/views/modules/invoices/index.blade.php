@extends('layouts.master')
@section('page_title', __('ui.modules.invoices.page_title'))
@section('page_subtitle', __('ui.modules.invoices.page_subtitle'))
@section('content')
    <div class="space-y-6 module-page module-page--invoices" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('ui.modules.invoices.search') }}" class="app-input sm:col-span-2" data-service-filter-input>
            <select name="status" class="app-input" data-service-filter-input>
                <option value="">{{ __('ui.common.status') }}</option>
                @foreach (\App\Models\Invoice::STATUS_OPTIONS as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input name="invoice_from" type="date" value="{{ request('invoice_from') }}" class="app-input" data-service-filter-input>
            <input name="invoice_to" type="date" value="{{ request('invoice_to') }}" class="app-input" data-service-filter-input>
            <select name="per_page" class="app-input" data-service-filter-input>
                @foreach ([10,25,50,100] as $size)
                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ __('ui.index.per_page_option', ['size' => $size]) }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                <a href="{{ route('invoices.index') }}" class="btn-ghost" data-service-filter-reset>{{ __('ui.common.reset') }}</a>
            </div>
        </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9" data-service-filter-results>
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.invoice') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.booking') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.customer') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.invoices.invoice_date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.invoices.due_date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.amount') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ __('ui.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($invoices as $index => $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->booking->booking_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->invoice_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-money :amount="$invoice->total_amount ?? 0" currency="IDR" /></td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-status-badge :status="$invoice->status" size="xs" /></td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary-sm" title="{{ __('ui.common.detail') }}" aria-label="{{ __('ui.common.detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ __('ui.common.detail') }}</span></a>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.invoices')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($invoices as $invoice)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $invoice->invoice_number }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->booking?->booking_number ?? '-' }} • {{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</p>
                        </div>
                        <x-status-badge :status="$invoice->status" size="xs" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ __('ui.modules.invoices.invoice_date') }}</div>
                        <div>{{ $invoice->invoice_date?->format('Y-m-d') ?? '-' }}</div>
                        <div>{{ __('ui.modules.invoices.due_date') }}</div>
                        <div>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</div>
                        <div>{{ __('ui.common.amount') }}</div>
                        <div><x-money :amount="$invoice->total_amount ?? 0" currency="IDR" /></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary-sm" title="{{ __('ui.common.detail') }}" aria-label="{{ __('ui.common.detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ __('ui.common.detail') }}</span></a>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.invoices')]) }}</div>
            @endforelse
        </div>
        <div>{{ $invoices->links() }}</div>
            </div>
        </div>
</div>
@endsection




