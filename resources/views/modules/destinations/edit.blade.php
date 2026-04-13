@extends('layouts.master')

@section('page_title', __('ui.common.edit') . ' ' . __('ui.modules.destinations.destination'))
@section('page_subtitle', __('ui.modules.destinations.edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('destinations.show', $destination) }}" class="btn-secondary">{{ __('ui.common.view_detail') }}</a>
    <a href="{{ route('destinations.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--destinations">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('destinations.update', $destination) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.destinations._form', ['destination' => $destination, 'buttonLabel' => __('ui.modules.destinations.update_destination')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $destination])
            </aside>
        </div>
    </div>
@endsection

