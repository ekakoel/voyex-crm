@extends('layouts.master')
@section('page_title', ui_phrase('page title'))
@section('page_subtitle', ui_phrase('page subtitle'))
@section('page_actions')
    <a href="{{ route('roles.create') }}" class="btn-primary">
        {{ ui_phrase('Add Role') }}
    </a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--roles" data-roles-index data-page-spinner="off">

        <div class="module-grid-9-3">
            <aside class="module-grid-side">
                @include('components.module-index-sidebar-info')
            </aside>

            <div class="module-grid-main" data-roles-index-results-wrap>
                <div class="app-card p-5">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('filters caption') }}</p>
                    </div>

                    <form
                        method="GET"
                        action="{{ route('roles.index') }}"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                        data-roles-index-form
                        data-disable-submit-lock="1"
                        data-page-spinner="off"
                    >
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search', $search ?? '') }}"
                            class="app-input sm:col-span-2"
                            placeholder="{{ ui_phrase('search') }}"
                            data-roles-filter-input
                        >
                        <select name="per_page" class="app-input" data-roles-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('roles.index') }}" class="btn-ghost" data-roles-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
                @include('modules.roles.partials._index-results', ['roles' => $roles])
            </div>
        </div>
    </div>
@endsection





