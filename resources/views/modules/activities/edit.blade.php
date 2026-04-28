@extends('layouts.master')

@section('page_title', ui_phrase('modules_activities_edit_activity'))
@section('page_subtitle', ui_phrase('modules_activities_edit_page_subtitle'))
@section('page_actions')
    <a href="{{ route('activities.show', $activity) }}" class="btn-secondary">{{ ui_phrase('common_view_detail') }}</a>
    <a href="{{ route('activities.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--activities">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('activities.update', $activity) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.activities._form', ['activity' => $activity, 'buttonLabel' => ui_phrase('modules_activities_update_activity')])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('modules.activities.partials._vendor-info', ['vendor' => $activity->vendor])
                @include('partials._audit-info', ['record' => $activity])
            </aside>
        </div>
    </div>
@endsection



