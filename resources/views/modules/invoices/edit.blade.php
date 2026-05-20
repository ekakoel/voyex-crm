@extends('layouts.master')

@section('page_title', ui_phrase('Edit Invoice'))
@section('page_subtitle', ui_phrase('Update invoice billing fields safely.'))

@section('content')
    <div class="space-y-6 module-page module-page--invoices">
        @section('page_actions')
            <a href="{{ route('invoices.show', $invoice) }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
        @endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="app-card p-5 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Invoice Number') }}</label>
                            <input type="text" class="mt-1 app-input bg-gray-50 dark:bg-gray-900/30" value="{{ $invoice->invoice_number }}" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Invoice Type') }}</label>
                            <select name="invoice_type" class="mt-1 app-input">
                                @foreach (\App\Models\Invoice::TYPE_OPTIONS as $type)
                                    <option value="{{ $type }}" @selected(old('invoice_type', $invoice->invoice_type) === $type)>{{ ui_phrase($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Invoice Date') }}</label>
                            <input type="date" name="invoice_date" value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d')) }}" class="mt-1 app-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Due Date') }}</label>
                            <input type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}" class="mt-1 app-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Subtotal') }}</label>
                            <input type="number" step="0.01" min="0" name="subtotal" value="{{ old('subtotal', (float) ($invoice->subtotal ?? 0)) }}" class="mt-1 app-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Discount Amount') }}</label>
                            <input type="number" step="0.01" min="0" name="discount_amount" value="{{ old('discount_amount', (float) ($invoice->discount_amount ?? 0)) }}" class="mt-1 app-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Tax Amount') }}</label>
                            <input type="number" step="0.01" min="0" name="tax_amount" value="{{ old('tax_amount', (float) ($invoice->tax_amount ?? 0)) }}" class="mt-1 app-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Paid Amount') }}</label>
                            <input type="text" class="mt-1 app-input bg-gray-50 dark:bg-gray-900/30" value="{{ number_format((float) ($invoice->paid_amount ?? 0), 2) }}" readonly>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Notes') }}</label>
                        <textarea name="notes" rows="4" class="mt-1 app-input">{{ old('notes', (string) ($invoice->notes ?? '')) }}</textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn-primary">{{ ui_phrase('Save') }}</button>
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary">{{ ui_phrase('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

