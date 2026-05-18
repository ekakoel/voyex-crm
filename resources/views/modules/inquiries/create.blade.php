@extends('layouts.master')

@section('page_title', ui_phrase('Create Inquiry'))
@section('page_subtitle', ui_phrase('Create a new inquiry record.'))
@section('page_actions')
    <a href="{{ route('inquiries.index') }}"  class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--inquiries">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('inquiries.store') }}">
                        @csrf
                        @include('modules.inquiries._form', [
                            'buttonLabel' => ui_phrase('Save Inquiry'),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('modules.inquiries.partials._form-info')
            </aside>
        </div>
    </div>
@endsection



