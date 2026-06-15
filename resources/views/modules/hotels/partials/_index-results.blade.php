@php($canManageActivationActions = auth()->user()?->canManageActivationActions() === true)
<div data-hotels-index-results>
    <div class="space-y-4">
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="table-header">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                #</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Hotel') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Location') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Rooms') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Rates') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Status') }}</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
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
                                    <x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                        <a href="{{ route('hotels.show', $hotel) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('View') }}</span>
                                        </a>
                                        <a href="{{ route('hotels.edit', $hotel) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Edit') }}</span>
                                        </a>
                                        @if ($canManageActivationActions)
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action
                                            :action="route('hotels.toggle-status', $hotel->id)"
                                            method="PATCH"
                                            :modal-name="'hotels-index-toggle-desktop-' . $hotel->id"
                                            :title="$isActive ? ui_phrase('Deactivate') . ' ' . ui_phrase('Hotel') : ui_phrase('Activate') . ' ' . ui_phrase('Hotel')"
                                            :message="$isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate')"
                                            :notice-message="__('confirm.notification_after_action')"
                                            :confirm-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                            :trigger-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                            :trigger-icon="$isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'"
                                            :trigger-class="$isActive ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
                                            confirm-class="btn-primary-sm"
                                        />
                                        @endif
                                    </x-ui.table-action-dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6">
                                    <x-ui.empty-state
                                        :title="ui_phrase('No hotels found.')"
                                        :description="ui_phrase('Create a new hotel or adjust your filters.')"
                                    />
                                </td>
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
                <div class="app-card relative p-4 pt-5">
                    <div class="absolute right-3 top-3 z-10">
                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                            <a href="{{ route('hotels.show', $hotel) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('View') }}</span>
                            </a>
                            <a href="{{ route('hotels.edit', $hotel) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Edit') }}</span>
                            </a>
                            @if ($canManageActivationActions)
                            <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                            <x-ui.confirm-action
                                :action="route('hotels.toggle-status', $hotel->id)"
                                method="PATCH"
                                :modal-name="'hotels-index-toggle-mobile-' . $hotel->id"
                                :title="$isActive ? ui_phrase('Deactivate') . ' ' . ui_phrase('Hotel') : ui_phrase('Activate') . ' ' . ui_phrase('Hotel')"
                                :message="$isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate')"
                                :notice-message="__('confirm.notification_after_action')"
                                :confirm-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                :trigger-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                :trigger-icon="$isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'"
                                :trigger-class="$isActive ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
                                confirm-class="btn-primary-sm"
                            />
                            @endif
                        </x-ui.table-action-dropdown>
                    </div>
                    <div class="flex items-start justify-between gap-3 pr-12">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $hotel->name }}
                            </p>
                        </div>
                        <x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
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
                </div>
            @empty
                <x-module-empty-state
                    :title="ui_phrase('No hotels found.')"
                    :message="ui_phrase('Create a new hotel or adjust your filters.')"
                />
            @endforelse
        </div>
        <div>{{ $hotels->links() }}</div>
    </div>
</div>



