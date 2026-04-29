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
                                {{ ui_phrase('Hotel') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ ui_phrase('Location') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ ui_phrase('Rooms') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ ui_phrase('Rates') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ ui_phrase('Status') }}</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                {{ ui_phrase('Actions') }}</th>
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
                                            title="{{ ui_phrase('View') }}" aria-label="{{ ui_phrase('View') }}"><i class="fa-solid fa-eye"></i><span
                                                class="sr-only">{{ ui_phrase('View') }}</span></a>
                                        <a href="{{ route('hotels.edit', $hotel) }}" class="btn-secondary-sm"
                                            title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span
                                                class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                                        <form action="{{ route('hotels.toggle-status', $hotel->id) }}"
                                            method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                onclick="return confirm('{{ $isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate') }}')"
                                                class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? ui_phrase('Deactivate') : ui_phrase('Activate') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Hotels')]) }}</td>
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
                        <div>{{ ui_phrase('Location') }}</div>
                        <div>{{ trim(($hotel->city ?? '') . (($hotel->city && $hotel->province) ? ', ' : '') . ($hotel->province ?? '')) ?: '-' }}</div>
                        <div>{{ ui_phrase('Country') }}</div>
                        <div>{{ $hotel->country ?: '-' }}</div>
                        <div>{{ ui_phrase('Destination') }}</div>
                        <div>{{ $hotel->destination?->province ?: ($hotel->destination?->name ?? '-') }}</div>
                        <div>{{ ui_phrase('Rooms') }}</div>
                        <div>{{ $hotel->rooms_count }}</div>
                        <div>{{ ui_phrase('Rates') }}</div>
                        <div>{{ $hotel->prices_count }}</div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('hotels.show', $hotel) }}" class="btn-outline-sm" title="{{ ui_phrase('View') }}"
                            aria-label="{{ ui_phrase('View') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('View') }}</span></a>
                        <a href="{{ route('hotels.edit', $hotel) }}" class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}"
                            aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                        <form action="{{ route('hotels.toggle-status', $hotel->id) }}" method="POST"
                            class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                onclick="return confirm('{{ $isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate') }}')"
                                class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? ui_phrase('Deactivate') : ui_phrase('Activate') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Hotels')]) }}</div>
            @endforelse
        </div>
        <div>{{ $hotels->links() }}</div>
    </div>
</div>




