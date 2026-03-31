@extends('layouts.master')
@section('page_title', 'Hotels')
@section('page_subtitle', 'Update hotel details in steps.')
@section('page_actions')
    <a href="{{ route('hotels.index') }}" class="btn-ghost">Back</a>
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





