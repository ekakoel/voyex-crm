@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--bookings">
        

        <div class="grid gap-6 xl:grid-cols-12">
            <div class="xl:col-span-8">
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
            <aside  class="xl:col-span-4">
                @include('partials._audit-info', ['record' => $booking])
            </aside>
        </div>
    </div>
@endsection






