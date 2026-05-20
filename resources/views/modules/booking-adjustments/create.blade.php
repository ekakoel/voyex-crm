@extends('layouts.master')

@section('page_title', ui_phrase('Create Booking Adjustment'))
@section('content')
<div class="module-page">
    <div class="module-card p-6 space-y-4">
        <h2 class="text-lg font-semibold">{{ ui_phrase('Create Booking Adjustment') }}</h2>
        <form method="POST" action="{{ route('bookings.adjustments.store', $booking) }}" class="space-y-4">
            @csrf
            @include('modules.booking-adjustments._form', ['adjustment' => null, 'booking' => $booking])
            <div class="flex justify-end gap-2">
                <a href="{{ route('bookings.adjustments.index', $booking) }}" class="btn-ghost">{{ ui_phrase('Back') }}</a>
                <button type="submit" class="btn-primary">{{ ui_phrase('Save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
