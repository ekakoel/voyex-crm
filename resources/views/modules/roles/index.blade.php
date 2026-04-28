@extends('layouts.master')
@section('page_title', ui_phrase('modules_roles_page_title'))
@section('page_subtitle', ui_phrase('modules_roles_page_subtitle'))
@section('page_actions')
    <a href="{{ route('roles.create') }}" class="btn-primary">
        {{ ui_phrase('modules_roles_add_role') }}
    </a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--roles" data-roles-index data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />

        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('common_filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_roles_filters_caption') }}</p>
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
                            placeholder="{{ ui_phrase('modules_roles_search') }}"
                            data-roles-filter-input
                        >
                        <select name="per_page" class="app-input" data-roles-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase('index_per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('roles.index') }}" class="btn-ghost" data-roles-filter-reset>{{ ui_phrase('common_reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>

            <div class="module-grid-main space-y-4" data-roles-index-results-wrap>
                @include('modules.roles.partials._index-results', ['roles' => $roles])
            </div>
        </div>
    </div>
@endsection
