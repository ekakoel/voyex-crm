@extends('layouts.master')

@php
    $routePrefix = match($serviceType) {
        'accommodations' => 'admin.services.items.accommodations',
        'transports' => 'admin.services.items.transports',
        'guides' => 'admin.services.items.guides',
        'attractions' => 'admin.services.items.attractions',
        'travel_activities' => 'admin.services.items.travel-activities',
        default => 'admin.services.items.accommodations',
    };
@endphp

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Add {{ $serviceTypeLabel }}</h1>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route($routePrefix.'.store') }}">
                @csrf
                @include('admin.services.items._form', [
                    'buttonLabel' => 'Save '.$serviceTypeLabel,
                ])
            </form>
        </div>
    </div>
@endsection
