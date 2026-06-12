@extends('layouts.master')
@section('page_title', ui_phrase('Roles'))
@section('page_subtitle', ui_phrase('Manage access roles and permission grouping.'))
@section('page_actions')
    <a href="{{ route('roles.create') }}" class="btn-primary">
        {{ ui_phrase('Add Role') }}
    </a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--roles" data-roles-index data-page-spinner="off">
        <div class="module-grid-main" data-roles-index-results-wrap>
                <div class="app-card p-5">
                    <form
                        method="GET"
                        action="{{ route('roles.index') }}"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                        data-roles-index-form
                        data-filter-min-text="3"
                        data-disable-submit-lock="1"
                        data-page-spinner="off"
                    >
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search', $search ?? '') }}"
                            class="app-input sm:col-span-2 lg:col-span-2"
                            placeholder="{{ ui_phrase('Search') }}"
                            data-roles-filter-input
                            data-filter-min-text="3"
                        >
                        <select name="per_page" class="app-input" data-roles-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                            <a href="{{ route('roles.index') }}" class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-roles-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
                @include('modules.roles.partials._index-results', ['roles' => $roles])
        </div>
    </div>
@endsection


