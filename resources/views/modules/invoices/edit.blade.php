@extends('layouts.master')

@section('page_title', ui_phrase('Edit Invoice'))
@section('page_subtitle', ui_phrase('Update invoice billing fields safely.'))

@section('content')
    <div class="space-y-6 module-page module-page--invoices">
        <x-ui.page-header :title="ui_phrase('Edit Invoice')" :subtitle="ui_phrase('Only editable invoices can be updated directly.')">
            <x-slot:actions>
                <a href="{{ route('invoices.show', $invoice) }}" class="btn-ghost">{{ ui_phrase('Back') }}</a>
            </x-slot:actions>
        </x-ui.page-header>

        <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <x-ui.section-card :title="ui_phrase('Invoice Form')">
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
                        <div class="mt-2"><x-ui.money :amount="(float) ($invoice->paid_amount ?? 0)" /></div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Notes') }}</label>
                        <textarea name="notes" rows="4" class="mt-1 app-input">{{ old('notes', (string) ($invoice->notes ?? '')) }}</textarea>
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.action-panel>
                <button type="submit" class="btn-primary">{{ ui_phrase('Save') }}</button>
                <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary">{{ ui_phrase('Cancel') }}</a>
            </x-ui.action-panel>
        </form>
    </div>
@endsection
