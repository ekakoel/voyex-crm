@extends('layouts.master')

@section('page_title', ui_phrase('Add Activity'))
@section('page_subtitle', ui_phrase('create page subtitle'))
@section('page_actions')
    <a href="{{ route('activities.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--activities">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('activities.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('modules.activities._form', ['buttonLabel' => ui_phrase('Save Activity')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('Info') }}</p>
                    <p class="mt-2">{{ ui_phrase('info text') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection



