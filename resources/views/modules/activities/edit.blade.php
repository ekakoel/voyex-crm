@extends('layouts.master')

@section('content')
    <div class="max-w-5xl space-y-6">
        
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('activities.update', $activity) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('modules.activities._form', ['activity' => $activity, 'buttonLabel' => 'Update Activity'])
            </form>
        </div>
    </div>
@endsection




