@extends('layouts.master')

@section('page_title', ui_phrase('Record Payment'))
@section('page_subtitle', ui_phrase('Create a payment record for an invoice.'))

@section('content')
    <div class="space-y-6 module-page module-page--payments">
        @section('page_actions')
            <a href="{{ route('payments.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
        @endsection

        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="app-card p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Invoice') }} *</label>
                    <select name="invoice_id" class="mt-1 app-input" required>
                        <option value="">{{ ui_phrase('Select invoice') }}</option>
                        @foreach($invoices as $row)
                            <option value="{{ $row->id }}" @selected((string) old('invoice_id', $invoice?->id) === (string) $row->id)>
                                {{ $row->invoice_number }} | {{ $row->booking?->booking_number ?? '-' }} | {{ number_format((float) ($row->balance_amount ?? 0), 2) }} IDR
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
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="mt-1 app-input" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Currency') }} *</label>
                    <input type="text" name="currency_code" value="{{ old('currency_code', 'IDR') }}" class="mt-1 app-input" required>
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
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Notes') }}</label>
                <textarea name="notes" rows="4" class="mt-1 app-input">{{ old('notes') }}</textarea>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary">{{ ui_phrase('Save') }}</button>
                <a href="{{ route('payments.index') }}" class="btn-secondary">{{ ui_phrase('Cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
