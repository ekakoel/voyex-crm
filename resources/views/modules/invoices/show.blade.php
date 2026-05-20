@extends('layouts.master')

@section('page_title', ui_phrase('Invoice Detail'))
@section('page_subtitle', ui_phrase('Review invoice lifecycle and billing breakdown.'))

@section('content')
    <div class="space-y-6 module-page module-page--invoices">
        @section('page_actions')
            @if ($invoice->isEditable())
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn-primary">{{ ui_phrase('Edit') }}</a>
                <form method="POST" action="{{ route('invoices.issue', $invoice) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-secondary">{{ ui_phrase('Issue') }}</button>
                </form>
            @endif
            @can('payments.create')
            @if ($invoice->canReceivePayment())
                <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn-primary">{{ ui_phrase('Record Payment') }}</a>
            @endif
            @endcan
            @if (! $invoice->isPaid() && ! in_array((string) $invoice->status, ['void', 'cancelled'], true))
                <form method="POST" action="{{ route('invoices.void', $invoice) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-danger">{{ ui_phrase('Void') }}</button>
                </form>
                <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-ghost">{{ ui_phrase('Cancel Invoice') }}</button>
                </form>
            @endif
            <a href="{{ route('invoices.index') }}"  class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
        @endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="app-card p-5">
                    <dl class="app-dl" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Invoice Number') }}</dt><dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $invoice->invoice_number }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}</dt><dd class="text-sm"><x-status-badge :status="$invoice->status" size="xs" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Booking') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking->booking_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Quotation') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking->quotation->quotation_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Customer:') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Invoice Type') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ ui_phrase((string) ($invoice->invoice_type ?? 'full_payment')) }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Subtotal') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$invoice->subtotal ?? 0" currency="IDR" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Discount Amount') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$invoice->discount_amount ?? 0" currency="IDR" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Tax Amount') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$invoice->tax_amount ?? 0" currency="IDR" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Amount') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$invoice->total_amount ?? 0" currency="IDR" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Paid Amount') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$invoice->paid_amount ?? 0" currency="IDR" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Balance Amount') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-money :amount="$invoice->balance_amount ?? 0" currency="IDR" /></dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Invoice Date') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->invoice_date?->format('Y-m-d') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Due Date') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Generated By') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100">{{ $invoice->generatedBy->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Paid At') }}</dt><dd class="text-sm text-gray-800 dark:text-gray-100"><x-local-time :value="$invoice->paid_at" /></dd></div>
                    </dl>
                </div>

                <div class="app-card p-5 mt-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Payments') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ ui_phrase('Paid Amount') }}: <x-money :amount="$invoice->paid_amount ?? 0" currency="IDR" />
                        | {{ ui_phrase('Balance Amount') }}: <x-money :amount="$invoice->balance_amount ?? 0" currency="IDR" />
                    </p>
                    <div class="overflow-x-auto mt-3">
                        <table class="app-table w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Payment Number') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Payment Date') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Amount') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Type') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Status') }}</th>
                                    <th class="px-3 py-2 text-right">{{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoice->payments as $payment)
                                    <tr>
                                        <td class="px-3 py-2">{{ $payment->payment_number }}</td>
                                        <td class="px-3 py-2">{{ optional($payment->payment_date)->format('Y-m-d') ?? '-' }}</td>
                                        <td class="px-3 py-2"><x-money :amount="$payment->amount ?? 0" :currency="(string) ($payment->currency_code ?? 'IDR')" /></td>
                                        <td class="px-3 py-2">{{ ui_phrase((string) ($payment->payment_type ?? 'full_payment')) }}</td>
                                        <td class="px-3 py-2"><x-status-badge :status="(string) ($payment->status ?? 'pending')" size="xs" /></td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('payments.show', $payment) }}" class="btn-outline-sm">{{ ui_phrase('Detail') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Payments')]) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $invoice])
            </aside>
        </div>
    </div>
@endsection






