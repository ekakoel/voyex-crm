@extends('layouts.master')

@section('page_title', ui_phrase('Edit Booking Adjustment'))
@section('content')
<div class="module-page">
    <div class="module-card p-6 space-y-4">
        <h2 class="text-lg font-semibold">{{ ui_phrase('Edit Booking Adjustment') }}: {{ $adjustment->adjustment_number }}</h2>
        <form method="POST" action="{{ route('booking-adjustments.update', $adjustment) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            @include('modules.booking-adjustments._form', ['adjustment' => $adjustment, 'booking' => $booking])
            <div class="flex justify-end gap-2">
                <a href="{{ route('booking-adjustments.show', $adjustment) }}" class="btn-ghost">{{ ui_phrase('Back') }}</a>
                <button type="submit" class="btn-primary">{{ ui_phrase('Update') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
