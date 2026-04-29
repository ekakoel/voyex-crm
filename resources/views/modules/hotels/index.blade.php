@extends('layouts.master')
@section('page_title', ui_phrase('page title'))
@section('page_subtitle', ui_phrase('page subtitle'))
@section('page_actions')
    <a href="{{ route('hotels.create') }}" class="btn-primary">{{ ui_phrase('Add Hotel') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--hotels" data-hotels-index data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('Refine your list quickly.') }}</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-hotels-index-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('search') }}"
                            class="app-input sm:col-span-2" data-hotels-filter-input>
                        <select name="destination_id" class="app-input sm:col-span-2" data-hotels-filter-input>
                            <option value="">{{ ui_phrase('All destinations') }}</option>
                            @foreach (($destinations ?? collect()) as $destination)
                                <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>
                                    {{ $destination->province ?: $destination->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="status" class="app-input" data-hotels-filter-input>
                            <option value="">{{ ui_phrase('All Status') }}</option>
                            <option value="active" @selected(request('status') === 'active')>{{ ui_phrase('Active') }}</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>{{ ui_phrase('Inactive') }}</option>
                        </select>
                        <select name="per_page" class="app-input" data-hotels-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', '10') === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('hotels.index') }}" class="btn-ghost" data-hotels-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-hotels-index-results-wrap>
                @include('modules.hotels.partials._index-results', ['hotels' => $hotels, 'statsCards' => $statsCards])
            </div>
        </div>
    </div>
@endsection







