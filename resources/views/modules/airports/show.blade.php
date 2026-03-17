@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        @section('page_actions')<a href="{{ route('airports.edit', $airport) }}"  class="btn-primary">Edit</a>
                <a href="{{ route('airports.index') }}"  class="btn-ghost">Back</a>@endsection

        <div class="app-card p-4">
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



