@extends('layouts.master')
@section('page_title', 'Vendors')
@section('page_subtitle', 'Manage vendor data.')
@section('page_actions')
    <a href="{{ route('vendors.create') }}" class="btn-primary">Add Vendor</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--vendors" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" action="{{ route('vendors.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="Search name/location/contact" class="app-input sm:col-span-2" data-service-filter-input>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}/page</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('vendors.index') }}" class="btn-ghost" data-service-filter-reset>Reset</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-service-filter-results>
                @if (session('success'))
                    <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ session('error') }}</div>
                @endif

                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Vendor</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Location</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Services</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($vendors as $index => $vendor)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $vendors->firstItem() + $index }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div>{{ $vendor->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->destination?->name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($vendor->city ?? '') . (($vendor->city && $vendor->province) ? ', ' : '') . ($vendor->province ?? '')) ?: '-' }}<div class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->country ?? '-' }}</div></td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-200">
                                            A: {{ (int) ($vendor->activities_count ?? 0) }} |
                                            F&B: {{ (int) ($vendor->food_beverages_count ?? 0) }} |
                                            TR: {{ (int) ($vendor->transports_count ?? 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <x-status-badge :status="$vendor->is_active ? 'active' : 'inactive'" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('vendors.edit', $vendor) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                                <form action="{{ route('vendors.toggle-status', $vendor) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" onclick="return confirm('{{ $vendor->is_active ? 'Deactivate this vendor?' : 'Activate this vendor?' }}')" class="{{ $vendor->is_active ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $vendor->is_active ? 'Deactivate' : 'Activate' }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No vendors available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="md:hidden space-y-3">
                    @forelse ($vendors as $vendor)
                        <div class="app-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $vendor->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->destination?->name ?? '-' }}</p>
                                </div>
                                <x-status-badge :status="$vendor->is_active ? 'active' : 'inactive'" size="xs" />
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>Location</div>
                                <div>{{ trim(($vendor->city ?? '') . (($vendor->city && $vendor->province) ? ', ' : '') . ($vendor->province ?? '')) ?: '-' }}</div>
                                <div>Country</div>
                                <div>{{ $vendor->country ?? '-' }}</div>
                                <div>Linked</div>
                                <div>
                                    A: {{ (int) ($vendor->activities_count ?? 0) }} |
                                    F&B: {{ (int) ($vendor->food_beverages_count ?? 0) }} |
                                    TR: {{ (int) ($vendor->transports_count ?? 0) }}
                                </div>
                                <div>Contact</div>
                                <div>{{ $vendor->contact_email ?? '-' }}</div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('vendors.edit', $vendor) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                <form action="{{ route('vendors.toggle-status', $vendor) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $vendor->is_active ? 'Deactivate this vendor?' : 'Activate this vendor?' }}')" class="{{ $vendor->is_active ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $vendor->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No vendors available.</div>
                    @endforelse
                </div>

                <div>{{ $vendors->links() }}</div>
            </div>
        </div>
    </div>
@endsection




