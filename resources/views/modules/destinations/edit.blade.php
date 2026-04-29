@extends('layouts.master')

@section('page_title', ui_phrase('Edit') . ' ' . ui_phrase('Destination'))
@section('page_subtitle', ui_phrase('edit page subtitle'))
@section('page_actions')
    <a href="{{ route('destinations.show', $destination) }}" class="btn-secondary">{{ ui_phrase('View Detail') }}</a>
    <a href="{{ route('destinations.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--destinations">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('destinations.update', $destination) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.destinations._form', ['destination' => $destination, 'buttonLabel' => ui_phrase('Update Destination')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $destination])
            </aside>
        </div>
    </div>
@endsection


