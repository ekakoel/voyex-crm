@extends('layouts.master')

@section('page_title', __('ui.modules.transports.edit_page_title'))
@section('page_subtitle', __('ui.modules.transports.edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('transports.show', $transport) }}" class="btn-secondary">{{ __('ui.common.view_detail') }}</a>
    <a href="{{ route('transports.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--transports">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('transports.update', $transport) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.transports._form', ['transport' => $transport, 'buttonLabel' => __('ui.modules.transports.update_transport')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $transport])
            </aside>
        </div>
    </div>
@endsection

