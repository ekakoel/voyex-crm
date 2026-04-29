@extends('layouts.master')
@section('page_title', ui_phrase('Edit Hotel'))
@section('page_subtitle', ui_phrase('edit page subtitle'))
@section('page_actions')
    <a href="{{ route('hotels.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--hotels">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                @include('modules.hotels.partials._editor', [
                    'hotel' => $hotel,
                    'destinations' => $destinations,
                    'roomViews' => $roomViews,
                    'roomOptions' => $roomOptions,
                    'step' => $step,
                ])
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $hotel])
            </aside>
        </div>
    </div>
@endsection






