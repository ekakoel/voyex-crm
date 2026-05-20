@extends('layouts.master')

@section('page_title', ui_phrase('Booking Adjustment Detail'))
@section('content')
<div class="module-page space-y-4">
    <div class="module-card p-6 space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">{{ $adjustment->adjustment_number }}</h2>
            <x-status-badge :status="(string) $adjustment->status" />
        </div>
        <p class="text-sm text-gray-600">{{ $adjustment->title }}</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div><span class="text-xs text-gray-500">{{ ui_phrase('Type') }}</span><p>{{ ui_phrase($adjustment->adjustment_type) }}</p></div>
            <div><span class="text-xs text-gray-500">{{ ui_phrase('Impact Type') }}</span><p>{{ ui_phrase($adjustment->impact_type) }}</p></div>
            <div><span class="text-xs text-gray-500">{{ ui_phrase('Amount') }}</span><p>{{ $adjustment->currency_code ?: 'IDR' }} {{ number_format((float)$adjustment->amount, 2) }}</p></div>
            <div><span class="text-xs text-gray-500">{{ ui_phrase('Booking') }}</span><p><a class="text-primary-600" href="{{ route('bookings.show', $adjustment->booking) }}">{{ $adjustment->booking->booking_number ?? '-' }}</a></p></div>
        </div>
        @if ($adjustment->description)
            <div><span class="text-xs text-gray-500">{{ ui_phrase('Description') }}</span><p>{{ $adjustment->description }}</p></div>
        @endif
        @if ($adjustment->reason)
            <div><span class="text-xs text-gray-500">{{ ui_phrase('Reason') }}</span><p>{{ $adjustment->reason }}</p></div>
        @endif
        @if ($adjustment->generatedInvoice)
            <div><span class="text-xs text-gray-500">{{ ui_phrase('Generated Invoice') }}</span><p><a class="text-primary-600" href="{{ route('invoices.show', $adjustment->generatedInvoice) }}">{{ $adjustment->generatedInvoice->invoice_number }}</a></p></div>
        @endif

        <div class="flex flex-wrap gap-2 pt-2 border-t">
            @if ($adjustment->isDraft() && auth()->user()?->can('booking_adjustments.update'))
                <a href="{{ route('booking-adjustments.edit', $adjustment) }}" class="btn-secondary">{{ ui_phrase('Edit') }}</a>
            @endif
            @if ($adjustment->canSubmit() && auth()->user()?->can('booking_adjustments.submit'))
                <form method="POST" action="{{ route('booking-adjustments.submit', $adjustment) }}">@csrf<button class="btn-secondary">{{ ui_phrase('Submit') }}</button></form>
            @endif
            @if ($adjustment->canApprove() && auth()->user()?->can('booking_adjustments.approve'))
                <form method="POST" action="{{ route('booking-adjustments.approve', $adjustment) }}">@csrf<button class="btn-secondary">{{ ui_phrase('Approve') }}</button></form>
            @endif
            @if ($adjustment->canReject() && auth()->user()?->can('booking_adjustments.reject'))
                <form method="POST" action="{{ route('booking-adjustments.reject', $adjustment) }}" class="flex gap-2">@csrf<input name="rejection_reason" class="app-input" placeholder="{{ ui_phrase('Rejection reason') }}"><button class="btn-danger">{{ ui_phrase('Reject') }}</button></form>
            @endif
            @if ($adjustment->canApply() && auth()->user()?->can('booking_adjustments.apply'))
                <form method="POST" action="{{ route('booking-adjustments.apply', $adjustment) }}">@csrf<button class="btn-primary">{{ ui_phrase('Apply') }}</button></form>
            @endif
            @if ($adjustment->canCancel() && auth()->user()?->can('booking_adjustments.cancel'))
                <form method="POST" action="{{ route('booking-adjustments.cancel', $adjustment) }}">@csrf<button class="btn-ghost">{{ ui_phrase('Cancel') }}</button></form>
            @endif
        </div>
    </div>
</div>
@endsection
