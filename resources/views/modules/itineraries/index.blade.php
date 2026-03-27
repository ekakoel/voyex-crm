@extends('layouts.master')
@section('page_title', 'Itineraries')
@section('page_subtitle', 'Manage itinerary data.')
@section('page_actions')
    <a href="{{ route('itineraries.create') }}" class="btn-primary">Create Itinerary</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--itineraries">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <input name="title" value="{{ request('title') }}" placeholder="Title" class="app-input sm:col-span-2">
                        <select name="destination_id" class="app-input sm:col-span-2">
                            <option value="">All destinations</option>
                            @foreach ($destinations as $destination)
                                <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>{{ $destination->name }}</option>
                            @endforeach
                        </select>
                        <input name="duration" type="number" min="1" value="{{ request('duration') }}" placeholder="Duration (days)" class="app-input">
                        <select name="per_page" class="app-input">
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <button class="btn-primary">Filter</button>
                            <a href="{{ route('itineraries.index') }}" class="btn-ghost">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9">
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Inquiry</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Duration</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($itineraries as $index => $itinerary)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                <div class="font-medium">{{ $itinerary->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">by {{ $itinerary->creator?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @if ($itinerary->inquiry)
                                    <span class="font-medium">{{ $itinerary->inquiry?->inquiry_number ?? '-' }}</span>
                                    @if (!empty($itinerary->inquiry?->customer?->name))
                                        <span class="text-xs text-gray-500 dark:text-gray-400">| {{ $itinerary->inquiry?->customer?->name }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Independent</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $itinerary->duration_days }}D{{ $itinerary->duration_nights > 0 ? "/".$itinerary->duration_nights."N":""; }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $itinerary->destination?->name ?? $itinerary->destination ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <x-status-badge :status="$itinerary->status" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                                @can('update', $itinerary)
                                    @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') && ! $itinerary->isFinal())
                                        <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                    @endif
                                @endcan</div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No itineraries available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($itineraries as $itinerary)
                <div class="app-card p-4">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $itinerary->title }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">by {{ $itinerary->creator?->name ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Inquiry:
                        {{ $itinerary->inquiry?->inquiry_number ?? 'Independent' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $itinerary->duration_days }} day(s)</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $itinerary->destination?->name ?? $itinerary->destination ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Status: {{ ucfirst($itinerary->status ?? 'draft') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                        @can('update', $itinerary)
                            @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') && ! $itinerary->isFinal())
                                <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                            @endif
                        @endcan</div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No itineraries available.</div>
            @endforelse
        </div>
        <div>{{ $itineraries->links() }}</div>
            </div>
        </div>
</div>
@endsection



