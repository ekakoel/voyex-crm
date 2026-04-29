@extends('layouts.master')

@section('page_title', ui_phrase('Edit Airport'))
@section('page_subtitle', ui_phrase('edit page subtitle'))
@section('page_actions')
    <a href="{{ route('airports.show', $airport) }}" class="btn-outline">{{ ui_phrase('View') }}</a>
    <a href="{{ route('airports.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--airports">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('airports.update', $airport) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.airports._form', ['airport' => $airport, 'buttonLabel' => ui_phrase('Update Airport')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                <div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('Guidelines') }}</p>
                    <ul class="mt-2 space-y-2 text-sm">
                        <li>{{ ui_phrase('guide edit 1') }}</li>
                        <li>{{ ui_phrase('guide edit 2') }}</li>
                        <li>{{ ui_phrase('guide edit 3') }}</li>
                    </ul>
                </div>
                @include('partials._audit-info', ['record' => $airport])
            </aside>
        </div>
    </div>
@endsection

