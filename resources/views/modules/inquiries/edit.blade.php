@extends('layouts.master')

@section('page_title', ui_phrase('Edit Inquiry'))
@section('page_subtitle', ui_phrase('Update inquiry information.'))
@section('page_actions')
    <a href="{{ route('inquiries.show', $inquiry) }}"  class="btn-secondary">
        {{ ui_phrase('View Detail') }}
    </a>
    <a href="{{ route('inquiries.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--inquiries">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('inquiries.update', $inquiry) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.inquiries._form', [
                            'inquiry' => $inquiry,
                            'buttonLabel' => ui_phrase('Update Inquiry'),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="app-card p-4 space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Activity Timeline') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('create update audit') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
            </aside>
        </div>
    </div>
@endsection

