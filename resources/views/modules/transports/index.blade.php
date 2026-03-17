@extends('layouts.master')
@section('page_title', 'Transports')
@section('page_subtitle', 'Manage transport data.')
@section('page_actions')
    <a href="{{ route('transports.create') }}" class="btn-primary">Add Transport</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--transports">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <input name="q" value="{{ request('q') }}" placeholder="Search code/name/provider/city" class="app-input sm:col-span-2">
                        <select name="transport_type" class="app-input">
                            <option value="">All Types</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected((string) request('transport_type') === (string) $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <button class="btn-primary">Filter</button>
                            <a href="{{ route('transports.index') }}" class="btn-ghost">Reset</a>
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
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Service</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Units</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Min Contract Rate</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($transports as $transport)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            @php($isActive = ! $transport->trashed())
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $transport->code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $transport->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $transport->provider_name ?: '-' }}</div>
                                <div class="text-xs text-indigo-600 dark:text-indigo-300">{{ $transport->destination?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $transport->transport_type)) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($transport->city ?? '') . (($transport->city && $transport->province) ? ', ' : '') . ($transport->province ?? '')) ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $transport->units_count }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @if ($transport->units_min_contract_rate !== null)
                                    <x-money :amount="(float) $transport->units_min_contract_rate" currency="IDR" />
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300' }}">
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('transports.show', $transport) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                                <a href="{{ route('transports.edit', $transport) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                <form action="{{ route('transports.toggle-status', $transport->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $isActive ? 'Deactivate this transport service?' : 'Activate this transport service?' }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
                                </form>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No transport services available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($transports as $transport)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $transport->code }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transport->name }}</p>
                        </div>
                        <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $transport->transport_type)) }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>Provider</div>
                        <div>{{ $transport->provider_name ?: '-' }}</div>
                        <div>Location</div>
                        <div>{{ trim(($transport->city ?? '') . (($transport->city && $transport->province) ? ', ' : '') . ($transport->province ?? '')) ?: '-' }}</div>
                        <div>Units</div>
                        <div>{{ $transport->units_count }}</div>
                        <div>Min Rate</div>
                        <div>
                            @if ($transport->units_min_contract_rate !== null)
                                <x-money :amount="(float) $transport->units_min_contract_rate" currency="IDR" />
                            @else
                                -
                            @endif
                        </div>
                        <div>Status</div>
                        <div>{{ $transport->trashed() ? 'Inactive' : 'Active' }}</div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('transports.show', $transport) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                        <a href="{{ route('transports.edit', $transport) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                        <form action="{{ route('transports.toggle-status', $transport->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" onclick="return confirm('{{ $transport->trashed() ? 'Activate this transport?' : 'Deactivate this transport?' }}')" class="{{ $transport->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $transport->trashed() ? 'Activate' : 'Deactivate' }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No transports available.</div>
            @endforelse
        </div>
        <div>{{ $transports->links() }}</div>
            </div>
        </div>
</div>
@endsection


