@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6 module-page module-page--bookings">
        

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





