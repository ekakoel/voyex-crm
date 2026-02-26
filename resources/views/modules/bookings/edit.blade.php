@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Edit Booking</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Update booking {{ $booking->booking_number }}.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
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


