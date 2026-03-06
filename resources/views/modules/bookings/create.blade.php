@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--bookings">
        <div>
            <h1 class="app-section-title">Add Booking</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Create a booking from a quotation.</p>
        </div>

        <div class="module-form-wrap">
            <form method="POST" action="{{ route('bookings.store') }}">
                @csrf
                @include('modules.bookings._form', [
                    'buttonLabel' => 'Save Booking',
                ])
            </form>
        </div>
    </div>
@endsection



