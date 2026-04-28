@extends('layouts.master')
@section('page_title', ui_phrase('modules_itineraries_create_itinerary'))
@section('page_subtitle', ui_phrase('modules_itineraries_create_page_subtitle'))
@section('page_actions')
    <a href="{{ route('itineraries.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection
@section('content')
    <div class="space-y-5 module-page module-page--itineraries">
        <div class="module-form-wrap">
            <form method="POST" action="{{ route('itineraries.store') }}">
                @csrf
                @include('modules.itineraries._form', ['buttonLabel' => ui_phrase('modules_itineraries_save_itinerary')])
            </form>
        </div>
    </div>
@endsection
