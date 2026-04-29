@extends('layouts.master')

@section('page_title', ui_phrase('Edit Itinerary'))
@section('page_subtitle', ui_phrase('Update itinerary information.'))
@section('page_actions')
    <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-secondary">{{ ui_phrase('View Detail') }}</a>
    <a href="{{ route('itineraries.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection


@section('content')
    <div class="space-y-5 module-page module-page--itineraries">
        <div class="module-form-wrap">
            <form method="POST" action="{{ route('itineraries.update', $itinerary) }}">
                @csrf
                @method('PUT')
                @include('modules.itineraries._form', [
                    'itinerary' => $itinerary,
                    'buttonLabel' => ui_phrase('Update Itinerary'),
                ])
            </form>
        </div>
    </div>
@endsection

