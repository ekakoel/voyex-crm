@extends('layouts.master')

@section('page_title', __('ui.modules.inquiries.create_page_title'))
@section('page_subtitle', __('ui.modules.inquiries.create_page_subtitle'))
@section('page_actions')
    <a href="{{ route('inquiries.index') }}"  class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--inquiries">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('inquiries.store') }}">
                        @csrf
                        @include('modules.inquiries._form', [
                            'buttonLabel' => __('ui.modules.inquiries.save_inquiry'),
                        ])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.common.info') }}</p>
                    <p class="mt-2">{{ __('ui.modules.inquiries.info_text') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection




