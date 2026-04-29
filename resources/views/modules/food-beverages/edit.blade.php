@extends('layouts.master')

@section('page_title', ui_phrase('Edit') . ' ' . ui_phrase('beverages page title'))
@section('page_subtitle', ui_phrase('beverages edit page subtitle'))
@section('page_actions')
    <a href="{{ route('food-beverages.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--food-beverages">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('food-beverages.update', $foodBeverage) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.food-beverages._form', ['foodBeverage' => $foodBeverage, 'buttonLabel' => ui_phrase('beverages update')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('modules.activities.partials._vendor-info', ['vendor' => $foodBeverage->vendor])
                @include('partials._audit-info', ['record' => $foodBeverage])
            </aside>
        </div>
    </div>
@endsection


