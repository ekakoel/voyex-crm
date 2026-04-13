@extends('layouts.master')

@section('page_title', __('ui.modules.bookings.edit_page_title'))
@section('page_subtitle', __('ui.modules.bookings.edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('bookings.show', $booking) }}" class="btn-secondary">{{ __('ui.common.view_detail') }}</a>
    <a href="{{ route('bookings.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
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
                            'buttonLabel' => __('ui.modules.bookings.update_booking'),
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






