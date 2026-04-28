@extends('layouts.master')

@section('page_title', ui_phrase('modules_island_transfers_create_page_title'))
@section('page_subtitle', ui_phrase('modules_island_transfers_create_page_subtitle'))
@section('page_actions')
    <a href="{{ route('island-transfers.index') }}" class="btn-ghost">{{ ui_phrase('modules_island_transfers_back') }}</a>
@endsection

@section('content')
    <div class="space-y-5 module-page module-page--island-transfers">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('island-transfers.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('modules.island-transfers._form', ['buttonLabel' => ui_phrase('modules_island_transfers_save_transfer')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('modules.island-transfers.partials._route-map', [
                    'mapTitle' => 'Island Transfer Preview Map (open map)',
                    'interactive' => true,
                ])
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('modules_island_transfers_info') }}</p>
                    <p class="mt-2">
                        {{ ui_phrase('modules_island_transfers_info_text') }}
                    </p>
                </div>
            </aside>
        </div>
    </div>
@endsection
