@extends('layouts.master')

@section('page_title', ui_phrase('Record Payment'))
@section('page_subtitle', ui_phrase('Create a payment record for an invoice.'))

@section('content')
    @php
        $bookingsModuleEnabled = \App\Services\ModuleService::isEnabledStatic('bookings');
        $selectedInvoice = $invoice;
        if (! $selectedInvoice && old('invoice_id')) {
            $selectedInvoice = $invoices->firstWhere('id', (int) old('invoice_id'));
        }
    @endphp

    <div class="space-y-6 module-page module-page--payments">
        <x-ui.page-header :title="ui_phrase('Record Payment')" :subtitle="ui_phrase('Capture payment data with invoice balance reference.')">
            <x-slot:actions>
                <a href="{{ route('payments.index') }}" class="btn-ghost">{{ ui_phrase('Back') }}</a>
            </x-slot:actions>
        </x-ui.page-header>

        @if ($selectedInvoice)
            <x-ui.section-card :title="ui_phrase('Invoice Summary')">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3 text-sm">
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Invoice') }}</p><p>{{ $selectedInvoice->invoice_number }}</p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Total') }}</p><p><x-ui.money :amount="(float) ($selectedInvoice->total_amount ?? 0)" /></p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Paid') }}</p><p><x-ui.money :amount="(float) ($selectedInvoice->paid_amount ?? 0)" /></p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Balance') }}</p><p><x-ui.money :amount="(float) ($selectedInvoice->balance_amount ?? 0)" /></p></div>
                    @if ($bookingsModuleEnabled)
                        <div><p class="text-xs text-gray-500">{{ ui_phrase('Booking') }}</p><p>{{ $selectedInvoice->booking?->booking_number ?? '-' }}</p></div>
                    @endif
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Customer') }}</p><p>{{ $selectedInvoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</p></div>
                </div>
            </x-ui.section-card>
        @endif

        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-ui.section-card :title="ui_phrase('Payment Form')">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Invoice') }} *</label>
                        <select name="invoice_id" class="mt-1 app-input" required>
                            <option value="">{{ ui_phrase('Select invoice') }}</option>
                            @foreach($invoices as $row)
                            <option value="{{ $row->id }}" @selected((string) old('invoice_id', $invoice?->id) === (string) $row->id)>
                                    {{ $row->invoice_number }}
                                    @if ($bookingsModuleEnabled)
                                        | {{ $row->booking?->booking_number ?? '-' }}
                                    @endif
                                    | {{ ui_phrase('Balance') }}: {{ \App\Support\Currency::format((float) ($row->balance_amount ?? 0), \App\Support\Currency::current()) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Payment Type') }} *</label>
                        <select name="payment_type" class="mt-1 app-input" required>
                            @foreach(\App\Models\Payment::TYPE_OPTIONS as $type)
                                <option value="{{ $type }}" @selected(old('payment_type', 'full_payment') === $type)>{{ ui_phrase($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Payment Date') }} *</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="mt-1 app-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Amount') }} *</label>
                        <x-money-input name="amount" :value="old('amount')" min="0.01" step="0.01" required class="mt-1 app-input" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Currency') }} *</label>
                        <input type="text" name="currency_code" value="{{ old('currency_code', (string) (\App\Support\Currency::current() ?: 'IDR')) }}" class="mt-1 app-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Method') }}</label>
                        <input type="text" name="method" value="{{ old('method') }}" class="mt-1 app-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Reference Number') }}</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="mt-1 app-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Status') }}</label>
                        <select name="status" class="mt-1 app-input">
                            @foreach(\App\Models\Payment::STATUS_OPTIONS as $status)
                                <option value="{{ $status }}" @selected(old('status', 'pending') === $status)>{{ ui_phrase($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Payment Proof') }}</label>
                        <input type="file" name="proof_file" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Notes') }}</label>
                        <textarea name="notes" rows="4" class="mt-1 app-input">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.action-panel>
                <button type="submit" class="btn-primary">{{ ui_phrase('Save') }}</button>
                <a href="{{ route('payments.index') }}" class="btn-secondary">{{ ui_phrase('Cancel') }}</a>
            </x-ui.action-panel>
        </form>
    </div>
@endsection
