@extends('layouts.master')

@section('page_title', ui_phrase('modules_transports_edit_page_title'))
@section('page_subtitle', ui_phrase('modules_transports_edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('transports.show', $transport) }}" class="btn-secondary">{{ ui_phrase('common_view_detail') }}</a>
    <a href="{{ route('transports.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--transports">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('transports.update', $transport) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.transports._form', ['transport' => $transport, 'buttonLabel' => ui_phrase('modules_transports_update_transport')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $transport])
            </aside>
        </div>
    </div>
@endsection

