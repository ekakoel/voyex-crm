@extends('layouts.master')

@section('page_title', __('ui.modules.invoices.show_page_title'))
@section('page_subtitle', __('ui.modules.invoices.show_page_subtitle'))

@section('content')
    <div class="space-y-6 module-page module-page--invoices">
        @section('page_actions')<a href="{{ route('invoices.index') }}"  class="btn-ghost">{{ __('ui.common.back') }}</a>@endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="app-card p-5">
                    <dl class="app-dl" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.invoices.invoice_number') }}</dt><dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $invoice->invoice_number }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.status') }}</dt><dd class="text-sm"><x-status-badge :status="$invoice->status" size="xs" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.booking') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking->booking_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.quotation') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking->quotation->quotation_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.customer') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.amount') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$invoice->total_amount ?? 0" currency="IDR" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.invoices.invoice_date') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->invoice_date?->format('Y-m-d') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.invoices.due_date') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.generated_by') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->generatedBy->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.paid_at') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-local-time :value="$invoice->paid_at" /></dd></div>
                    </dl>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $invoice])
            </aside>
        </div>
    </div>
@endsection








