@extends('layouts.master')

@section('page_title', ui_phrase('edit page title'))
@section('page_subtitle', ui_phrase('edit page subtitle'))
@section('page_actions')
    <a href="{{ route('vendors.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--vendors">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('vendors.update', $vendor) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.vendors._form', ['vendor' => $vendor, 'buttonLabel' => ui_phrase('Update Vendor')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $vendor])
            </aside>
        </div>
    </div>
@endsection




