@extends('layouts.master')
@section('page_title', 'Hotels')
@section('page_subtitle', 'Manage hotel data and contracts.')
@section('page_actions')
    <a href="{{ route('hotels.create') }}" class="btn-primary">Add Hotel</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--hotels" data-hotels-index data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-hotels-index-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="Search name/code/property/city/province/destination/contact"
                            class="app-input sm:col-span-2" data-hotels-filter-input>
                        <select name="destination_id" class="app-input sm:col-span-2" data-hotels-filter-input>
                            <option value="">All Destinations</option>
                            @foreach (($destinations ?? collect()) as $destination)
                                <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>
                                    {{ $destination->province ?: $destination->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="status" class="app-input" data-hotels-filter-input>
                            <option value="">All Status</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                        <select name="per_page" class="app-input" data-hotels-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', '10') === (string) $size)>{{ $size }} / page
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('hotels.index') }}" class="btn-ghost" data-hotels-filter-reset>Reset</a>
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







