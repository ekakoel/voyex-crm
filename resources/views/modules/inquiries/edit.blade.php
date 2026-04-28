@extends('layouts.master')

@section('page_title', ui_phrase('modules_inquiries_edit_page_title'))
@section('page_subtitle', ui_phrase('modules_inquiries_edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('inquiries.show', $inquiry) }}"  class="btn-secondary">
        {{ ui_phrase('common_view_detail') }}
    </a>
    <a href="{{ route('inquiries.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
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
                            'buttonLabel' => ui_phrase('modules_inquiries_update_inquiry'),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="app-card p-4 space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('common_activity_timeline') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_inquiries_create_update_audit') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
            </aside>
        </div>
    </div>
@endsection
