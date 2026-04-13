@extends('layouts.master')

@section('page_title', __('ui.modules.quotations.create_page_title'))
@section('page_subtitle', __('ui.modules.quotations.create_page_subtitle'))
@section('page_actions')
    <a href="{{ route('quotations.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--quotations">
        <div class="module-form-wrap">
            <form method="POST" action="{{ route('quotations.store') }}">
                @csrf
                @include('modules.quotations._form', [
                    'buttonLabel' => __('ui.modules.quotations.save_quotation'),
                ])
            </form>
        </div>
    </div>
@endsection



