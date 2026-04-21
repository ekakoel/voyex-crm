<div data-hotels-index-results>
    <div class="space-y-4">
        @if (session('success'))
            <div
                class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}</div>
        @endif
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
                                {{ __('ui.modules.hotels.hotel') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ __('ui.common.location') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ __('ui.common.rooms') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ __('ui.common.rates') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ __('ui.common.status') }}</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                {{ __('ui.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($hotels as $index=>$hotel)
                            @php
                                $rawStatus = strtolower((string) ($hotel->status ?? 'active'));
                                $isActive = !$hotel->trashed() && $rawStatus === 'active';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ ++$index }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <div>{{ $hotel->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <div>{{ trim(($hotel->city ?? '') . (($hotel->city && $hotel->province) ? ', ' : '') . ($hotel->province ?? '')) ?: '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $hotel->country ?: '-' }}</div>
                                    <div class="text-xs text-indigo-600 dark:text-indigo-300">{{ $hotel->destination?->province ?: ($hotel->destination?->name ?? '-') }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-200">
                                    {{ $hotel->rooms_count }}</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-200">
                                    {{ $hotel->prices_count }}</td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('hotels.show', $hotel) }}" class="btn-outline-sm"
                                            title="{{ __('ui.common.view') }}" aria-label="{{ __('ui.common.view') }}"><i class="fa-solid fa-eye"></i><span
                                                class="sr-only">{{ __('ui.common.view') }}</span></a>
                                        <a href="{{ route('hotels.edit', $hotel) }}" class="btn-secondary-sm"
                                            title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span
                                                class="sr-only">{{ __('ui.common.edit') }}</span></a>
                                        <form action="{{ route('hotels.toggle-status', $hotel->id) }}"
                                            method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                onclick="return confirm('{{ $isActive ? __('ui.modules.hotels.confirm_deactivate') : __('ui.modules.hotels.confirm_activate') }}')"
                                                class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? __('ui.common.deactivate') : __('ui.common.activate') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.hotels')]) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($hotels as $hotel)
                @php
                    $rawStatus = strtolower((string) ($hotel->status ?? 'active'));
                    $isActive = !$hotel->trashed() && $rawStatus === 'active';
                @endphp
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $hotel->code }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $hotel->name }}</p>
                        </div>
                        <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ __('ui.common.location') }}</div>
                        <div>{{ trim(($hotel->city ?? '') . (($hotel->city && $hotel->province) ? ', ' : '') . ($hotel->province ?? '')) ?: '-' }}</div>
                        <div>{{ __('ui.common.country') }}</div>
                        <div>{{ $hotel->country ?: '-' }}</div>
                        <div>{{ __('ui.common.destination') }}</div>
                        <div>{{ $hotel->destination?->province ?: ($hotel->destination?->name ?? '-') }}</div>
                        <div>{{ __('ui.common.rooms') }}</div>
                        <div>{{ $hotel->rooms_count }}</div>
                        <div>{{ __('ui.common.rates') }}</div>
                        <div>{{ $hotel->prices_count }}</div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('hotels.show', $hotel) }}" class="btn-outline-sm" title="{{ __('View') }}"
                            aria-label="{{ __('ui.common.view') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ __('ui.common.view') }}</span></a>
                        <a href="{{ route('hotels.edit', $hotel) }}" class="btn-secondary-sm" title="{{ __('Edit') }}"
                            aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                        <form action="{{ route('hotels.toggle-status', $hotel->id) }}" method="POST"
                            class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                onclick="return confirm('{{ $isActive ? __('ui.modules.hotels.confirm_deactivate') : __('ui.modules.hotels.confirm_activate') }}')"
                                class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? __('ui.common.deactivate') : __('ui.common.activate') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.hotels')]) }}</div>
            @endforelse
        </div>
        <div>{{ $hotels->links() }}</div>
    </div>
</div>




