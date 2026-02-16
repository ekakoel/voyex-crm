@extends('layouts.master')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Add Promotion</h1>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('admin.promotions.store') }}">
                @csrf
                @include('admin.promotions._form', ['buttonLabel' => 'Save Promotion'])
            </form>
        </div>
    </div>
@endsection
