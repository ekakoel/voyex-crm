@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--activities">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('activities.update', $activity) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('modules.activities._form', ['activity' => $activity, 'buttonLabel' => 'Update Activity'])
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




