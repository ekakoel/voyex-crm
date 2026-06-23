@extends('layouts.master')
@section('page_title', ui_phrase('Hotels'))
@section('page_subtitle', ui_phrase('Manage hotel inventory, destinations, and active listing status.'))
@section('page_actions')
    <a href="{{ route('hotels.create') }}" class="btn-primary">{{ ui_phrase('Add Hotel') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--hotels" data-hotels-index data-page-spinner="off">
        <div class="module-grid-main">
            <div class="app-card p-5">
                <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-hotels-index-form data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('search') }}"
                            class="app-input sm:col-span-2 lg:col-span-2" data-hotels-filter-input data-filter-min-text="3">
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
                            @foreach ($statusFilterOptions as $option)
                                <option value="{{ $option['value'] }}" @selected((string) request('status') === (string) $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-hotels-filter-input>
                            @foreach ($perPageOptions as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', '10') === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                            <a href="{{ route('hotels.index') }}" class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-hotels-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                </form>
            </div>
            <div data-hotels-index-results-wrap>
                @include('modules.hotels.partials._index-results', ['hotels' => $hotels, 'hotelRows' => $hotelRows])
            </div>
        </div>
    </div>
@endsection








