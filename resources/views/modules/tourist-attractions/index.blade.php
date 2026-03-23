@extends('layouts.master')
@section('page_title', 'Tourist Attractions')
@section('page_subtitle', 'Manage attraction data.')
@section('page_actions')
    <a href="{{ route('tourist-attractions.create') }}" class="btn-primary">Add Attraction</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--tourist-attractions">
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
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ideal Duration</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Pricing / Pax</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">City / Province</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($touristAttractions as $touristAttraction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            @php($isActive = ! $touristAttraction->trashed())
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                <div>{{ $touristAttraction->name }}</div>
                                <div class="text-xs text-indigo-600 dark:text-indigo-300">{{ $touristAttraction->destination?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $touristAttraction->ideal_visit_minutes }} min</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>Entrance: {{ $touristAttraction->entrance_fee_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->entrance_fee_per_pax, $touristAttraction->currency ?? 'IDR') : '-' }} / pax</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $touristAttraction->other_fee_label ?: 'Other Fee' }}:
                                    {{ $touristAttraction->other_fee_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->other_fee_per_pax, $touristAttraction->currency ?? 'IDR') : '-' }} / pax
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $touristAttraction->city ?? '-' }} / {{ $touristAttraction->province ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-sm">
                                <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('tourist-attractions.edit', $touristAttraction) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                <form action="{{ route('tourist-attractions.toggle-status', $touristAttraction->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $isActive ? 'Deactivate this attraction?' : 'Activate this attraction?' }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
                                </form>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No tourist attractions available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($touristAttractions as $touristAttraction)
                <div class="app-card p-4">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $touristAttraction->name }}</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-300">{{ $touristAttraction->destination?->name ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ideal: {{ $touristAttraction->ideal_visit_minutes }} min</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Entrance: {{ $touristAttraction->entrance_fee_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->entrance_fee_per_pax, $touristAttraction->currency ?? 'IDR') : '-' }} / pax</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $touristAttraction->other_fee_label ?: 'Other Fee' }}: {{ $touristAttraction->other_fee_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->other_fee_per_pax, $touristAttraction->currency ?? 'IDR') : '-' }} / pax</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $touristAttraction->city ?? '-' }} / {{ $touristAttraction->province ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Status: <x-status-badge :status="$touristAttraction->trashed() ? 'inactive' : 'active'" size="xs" /></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('tourist-attractions.edit', $touristAttraction) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                        <form action="{{ route('tourist-attractions.toggle-status', $touristAttraction->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" onclick="return confirm('{{ $touristAttraction->trashed() ? 'Activate this attraction?' : 'Deactivate this attraction?' }}')" class="{{ $touristAttraction->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $touristAttraction->trashed() ? 'Activate' : 'Deactivate' }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No tourist attractions available.</div>
            @endforelse
        </div>
        <div>{{ $touristAttractions->links() }}</div>
            </div>
        </div>
</div>
@endsection



