@extends('layouts.master')

@section('page_title', ui_phrase('Payment Detail'))
@section('page_subtitle', ui_phrase('Review payment and invoice impact.'))

@section('content')
    @php
        $bookingsModuleEnabled = \App\Services\ModuleService::isEnabledStatic('bookings');
    @endphp
    <div class="space-y-6 module-page module-page--payments">
        <x-ui.page-header :title="ui_phrase('Payment Detail')" :subtitle="ui_phrase('Track payment approval and invoice impact in one place.')">
            <x-slot:actions>
                @can('payments.confirm')
                    @if ($payment->canBeConfirmed())
                        <form method="POST" action="{{ route('payments.confirm', $payment) }}" class="inline">@csrf<button type="submit" class="btn-primary">{{ ui_phrase('Confirm Payment') }}</button></form>
                    @endif
                @endcan
                @can('payments.reject')
                    @if ($payment->canBeRejected())
                        <form method="POST" action="{{ route('payments.reject', $payment) }}" class="inline">@csrf<button type="submit" class="btn-danger">{{ ui_phrase('Reject Payment') }}</button></form>
                    @endif
                @endcan
                @can('payments.cancel')
                    @if ($payment->isCancellable())
                        <form method="POST" action="{{ route('payments.cancel', $payment) }}" class="inline">@csrf<button type="submit" class="btn-ghost">{{ ui_phrase('Cancel Payment') }}</button></form>
                    @endif
                @endcan
                <a href="{{ route('payments.index') }}" class="btn-ghost">{{ ui_phrase('Back') }}</a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-ui.section-card class="lg:col-span-2" :title="ui_phrase('Payment Information')">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Payment Number') }}</p><p>{{ $payment->payment_number }}</p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Status') }}</p><p><x-ui.status-badge :status="(string) $payment->status" size="xs" /></p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Payment Type') }}</p><p>{{ ui_phrase((string) $payment->payment_type) }}</p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Payment Date') }}</p><p><x-ui.date-display :date="$payment->payment_date" format="Y-m-d" /></p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Amount') }}</p><p><x-ui.money :amount="(float) ($payment->amount ?? 0)" :currency="(string) ($payment->currency_code ?? 'IDR')" /></p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Method') }}</p><p>{{ $payment->method ?? '-' }}</p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Reference Number') }}</p><p>{{ $payment->reference_number ?? '-' }}</p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Invoice') }}</p><p>{{ $payment->invoice?->invoice_number ?? '-' }}</p></div>
                    @if ($bookingsModuleEnabled)
                        <div><p class="text-xs text-gray-500">{{ ui_phrase('Booking') }}</p><p>{{ $payment->invoice?->booking?->booking_number ?? '-' }}</p></div>
                    @endif
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Customer') }}</p><p>{{ $payment->invoice?->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Confirmed At') }}</p><p><x-local-time :value="$payment->confirmed_at" /></p></div>
                    <div><p class="text-xs text-gray-500">{{ ui_phrase('Rejected At') }}</p><p><x-local-time :value="$payment->rejected_at" /></p></div>
                    <div class="md:col-span-2"><p class="text-xs text-gray-500">{{ ui_phrase('Rejection Reason') }}</p><p>{{ $payment->rejection_reason ?? '-' }}</p></div>
                </div>
            </x-ui.section-card>

            <x-ui.section-card :title="ui_phrase('Invoice Impact')">
                @php
                    $inv = $payment->invoice;
                @endphp
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between"><span>{{ ui_phrase('Invoice Total') }}</span><x-ui.money :amount="(float) ($inv->total_amount ?? 0)" /></div>
                    <div class="flex items-center justify-between"><span>{{ ui_phrase('Paid') }}</span><x-ui.money :amount="(float) ($inv->paid_amount ?? 0)" /></div>
                    <div class="flex items-center justify-between"><span>{{ ui_phrase('Balance') }}</span><x-ui.money :amount="(float) ($inv->balance_amount ?? 0)" /></div>
                    <div class="flex items-center justify-between"><span>{{ ui_phrase('Invoice Status') }}</span><x-ui.status-badge :status="(string) ($inv->status ?? 'draft')" size="xs" /></div>
                </div>
                @if ($payment->proof_path)
                    <div class="mt-4 text-sm">
                        <p class="text-xs text-gray-500">{{ ui_phrase('Payment Proof') }}</p>
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->proof_path) }}" target="_blank" class="text-primary-600 hover:underline">{{ ui_phrase('View') }}</a>
                    </div>
                @endif
            </x-ui.section-card>
        </div>
    </div>
@endsection
