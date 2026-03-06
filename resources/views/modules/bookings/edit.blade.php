@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--bookings">
        <div>
            <h1 class="app-section-title">Edit Booking</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update booking {{ $booking->booking_number }}.</p>
        </div>

        <div class="module-form-wrap">
            <form method="POST" action="{{ route('bookings.update', $booking) }}">
                @csrf
                @method('PUT')
                @include('modules.bookings._form', [
                    'booking' => $booking,
                    'buttonLabel' => 'Update Booking',
                ])
            </form>
        </div>
    </div>
@endsection



