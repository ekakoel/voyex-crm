@extends('layouts.master')

@section('page_title', ui_phrase('edit page title'))
@section('page_subtitle', ui_phrase('edit page subtitle'))
@section('page_actions')
    <a href="{{ route('bookings.show', $booking) }}" class="btn-secondary">{{ ui_phrase('View Detail') }}</a>
    <a href="{{ route('bookings.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

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
                            'buttonLabel' => ui_phrase('Update Booking'),
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







