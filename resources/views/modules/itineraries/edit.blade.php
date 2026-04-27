@extends('layouts.master')

@section('page_title', __('ui.common.edit') . ' ' . __('ui.modules.itineraries.page_title'))
@section('page_subtitle', __('ui.modules.itineraries.edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-secondary">{{ __('ui.common.view_detail') }}</a>
    <a href="{{ route('itineraries.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection


@section('content')
    <div class="space-y-5 module-page module-page--itineraries">
        <div class="module-form-wrap">
            <form method="POST" action="{{ route('itineraries.update', $itinerary) }}">
                @csrf
                @method('PUT')
                @include('modules.itineraries._form', [
                    'itinerary' => $itinerary,
                    'buttonLabel' => __('ui.modules.itineraries.update_itinerary'),
                ])
            </form>
        </div>
    </div>
@endsection
