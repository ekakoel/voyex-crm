@extends('layouts.master')
@section('page_title', ui_phrase('modules_activities_page_title'))
@section('page_subtitle', ui_phrase('modules_activities_page_subtitle'))
@section('page_actions')
    <a href="{{ route('activities.create') }}" class="btn-primary">{{ ui_phrase('modules_activities_add_activity') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--activities" data-activities-index data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('common_filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('activities.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-activities-index-form data-disable-submit-lock="1" data-page-spinner="off">
                        <select name="vendor_id" class="app-input" data-activities-filter-input>
                            <option value="">{{ ui_phrase('common_all_vendors') }}</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        <select name="activity_type_id" class="app-input" data-activities-filter-input>
                            <option value="">{{ ui_phrase('common_all_types') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type['value'] }}" @selected((string) request('activity_type_id') === (string) $type['value'])>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-activities-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase('index_per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('activities.index') }}" class="btn-ghost" data-activities-filter-reset>{{ ui_phrase('common_reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-activities-index-results-wrap>
                @include('modules.activities.partials._index-results', ['activities' => $activities])
            </div>
        </div>
    </div>
@endsection


