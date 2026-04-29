@extends('layouts.master')

@section('page_title', ui_phrase('beverages page title'))
@section('page_subtitle', ui_phrase('beverages create page subtitle'))
@section('page_actions')
    <a href="{{ route('food-beverages.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--food-beverages">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    @if (isset($copiedFrom) && $copiedFrom)
                        <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-700/70 dark:bg-sky-900/20 dark:text-sky-200">
                            {!! ui_phrase('beverages copied from', ['name' => '<span class="font-semibold">'.e($copiedFrom->name).'</span>']) !!}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('food-beverages.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('modules.food-beverages._form', ['buttonLabel' => ui_phrase('beverages save'), 'prefill' => $prefill ?? []])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('Info') }}</p>
                    <p class="mt-2">{{ ui_phrase('beverages info text') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection

