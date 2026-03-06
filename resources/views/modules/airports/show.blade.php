@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $airport->name }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $airport->code }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('airports.edit', $airport) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Edit</a>
                <a href="{{ route('airports.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Back</a>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                <div><span class="text-gray-500 dark:text-gray-400">Location:</span> <span class="text-gray-800 dark:text-gray-100">{{ $airport->location ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Destination:</span> <span class="text-gray-800 dark:text-gray-100">{{ $airport->destination?->name ?? '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">City/Province:</span> <span class="text-gray-800 dark:text-gray-100">{{ trim(($airport->city ?? '') . (($airport->city && $airport->province) ? ', ' : '') . ($airport->province ?? '')) ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Country:</span> <span class="text-gray-800 dark:text-gray-100">{{ $airport->country ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Timezone:</span> <span class="text-gray-800 dark:text-gray-100">{{ $airport->timezone ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Latitude:</span> <span class="text-gray-800 dark:text-gray-100">{{ $airport->latitude ?? '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Longitude:</span> <span class="text-gray-800 dark:text-gray-100">{{ $airport->longitude ?? '-' }}</span></div>
                <div class="md:col-span-2"><span class="text-gray-500 dark:text-gray-400">Address:</span> <span class="text-gray-800 dark:text-gray-100">{{ $airport->address ?: '-' }}</span></div>
                <div class="md:col-span-2"><span class="text-gray-500 dark:text-gray-400">Notes:</span> <span class="text-gray-800 dark:text-gray-100">{{ $airport->notes ?: '-' }}</span></div>
            </div>
        </div>
    </div>
@endsection
