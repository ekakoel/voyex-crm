@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Add F&B Service</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Create food and beverage service data for itinerary stop points.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('food-beverages.store') }}">
                @csrf
                @include('modules.food-beverages._form', ['buttonLabel' => 'Save F&B'])
            </form>
        </div>
    </div>
@endsection
