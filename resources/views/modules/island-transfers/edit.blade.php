@extends('layouts.master')

@section('page_title', ui_phrase('transfers edit page title'))
@section('page_subtitle', ui_phrase('transfers edit page subtitle'))
@section('page_actions')
    <a href="{{ route('island-transfers.show', $islandTransfer) }}" class="btn-secondary">{{ ui_phrase('transfers view details') }}</a>
    <a href="{{ route('island-transfers.index') }}" class="btn-ghost">{{ ui_phrase('transfers back') }}</a>
@endsection

@section('content')
    <div class="space-y-5 module-page module-page--island-transfers">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('island-transfers.update', $islandTransfer) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.island-transfers._form', ['buttonLabel' => ui_phrase('transfers update transfer')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('modules.island-transfers.partials._route-map', [
                    'mapTitle' => 'Island Transfer Preview Map (open map)',
                    'interactive' => true,
                ])
                @include('modules.activities.partials._vendor-info', ['vendor' => $islandTransfer->vendor])
                @include('partials._audit-info', ['record' => $islandTransfer])
            </aside>
        </div>
    </div>
@endsection
