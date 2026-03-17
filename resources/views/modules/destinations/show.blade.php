@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        @section('page_actions')<a href="{{ route('destinations.edit', $destination) }}"  class="btn-primary">Edit</a>
                <a href="{{ route('destinations.index') }}"  class="btn-ghost">Back</a>@endsection

        @php
            $serviceCards = [
                [
                    'key' => 'vendors',
                    'label' => 'Vendors',
                    'value' => (int) ($destination->vendors_count ?? 0),
                    'caption' => 'Total',
                    'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                ],
                [
                    'key' => 'accommodations',
                    'label' => 'Accommodations',
                    'value' => (int) ($destination->accommodations_count ?? 0),
                    'caption' => 'Total',
                    'tone' => 'bg-sky-50 text-sky-700 border-sky-100',
                ],
                [
                    'key' => 'attractions',
                    'label' => 'Attractions',
                    'value' => (int) ($destination->tourist_attractions_count ?? 0),
                    'caption' => 'Total',
                    'tone' => 'bg-amber-50 text-amber-700 border-amber-100',
                ],
                [
                    'key' => 'airports',
                    'label' => 'Airports',
                    'value' => (int) ($destination->airports_count ?? 0),
                    'caption' => 'Total',
                    'tone' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                ],
                [
                    'key' => 'transports',
                    'label' => 'Transports',
                    'value' => (int) ($destination->transports_count ?? 0),
                    'caption' => 'Total',
                    'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
                ],
            ];
        @endphp
        <div class="space-y-2 mb-6">
            <x-index-stats :cards="$serviceCards" />
        </div>

        <div class="app-card p-4 mb-6">
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
    </div>
@endsection


