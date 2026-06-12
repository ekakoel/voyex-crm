@extends('layouts.master')

@section('page_title', ui_phrase('Invoices'))
@section('page_subtitle', ui_phrase('Monitor proforma and final invoice lifecycle.'))
@section('page_actions')
    <a href="{{ route('invoices.index') }}" class="btn-ghost">{{ ui_phrase('Refresh') }}</a>
@endsection

@section('content')
    @php
        $bookingsModuleEnabled = \App\Services\ModuleService::isEnabledStatic('bookings');
    @endphp
    <div class="space-y-5 module-page module-page--invoices" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.metric-card :title="ui_phrase('Total Invoice')" :value="(string) ($summaries['total'] ?? 0)" />
                <x-ui.metric-card :title="ui_phrase('Proforma')" :value="(string) ($summaries['proforma'] ?? 0)" />
                <x-ui.metric-card :title="ui_phrase('Final')" :value="(string) ($summaries['final'] ?? 0)" />
                <x-ui.metric-card :title="ui_phrase('Unpaid Balance')">
                    <x-slot:value><x-ui.money :amount="(float) ($summaries['unpaid_balance'] ?? 0)" /></x-slot:value>
                </x-ui.metric-card>
            </div>
            <div class="app-card p-4 mt-3">
                <form method="GET" action="{{ route('invoices.index') }}"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form
                    data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                    <input name="q" value="{{ request('q') }}"
                        placeholder="{{ $bookingsModuleEnabled ? ui_phrase('Search invoice number, booking, quotation, customer...') : ui_phrase('Search invoice number, quotation, customer...') }}"
                        class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
                    <select name="invoice_type" class="app-input" data-service-filter-input>
                        <option value="">{{ ui_phrase('Invoice Type') }}</option>
                        @foreach (\App\Models\Invoice::TYPE_OPTIONS as $type)
                            <option value="{{ $type }}" @selected(request('invoice_type') === $type)>{{ ui_phrase($type) }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="app-input" data-service-filter-input>
                        <option value="">{{ ui_phrase('Status') }}</option>
                        @foreach (\App\Models\Invoice::STATUS_OPTIONS as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ui_phrase($status) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="per_page" class="app-input" data-service-filter-input>
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                {{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                        <a href="{{ route('invoices.index') }}"
                            class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4"
                            data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                    </div>
                </form>
            </div>
            <div class="hidden md:block app-card overflow-hidden mt-3">
                <div class="overflow-x-auto">
                    <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="table-header">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Invoice Number') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Type') }}</th>
                                @if ($bookingsModuleEnabled)
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Booking') }}</th>
                                @endif
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Customer') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Handled By') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Issue Date') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Due Date') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Total') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Paid') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Balance') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Status') }}</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($invoices as $invoice)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                                        {{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.status-badge
                                            :status="(string) ($invoice->invoice_type ?? 'proforma')" size="xs" /></td>
                                    @if ($bookingsModuleEnabled)
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $invoice->booking->booking_number ?? '-' }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $invoice->booking?->quotation?->inquiry?->handledBy?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.date-display
                                            :date="$invoice->invoice_date" format="Y-m-d" /></td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.date-display
                                            :date="$invoice->due_date" format="Y-m-d" /></td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.money
                                            :amount="(float) ($invoice->total_amount ?? 0)" /></td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.money
                                            :amount="(float) ($invoice->paid_amount ?? 0)" /></td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.money
                                            :amount="(float) ($invoice->balance_amount ?? 0)" /></td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.status-badge
                                            :status="(string) $invoice->status" size="xs" /></td>
                                    <td class="px-4 py-3 text-right text-sm actions-compact">
                                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                            <a href="{{ route('invoices.show', $invoice) }}"
                                                class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                                <span>{{ ui_phrase('Detail') }}</span>
                                            </a>
                                        </x-ui.table-action-dropdown>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $bookingsModuleEnabled ? '12' : '11' }}" class="px-4 py-6">
                                        <x-ui.empty-state :title="ui_phrase('No invoices found.')" :description="$bookingsModuleEnabled ? ui_phrase('Try adjusting filters or generate invoice from quotation flow.') : ui_phrase('Try adjusting filters or review invoice availability from quotation flow.')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="md:hidden space-y-3 mt-3">
                @forelse ($invoices as $invoice)
                    <div class="app-card relative p-4 pt-5">
                        <div class="absolute right-3 top-3 z-10">
                            <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                    <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                    <span>{{ ui_phrase('Detail') }}</span>
                                </a>
                            </x-ui.table-action-dropdown>
                        </div>
                        <div class="flex items-start gap-3 pr-12">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $invoice->invoice_number }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $bookingsModuleEnabled ? ($invoice->booking->booking_number ?? '-') : ($invoice->booking?->quotation?->quotation_number ?? '-') }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                            <div>{{ ui_phrase('Type') }}</div>
                            <div><x-ui.status-badge :status="(string) ($invoice->invoice_type ?? 'proforma')" size="xs" /></div>
                            <div>{{ ui_phrase('Customer') }}</div>
                            <div>{{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</div>
                            <div>{{ ui_phrase('Issue Date') }}</div>
                            <div><x-ui.date-display :date="$invoice->invoice_date" format="Y-m-d" /></div>
                            <div>{{ ui_phrase('Due Date') }}</div>
                            <div><x-ui.date-display :date="$invoice->due_date" format="Y-m-d" /></div>
                            <div>{{ ui_phrase('Balance') }}</div>
                            <div><x-ui.money :amount="(float) ($invoice->balance_amount ?? 0)" /></div>
                            <div>{{ ui_phrase('Status') }}</div>
                            <div><x-ui.status-badge :status="(string) $invoice->status" size="xs" /></div>
                        </div>
                    </div>
                @empty
                    <x-module-empty-state :title="ui_phrase('No invoices found.')" :message="$bookingsModuleEnabled ? ui_phrase('Try adjusting filters or generate invoice from quotation flow.') : ui_phrase('Try adjusting filters or review invoice availability from quotation flow.')" />
                @endforelse
            </div>
            <div>{{ $invoices->links() }}</div>
        </div>
    @endsection
