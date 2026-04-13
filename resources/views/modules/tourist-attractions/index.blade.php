@extends('layouts.master')
@section('page_title', __('ui.modules.tourist_attractions.page_title'))
@section('page_subtitle', __('ui.modules.tourist_attractions.page_subtitle'))
@section('page_actions')
    <a href="{{ route('tourist-attractions.create') }}" class="btn-primary">{{ __('ui.modules.tourist_attractions.add_attraction') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--tourist-attractions" data-tourist-attractions-index data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('tourist-attractions.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-tourist-attractions-index-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ __('ui.modules.tourist_attractions.search') }}" class="app-input sm:col-span-2" data-tourist-attractions-filter-input>
                        <select name="per_page" class="app-input" data-tourist-attractions-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ __('ui.index.per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('tourist-attractions.index') }}" class="btn-ghost" data-tourist-attractions-filter-reset>{{ __('ui.common.reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-tourist-attractions-index-results-wrap>
                @include('modules.tourist-attractions.partials._index-results', ['touristAttractions' => $touristAttractions])
            </div>
        </div>
    </div>
@endsection
