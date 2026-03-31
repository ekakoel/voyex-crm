@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--bookings">
        

        <div class="module-grid-8-4">
            <div class="module-grid-main">
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
            <aside  class="module-grid-side">
                @include('partials._audit-info', ['record' => $booking])
            </aside>
        </div>
    </div>
@endsection







