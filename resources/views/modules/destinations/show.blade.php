@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $destination->province ?: $destination->name }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $destination->code }} | {{ $destination->slug }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('destinations.edit', $destination) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Edit</a>
                <a href="{{ route('destinations.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Back</a>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                <div><span class="text-gray-500 dark:text-gray-400">Location:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->location ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">City/Province:</span> <span class="text-gray-800 dark:text-gray-100">{{ trim(($destination->city ?? '') . (($destination->city && $destination->province) ? ', ' : '') . ($destination->province ?? '')) ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Country:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->country ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Timezone:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->timezone ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Latitude:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->latitude ?? '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Longitude:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->longitude ?? '-' }}</span></div>
                <div class="md:col-span-2"><span class="text-gray-500 dark:text-gray-400">Description:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->description ?: '-' }}</span></div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Linked Modules</h2>
            <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-5">
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Vendors</p>
                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($destination->vendors_count ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Accommodations</p>
                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($destination->accommodations_count ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Attractions</p>
                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($destination->tourist_attractions_count ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Airports</p>
                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($destination->airports_count ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Transports</p>
                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ (int) ($destination->transports_count ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
