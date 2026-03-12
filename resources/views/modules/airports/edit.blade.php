@extends('layouts.master')

@section('content')
    <div class="max-w-5xl space-y-6">
        
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('airports.update', $airport) }}">
                @csrf
                @method('PUT')
                @include('modules.airports._form', ['airport' => $airport, 'buttonLabel' => 'Update Airport'])
            </form>
        </div>
    </div>
@endsection


