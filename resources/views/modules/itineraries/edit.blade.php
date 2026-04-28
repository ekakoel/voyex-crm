@extends('layouts.master')

@section('page_title', ui_phrase('common_edit') . ' ' . ui_phrase('modules_itineraries_page_title'))
@section('page_subtitle', ui_phrase('modules_itineraries_edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-secondary">{{ ui_phrase('common_view_detail') }}</a>
    <a href="{{ route('itineraries.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection


@section('content')
    <div class="space-y-5 module-page module-page--itineraries">
        <div class="module-form-wrap">
            <form method="POST" action="{{ route('itineraries.update', $itinerary) }}">
                @csrf
                @method('PUT')
                @include('modules.itineraries._form', [
                    'itinerary' => $itinerary,
                    'buttonLabel' => ui_phrase('modules_itineraries_update_itinerary'),
                ])
            </form>
        </div>
    </div>
@endsection
