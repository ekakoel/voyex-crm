@extends('layouts.master')

@section('page_title', ui_phrase('Payment Detail'))
@section('page_subtitle', ui_phrase('Review payment and invoice impact.'))

@section('content')
    <div class="space-y-6 module-page module-page--payments">
        @section('page_actions')
            @can('payments.confirm')
            @if ($payment->canBeConfirmed())
                <form method="POST" action="{{ route('payments.confirm', $payment) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-primary">{{ ui_phrase('Confirm Payment') }}</button>
                </form>
            @endif
            @endcan
            @can('payments.reject')
            @if ($payment->canBeRejected())
                <form method="POST" action="{{ route('payments.reject', $payment) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-danger">{{ ui_phrase('Reject Payment') }}</button>
                </form>
            @endif
            @endcan
            @can('payments.cancel')
            @if ($payment->isCancellable())
                <form method="POST" action="{{ route('payments.cancel', $payment) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-ghost">{{ ui_phrase('Cancel Payment') }}</button>
                </form>
            @endif
            @endcan
            <a href="{{ route('payments.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
        @endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="app-card p-5">
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Payment Number') }}</dt><dd class="text-sm font-medium">{{ $payment->payment_number }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Status') }}</dt><dd class="text-sm"><x-status-badge :status="$payment->status" size="xs" /></dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Invoice') }}</dt><dd class="text-sm">{{ $payment->invoice?->invoice_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Booking') }}</dt><dd class="text-sm">{{ $payment->invoice?->booking?->booking_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Payment Type') }}</dt><dd class="text-sm">{{ ui_phrase((string) $payment->payment_type) }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Payment Date') }}</dt><dd class="text-sm">{{ optional($payment->payment_date)->format('Y-m-d') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Amount') }}</dt><dd class="text-sm"><x-money :amount="$payment->amount ?? 0" :currency="(string) ($payment->currency_code ?? 'IDR')" /></dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Method') }}</dt><dd class="text-sm">{{ $payment->method ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Reference Number') }}</dt><dd class="text-sm">{{ $payment->reference_number ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Confirmed At') }}</dt><dd class="text-sm"><x-local-time :value="$payment->confirmed_at" /></dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Rejected At') }}</dt><dd class="text-sm"><x-local-time :value="$payment->rejected_at" /></dd></div>
                        <div><dt class="text-xs text-gray-500">{{ ui_phrase('Rejection Reason') }}</dt><dd class="text-sm">{{ $payment->rejection_reason ?? '-' }}</dd></div>
                    </dl>
                    @if ($payment->proof_path)
                        <div class="mt-4">
                            <p class="text-xs text-gray-500">{{ ui_phrase('Payment Proof') }}</p>
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->proof_path) }}" target="_blank" class="text-sm text-indigo-600 hover:underline">{{ ui_phrase('View') }}</a>
                        </div>
                    @endif
                </div>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $payment])
            </aside>
        </div>
    </div>
@endsection
