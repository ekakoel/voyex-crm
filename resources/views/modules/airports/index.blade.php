@extends('layouts.master')
@section('page_title', 'Airports')
@section('page_subtitle', 'Manage airport data.')
@section('page_actions')
    <a href="{{ route('airports.create') }}" class="btn-primary">Add Airport</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--airports">
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
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <button class="btn-primary">Filter</button>
                            <a href="{{ route('airports.index') }}" class="btn-ghost">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9">
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Airport</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Location</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($airports as $airport)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        @php($isActive = ! $airport->trashed())
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $airport->code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div>{{ $airport->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $airport->country ?: '-' }}</div>
                                            <div class="text-xs text-indigo-600 dark:text-indigo-300">{{ $airport->destination?->name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($airport->city ?? '') . (($airport->city && $airport->province) ? ', ' : '') . ($airport->province ?? '')) ?: '-' }}</td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('airports.show', $airport) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                                                <a href="{{ route('airports.edit', $airport) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                                <form action="{{ route('airports.toggle-status', $airport->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" onclick="return confirm('{{ $isActive ? 'Deactivate this airport?' : 'Activate this airport?' }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No airports available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="md:hidden space-y-3">
                    @forelse ($airports as $airport)
                        <div class="app-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $airport->code }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $airport->name }}</p>
                                </div>
                                <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $airport->country ?: '-' }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>Location</div>
                                <div>{{ trim(($airport->city ?? '') . (($airport->city && $airport->province) ? ', ' : '') . ($airport->province ?? '')) ?: '-' }}</div>
                                <div>Destination</div>
                                <div>{{ $airport->destination?->name ?? '-' }}</div>
                                <div>Status</div>
                                <div><x-status-badge :status="$airport->trashed() ? 'inactive' : 'active'" size="xs" /></div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('airports.show', $airport) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                                <a href="{{ route('airports.edit', $airport) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                <form action="{{ route('airports.toggle-status', $airport->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $airport->trashed() ? 'Activate this airport?' : 'Deactivate this airport?' }}')" class="{{ $airport->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $airport->trashed() ? 'Activate' : 'Deactivate' }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No airports available.</div>
                    @endforelse
                </div>
                <div>{{ $airports->links() }}</div>
            </div>
        </div>
</div>
@endsection


