@extends('layouts.master')

@section('page_title', 'Edit Inquiry')
@section('page_subtitle', 'Update inquiry details.')
@section('page_actions')
    <a href="{{ route('inquiries.show', $inquiry) }}"  class="btn-secondary">
        View Detail
    </a>
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
                            'buttonLabel' => 'Update Inquiry',
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="app-card p-4 space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Activity Timeline</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">Detailed create/update audit log for this inquiry.</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
            </aside>
        </div>
    </div>
@endsection

