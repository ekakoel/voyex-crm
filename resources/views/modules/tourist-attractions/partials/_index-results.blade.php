<div data-tourist-attractions-index-results>
    <div class="space-y-4">
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ideal Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Rates / Pax</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($touristAttractions as $index => $touristAttraction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                @php($isActive = ! $touristAttraction->trashed())
                                <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                    <div>{{ $touristAttraction->name }}</div>
                                    <div class="text-xs text-indigo-600 dark:text-indigo-300">{{ $touristAttraction->destination?->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $touristAttraction->ideal_visit_minutes }} min</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <div>Contract: {{ $touristAttraction->contract_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->contract_rate_per_pax, 'IDR') : '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Publish:
                                        {{ $touristAttraction->publish_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->publish_rate_per_pax, 'IDR') : '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($touristAttraction->city ?? '') . (($touristAttraction->city && $touristAttraction->province) ? ', ' : '') . ($touristAttraction->province ?? '')) ?: '-' }}<div class="text-xs text-gray-500 dark:text-gray-400">{{ $touristAttraction->country ?? '-' }}</div></td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('tourist-attractions.edit', $touristAttraction) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
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
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No tourist attractions available.</td>
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
                    <p class="text-xs text-gray-500 dark:text-gray-400">Contract: {{ $touristAttraction->contract_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->contract_rate_per_pax, 'IDR') : '-' }} / pax</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Publish: {{ $touristAttraction->publish_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->publish_rate_per_pax, 'IDR') : '-' }} / pax</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ trim(($touristAttraction->city ?? '') . (($touristAttraction->city && $touristAttraction->province) ? ', ' : '') . ($touristAttraction->province ?? '')) ?: '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $touristAttraction->country ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Status: <x-status-badge :status="$touristAttraction->trashed() ? 'inactive' : 'active'" size="xs" /></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('tourist-attractions.edit', $touristAttraction) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
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
