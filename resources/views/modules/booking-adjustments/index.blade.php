@extends('layouts.master')

@section('page_title', ui_phrase('Booking Adjustments'))
@section('content')
<div class="module-page space-y-4">
    <div class="module-card p-6 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">{{ ui_phrase('Booking Adjustments') }}</h2>
                <p class="text-sm text-gray-600">{{ $booking->booking_number }} @ {{ $booking->quotation?->inquiry?->customer?->name }}</p>
            </div>
            @can('booking_adjustments.create')
                <a class="btn-primary" href="{{ route('bookings.adjustments.create', $booking) }}">{{ ui_phrase('Create Adjustment') }}</a>
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="app-table w-full text-sm">
                <thead><tr><th>{{ ui_phrase('Number') }}</th><th>{{ ui_phrase('Type') }}</th><th>{{ ui_phrase('Impact Type') }}</th><th>{{ ui_phrase('Amount') }}</th><th>{{ ui_phrase('Status') }}</th><th>{{ ui_phrase('Action') }}</th></tr></thead>
                <tbody>
                @forelse($adjustments as $adj)
                    <tr>
                        <td>{{ $adj->adjustment_number }}</td>
                        <td>{{ ui_phrase($adj->adjustment_type) }}</td>
                        <td>{{ ui_phrase($adj->impact_type) }}</td>
                        <td>{{ $adj->currency_code ?: 'IDR' }} {{ number_format((float)$adj->amount, 2) }}</td>
                        <td><x-status-badge :status="(string) $adj->status" size="xs" /></td>
                        <td><a class="btn-outline-sm" href="{{ route('booking-adjustments.show', $adj) }}">{{ ui_phrase('View') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Adjustments')]) }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $adjustments->links() }}
    </div>
</div>
@endsection
