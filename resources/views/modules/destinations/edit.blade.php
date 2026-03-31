@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--destinations">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('destinations.update', $destination) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.destinations._form', ['destination' => $destination, 'buttonLabel' => 'Update Destination'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side space-y-6">
                @include('partials._audit-info', ['record' => $destination])
            </aside>
        </div>
    </div>
@endsection


