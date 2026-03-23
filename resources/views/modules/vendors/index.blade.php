@extends('layouts.master')
@section('page_title', 'Vendors')
@section('page_subtitle', 'Manage vendor data.')
@section('page_actions')
    <a href="{{ route('vendors.create') }}" class="btn-primary">Add Vendor</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--vendors">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">No filters available.</div>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9">
                @if (session('success'))
                    <div
                        class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        {{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div
                        class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                        {{ session('error') }}</div>
                @endif
                <div class="md:hidden space-y-3">
                    @forelse ($vendors as $vendor)
                        <div class="app-card p-4">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $vendor->name }}</p>
                            <p class="text-xs text-indigo-600 dark:text-indigo-300">{{ $vendor->destination?->name ?? '-' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->city ?? '-' }} /
                                {{ $vendor->province ?? '-' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->contact_email ?? '-' }}</p>
                            <p class="mt-2 text-xs">
                                <x-status-badge :status="$vendor->is_active ? 'active' : 'inactive'" size="xs" />
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('vendors.edit', $vendor) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                <form action="{{ route('vendors.toggle-status', $vendor) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        onclick="return confirm('{{ $vendor->is_active ? 'Deactivate this vendor?' : 'Activate this vendor?' }}')"
                                        class="{{ $vendor->is_active ? 'btn-muted-sm' : 'btn-primary-sm' }}">
                                        {{ $vendor->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No vendors available.
                        </div>
                    @endforelse
                </div>
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Name</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        City / Province</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Contact</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($vendors as $index=>$vendor)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ ++$index }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $vendor->name }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $vendor->city ?? '-' }} / {{ $vendor->province ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $vendor->contact_name ?? '-' }}<br>
                                            {{ $vendor->contact_email ?? '-' }}<br>
                                            {{ $vendor->contact_phone ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <x-status-badge :status="$vendor->is_active ? 'active' : 'inactive'" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('vendors.edit', $vendor) }}"
                                                    class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                                <form action="{{ route('vendors.toggle-status', $vendor) }}" method="POST"
                                                    class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        onclick="return confirm('{{ $vendor->is_active ? 'Deactivate this vendor?' : 'Activate this vendor?' }}')"
                                                        class="{{ $vendor->is_active ? 'btn-muted-sm' : 'btn-primary-sm' }}">
                                                        {{ $vendor->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No
                                            vendors available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>{{ $vendors->links() }}</div>
            </div>
        </div>
    </div>
@endsection

