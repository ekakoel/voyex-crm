@extends('layouts.master')

@section('content')
    <div class="max-w-7xl space-y-6">
        
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('accommodations.update', $accommodation) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('modules.accommodations._form', ['accommodation' => $accommodation, 'buttonLabel' => 'Update Accommodation'])
            </form>
        </div>
    </div>
@endsection


