@extends('layouts.master')

@section('page_title', ui_phrase('modules_invoices_show_page_title'))
@section('page_subtitle', ui_phrase('modules_invoices_show_page_subtitle'))

@section('content')
    <div class="space-y-6 module-page module-page--invoices">
        @section('page_actions')<a href="{{ route('invoices.index') }}"  class="btn-ghost">{{ ui_phrase('common_back') }}</a>@endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="app-card p-5">
                    <dl class="app-dl" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_invoices_invoice_number') }}</dt><dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $invoice->invoice_number }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_status') }}</dt><dd class="text-sm"><x-status-badge :status="$invoice->status" size="xs" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_booking') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking->booking_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_quotation') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking->quotation->quotation_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_customer') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_amount') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$invoice->total_amount ?? 0" currency="IDR" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_invoices_invoice_date') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->invoice_date?->format('Y-m-d') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_invoices_due_date') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_generated_by') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->generatedBy->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_paid_at') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-local-time :value="$invoice->paid_at" /></dd></div>
                    </dl>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $invoice])
            </aside>
        </div>
    </div>
@endsection








