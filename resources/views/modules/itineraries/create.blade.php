@extends('layouts.master')
@section('page_title', ui_phrase('Create Itinerary'))
@section('page_subtitle', ui_phrase('Create a new itinerary record.'))
@section('page_actions')
    <a href="{{ route('itineraries.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection
@section('content')
    <div class="space-y-5 module-page module-page--itineraries">
        <div class="module-form-wrap">
            <form method="POST" action="{{ route('itineraries.store') }}">
                @csrf
                @include('modules.itineraries._form', ['buttonLabel' => ui_phrase('Save Itinerary')])
            </form>
        </div>
    </div>
@endsection

