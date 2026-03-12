@extends('layouts.master')

@section('content')
    <div class="max-w-5xl space-y-6">
        
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('destinations.store') }}">
                @csrf
                @include('modules.destinations._form', ['buttonLabel' => 'Save Destination'])
            </form>
        </div>
    </div>
@endsection


