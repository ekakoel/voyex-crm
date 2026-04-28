@extends('layouts.master')

@section('page_title', ui_phrase('modules_airports_edit_airport'))
@section('page_subtitle', ui_phrase('modules_airports_edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('airports.show', $airport) }}" class="btn-outline">{{ ui_phrase('common_view') }}</a>
    <a href="{{ route('airports.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--airports">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('airports.update', $airport) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.airports._form', ['airport' => $airport, 'buttonLabel' => ui_phrase('modules_airports_update_airport')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                <div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('common_guidelines') }}</p>
                    <ul class="mt-2 space-y-2 text-sm">
                        <li>{{ ui_phrase('modules_airports_guide_edit_1') }}</li>
                        <li>{{ ui_phrase('modules_airports_guide_edit_2') }}</li>
                        <li>{{ ui_phrase('modules_airports_guide_edit_3') }}</li>
                    </ul>
                </div>
                @include('partials._audit-info', ['record' => $airport])
            </aside>
        </div>
    </div>
@endsection
