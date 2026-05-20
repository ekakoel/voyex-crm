@extends('layouts.master')

@section('page_title', ui_phrase('Settlement Review'))
@section('page_subtitle', ui_phrase('Review settlement gate before closing booking.'))

@section('page_actions')
    <a href="{{ route('bookings.show', $booking) }}" class="btn-ghost">{{ ui_phrase('Back to Booking') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page">
        <div class="module-card p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Booking Summary') }}</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Booking No') }}</p><p>{{ $booking->booking_number }}</p></div>
                <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Status') }}</p><p><x-status-badge :status="(string) ($booking->status ?? 'confirmed')" size="xs" /></p></div>
                <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Settlement Status') }}</p><p><x-status-badge :status="(string) ($settlement?->status ?? 'pending_review')" size="xs" /></p></div>
            </div>
        </div>

        <div class="module-card p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Settlement Checklist') }}</h3>
            @php
                $checks = [
                    'Service Completed' => !in_array('service_not_completed', $blockers, true),
                    'Invoice Balance Clear' => !in_array('outstanding_balance', $blockers, true),
                    'Pending Payment Clear' => !in_array('pending_payment', $blockers, true),
                    'Pending Adjustment Clear' => !in_array('pending_adjustment', $blockers, true),
                    'Overpayment Resolved' => !in_array('overpayment_unresolved', $blockers, true),
                ];
            @endphp
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($checks as $label => $passed)
                    <div class="rounded border p-3 {{ $passed ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                        <p class="text-xs font-semibold uppercase">{{ ui_phrase($label) }}</p>
                        <p class="text-sm">{{ $passed ? ui_phrase('PASS') : ui_phrase('BLOCKED') }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Total Invoice') }}</p><p>{{ number_format((float) ($summary['total_invoice_amount'] ?? 0), 2, '.', ',') }}</p></div>
                <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Total Paid') }}</p><p>{{ number_format((float) ($summary['total_paid_amount'] ?? 0), 2, '.', ',') }}</p></div>
                <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Outstanding') }}</p><p>{{ number_format((float) ($summary['outstanding_amount'] ?? 0), 2, '.', ',') }}</p></div>
                <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Overpaid') }}</p><p>{{ number_format((float) ($summary['overpaid_amount'] ?? 0), 2, '.', ',') }}</p></div>
            </div>

            @if($blockers !== [])
                <div class="rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    {{ ui_phrase('Blockers detected: :list', ['list' => implode(', ', $blockers)]) }}
                </div>
            @endif

            <form method="POST" action="{{ route('bookings.settlement.review', $booking) }}" class="space-y-2">
                @csrf
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Settlement Notes') }}</label>
                <textarea name="settlement_notes" rows="3" class="app-input">{{ old('settlement_notes', (string) ($settlement?->settlement_notes ?? '')) }}</textarea>
                @can('booking_settlements.review')
                    <button type="submit" class="btn-secondary">{{ ui_phrase('Review / Recalculate') }}</button>
                @endcan
            </form>

            <div class="flex flex-wrap gap-2">
                @can('booking_settlements.mark_settled')
                    <form method="POST" action="{{ route('bookings.settlement.mark-settled', $booking) }}">
                        @csrf
                        <button type="submit" class="btn-primary">{{ ui_phrase('Mark Settled') }}</button>
                    </form>
                @endcan
                @can('booking_settlements.close_booking')
                    @if((string) ($settlement?->status ?? '') === 'settled' && $blockers === [])
                        <form method="POST" action="{{ route('bookings.settlement.close', $booking) }}">
                            @csrf
                            <button type="submit" class="btn-danger">{{ ui_phrase('Close Booking') }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </div>
@endsection
