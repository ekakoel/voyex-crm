@extends('layouts.master')
@section('page_title', 'Destinations')
@section('page_subtitle', 'Manage destination data.')
@section('page_actions')
    <a href="{{ route('destinations.create') }}" class="btn-primary">Add Destination</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--destinations">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <input name="q" value="{{ request('q') }}" placeholder="Search code/name/city/province" class="app-input sm:col-span-2">
                        <select name="per_page" class="app-input">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}/page</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <button class="btn-primary">Filter</button>
                            <a href="{{ route('destinations.index') }}" class="btn-ghost">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9">
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Destination</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">City / Province</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Linked Data</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($destinations as $index=>$destination)
                        @php($isActive = ! $destination->trashed())
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $destination->province ?: $destination->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $destination->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($destination->city ?? '') . (($destination->city && $destination->province) ? ', ' : '') . ($destination->province ?? '')) ?: '-' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-200">
                                V: {{ (int) ($destination->vendors_count ?? 0) }} |
                                A: {{ (int) ($destination->accommodations_count ?? 0) }} |
                                TA: {{ (int) ($destination->tourist_attractions_count ?? 0) }} |
                                AP: {{ (int) ($destination->airports_count ?? 0) }} |
                                TR: {{ (int) ($destination->transports_count ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300' }}">
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('destinations.show', $destination) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                                <a href="{{ route('destinations.edit', $destination) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                <form action="{{ route('destinations.toggle-status', $destination->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $isActive ? 'Deactivate this destination?' : 'Activate this destination?' }}')"   class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
                                </form>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No destinations available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($destinations as $destination)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $destination->code }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $destination->province ?: $destination->name }}</p>
                        </div>
                        <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $destination->slug }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>Location</div>
                        <div>{{ trim(($destination->city ?? '') . (($destination->city && $destination->province) ? ', ' : '') . ($destination->province ?? '')) ?: '-' }}</div>
                        <div>Linked</div>
                        <div>
                            V: {{ (int) ($destination->vendors_count ?? 0) }} |
                            A: {{ (int) ($destination->accommodations_count ?? 0) }} |
                            TA: {{ (int) ($destination->tourist_attractions_count ?? 0) }} |
                            AP: {{ (int) ($destination->airports_count ?? 0) }} |
                            TR: {{ (int) ($destination->transports_count ?? 0) }}
                        </div>
                        <div>Status</div>
                        <div>{{ $destination->trashed() ? 'Inactive' : 'Active' }}</div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('destinations.show', $destination) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                        <a href="{{ route('destinations.edit', $destination) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                        <form action="{{ route('destinations.toggle-status', $destination->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" onclick="return confirm('{{ $destination->trashed() ? 'Activate this destination?' : 'Deactivate this destination?' }}')" class="{{ $destination->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $destination->trashed() ? 'Activate' : 'Deactivate' }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No destinations available.</div>
            @endforelse
        </div>
        <div>{{ $destinations->links() }}</div>
            </div>
        </div>
</div>
@endsection

