@extends('layouts.master')
@section('page_title', __('ui.modules.itineraries.create_itinerary'))
@section('page_subtitle', __('ui.modules.itineraries.create_page_subtitle'))
@section('page_actions')
    <a href="{{ route('itineraries.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection
@section('content')
    <div class="space-y-5 module-page module-page--itineraries">
        <div class="module-form-wrap">
            <form method="POST" action="{{ route('itineraries.store') }}">
                @csrf
                @include('modules.itineraries._form', ['buttonLabel' => __('ui.modules.itineraries.save_itinerary')])
            </form>
        </div>
    </div>
@endsection
