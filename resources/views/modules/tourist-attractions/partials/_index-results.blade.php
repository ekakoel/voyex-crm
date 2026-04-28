<div data-tourist-attractions-index-results>
    <div class="space-y-4">
        @php($canDeleteTouristAttraction = auth()->user()?->isSuperAdmin() ?? false)
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ session('error') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_tourist_attractions_ideal_duration') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_tourist_attractions_rates_per_pax') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_location') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('common_actions') }}</th>
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
                                    <div>{{ ui_phrase('modules_tourist_attractions_contract') }}: {{ $touristAttraction->contract_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->contract_rate_per_pax, 'IDR') : '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('modules_tourist_attractions_markup') }}:
                                        {{ ($touristAttraction->markup_type ?? 'fixed') === 'percent'
                                            ? rtrim(rtrim(number_format((float) ($touristAttraction->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                            : \App\Support\Currency::format((float) ($touristAttraction->markup ?? 0), 'IDR') }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('modules_tourist_attractions_publish') }}:
                                        {{ $touristAttraction->publish_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->publish_rate_per_pax, 'IDR') : '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($touristAttraction->city ?? '') . (($touristAttraction->city && $touristAttraction->province) ? ', ' : '') . ($touristAttraction->province ?? '')) ?: '-' }}<div class="text-xs text-gray-500 dark:text-gray-400">{{ $touristAttraction->country ?? '-' }}</div></td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('tourist-attractions.edit', $touristAttraction) }}" class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                                        <form action="{{ route('tourist-attractions.toggle-status', $touristAttraction->id) }}" method="POST" class="inline" data-tourist-attractions-toggle-form>
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('{{ $isActive ? ui_phrase('modules_tourist_attractions_confirm_deactivate') : ui_phrase('modules_tourist_attractions_confirm_activate') }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? ui_phrase('common_deactivate') : ui_phrase('common_activate') }}</button>
                                        </form>
                                        @if ($canDeleteTouristAttraction)
                                            <form action="{{ route('tourist-attractions.destroy', $touristAttraction) }}" method="POST" class="inline" data-tourist-attractions-delete-form>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger-sm">
                                                    {{ ui_phrase('common_delete') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_tourist_attractions')]) }}</td>
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
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_tourist_attractions_ideal') }}: {{ $touristAttraction->ideal_visit_minutes }} min</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_tourist_attractions_contract') }}: {{ $touristAttraction->contract_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->contract_rate_per_pax, 'IDR') : '-' }} / pax</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ ui_phrase('modules_tourist_attractions_markup') }}:
                        {{ ($touristAttraction->markup_type ?? 'fixed') === 'percent'
                            ? rtrim(rtrim(number_format((float) ($touristAttraction->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                            : \App\Support\Currency::format((float) ($touristAttraction->markup ?? 0), 'IDR') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_tourist_attractions_publish') }}: {{ $touristAttraction->publish_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->publish_rate_per_pax, 'IDR') : '-' }} / pax</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ trim(($touristAttraction->city ?? '') . (($touristAttraction->city && $touristAttraction->province) ? ', ' : '') . ($touristAttraction->province ?? '')) ?: '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $touristAttraction->country ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_status') }}: <x-status-badge :status="$touristAttraction->trashed() ? 'inactive' : 'active'" size="xs" /></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('tourist-attractions.edit', $touristAttraction) }}" class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                        <form action="{{ route('tourist-attractions.toggle-status', $touristAttraction->id) }}" method="POST" class="inline" data-tourist-attractions-toggle-form>
                            @csrf
                            @method('PATCH')
                            <button type="submit" onclick="return confirm('{{ $touristAttraction->trashed() ? ui_phrase('modules_tourist_attractions_confirm_activate') : ui_phrase('modules_tourist_attractions_confirm_deactivate') }}')" class="{{ $touristAttraction->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $touristAttraction->trashed() ? ui_phrase('common_activate') : ui_phrase('common_deactivate') }}</button>
                        </form>
                        @if ($canDeleteTouristAttraction)
                            <form action="{{ route('tourist-attractions.destroy', $touristAttraction) }}" method="POST" class="inline" data-tourist-attractions-delete-form>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger-sm">
                                    {{ ui_phrase('common_delete') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_tourist_attractions')]) }}</div>
            @endforelse
        </div>
        <div>{{ $touristAttractions->links() }}</div>
    </div>
</div>
