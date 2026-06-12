@extends('layouts.master')
@section('page_title', ui_phrase('Activities'))
@section('page_subtitle', ui_phrase('Manage activity services, providers, and availability.'))
@section('page_actions')
    <a href="{{ route('activities.create') }}" class="btn-primary">{{ ui_phrase('Add Activity') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--activities" data-activities-index data-page-spinner="off">
        <div class="module-grid-main">
                <div class="app-card p-5">
                    <form method="GET" action="{{ route('activities.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-activities-index-form data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}" class="app-input sm:col-span-2 lg:col-span-2" data-activities-filter-input data-filter-min-text="3">
                        <select name="vendor_id" class="app-input" data-activities-filter-input>
                            <option value="">{{ ui_phrase('All Vendors') }}</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        <select name="activity_type_id" class="app-input" data-activities-filter-input>
                            <option value="">{{ ui_phrase('All Types') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type['value'] }}" @selected((string) request('activity_type_id') === (string) $type['value'])>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="app-input" data-activities-filter-input>
                            <option value="">{{ ui_phrase('Status') }}</option>
                            <option value="active" @selected((string) request('status') === 'active')>{{ ui_phrase('Active') }}</option>
                            <option value="inactive" @selected((string) request('status') === 'inactive')>{{ ui_phrase('Inactive') }}</option>
                        </select>
                        <select name="per_page" class="app-input" data-activities-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                            <a href="{{ route('activities.index') }}" class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-activities-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
                <div data-activities-index-results-wrap>
                    @include('modules.activities.partials._index-results', ['activities' => $activities])
                </div>
        </div>
    </div>
@endsection







