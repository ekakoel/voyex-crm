@extends('layouts.master')

@section('page_title', __('ui.modules.vendors.edit_page_title'))
@section('page_subtitle', __('ui.modules.vendors.edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('vendors.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--vendors">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('vendors.update', $vendor) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.vendors._form', ['vendor' => $vendor, 'buttonLabel' => __('ui.modules.vendors.update_vendor')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $vendor])
            </aside>
        </div>
    </div>
@endsection



