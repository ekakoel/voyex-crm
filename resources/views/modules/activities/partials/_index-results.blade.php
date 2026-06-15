<div data-activities-index-results>
    <div class="space-y-4">
        @php
            $manualSeedMarker = 'draft created from itinerary day planner quick add';
            $canManageActivationActions = auth()->user()?->canManageActivationActions() === true;
        @endphp
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Activity') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Type') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Duration') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('rate per pax') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($activities as $index => $activity)
                            @php
                                $isActive = ! $activity->trashed();
                                $galleryImages = is_array($activity->gallery_images ?? null) ? $activity->gallery_images : [];
                                $hasGalleryImages = count($galleryImages) > 0;
                                $hasDestination = (int) ($activity->vendor?->destination_id ?? 0) > 0;
                                $hasActivityType = (int) ($activity->activity_type_id ?? 0) > 0
                                    || trim((string) ($activity->activity_type ?? '')) !== '';
                                $needsDataAttention = ! $hasGalleryImages || ! $hasDestination || ! $hasActivityType;
                            @endphp
                            <tr class="{{ $needsDataAttention ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                    <div>{{ $activity->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->vendor->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $activity->activityType->name ?? $activity->activity_type ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $activity->duration_minutes }} min</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    @if ($activity->adult_contract_rate !== null)
                                        <div>ACR: <x-money :amount="(float) $activity->adult_contract_rate" currency="IDR" /></div>
                                    @endif
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        AM:
                                        {{ ($activity->adult_markup_type ?? 'fixed') === 'percent'
                                            ? rtrim(rtrim(number_format((float) ($activity->adult_markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                            : \App\Support\Currency::format((float) ($activity->adult_markup ?? 0), 'IDR') }}
                                    </div>
                                    @if ($activity->adult_publish_rate !== null)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">APR: <x-money :amount="(float) $activity->adult_publish_rate" currency="IDR" /></div>
                                    @endif
                                    @if ($activity->child_contract_rate !== null)
                                        <div>CCR: <x-money :amount="(float) $activity->child_contract_rate" currency="IDR" /></div>
                                    @endif
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        CM:
                                        {{ ($activity->child_markup_type ?? 'fixed') === 'percent'
                                            ? rtrim(rtrim(number_format((float) ($activity->child_markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                            : \App\Support\Currency::format((float) ($activity->child_markup ?? 0), 'IDR') }}
                                    </div>
                                    @if ($activity->child_publish_rate !== null)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">CPR: <x-money :amount="(float) $activity->child_publish_rate" currency="IDR" /></div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                        <a href="{{ route('activities.show', $activity->id) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Detail') }}</span>
                                        </a>
                                        <a href="{{ route('activities.edit', $activity) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Edit') }}</span>
                                        </a>
                                        @if ($canManageActivationActions)
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action
                                            :action="route('activities.toggle-status', $activity->id)"
                                            method="PATCH"
                                            :modal-name="'activities-index-toggle-desktop-' . $activity->id"
                                            :title="$isActive ? ui_phrase('Deactivate') . ' ' . ui_phrase('Activity') : ui_phrase('Activate') . ' ' . ui_phrase('Activity')"
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
                                <td colspan="8" class="px-4 py-6">
                                    <x-ui.empty-state
                                        :title="ui_phrase('No activities found.')"
                                        :description="ui_phrase('Create a new activity or adjust your filters.')"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($activities as $activity)
                @php
                    $isActive = ! $activity->trashed();
                    $galleryImages = is_array($activity->gallery_images ?? null) ? $activity->gallery_images : [];
                    $hasGalleryImages = count($galleryImages) > 0;
                    $hasDestination = (int) ($activity->vendor?->destination_id ?? 0) > 0;
                    $hasActivityType = (int) ($activity->activity_type_id ?? 0) > 0
                        || trim((string) ($activity->activity_type ?? '')) !== '';
                    $needsDataAttention = ! $hasGalleryImages || ! $hasDestination || ! $hasActivityType;
                @endphp
                <div class="app-card relative p-4 pt-5 {{ $needsDataAttention ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }}">
                    <div class="absolute right-3 top-3 z-10">
                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                            <a href="{{ route('activities.show', $activity->id) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Detail') }}</span>
                            </a>
                            <a href="{{ route('activities.edit', $activity) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Edit') }}</span>
                            </a>
                            @if ($canManageActivationActions)
                            <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                            <x-ui.confirm-action
                                :action="route('activities.toggle-status', $activity->id)"
                                method="PATCH"
                                :modal-name="'activities-index-toggle-mobile-' . $activity->id"
                                :title="$isActive ? ui_phrase('Deactivate') . ' ' . ui_phrase('Activity') : ui_phrase('Activate') . ' ' . ui_phrase('Activity')"
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
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $activity->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->vendor->name ?? '-' }}</p>
                        </div>
                        <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $activity->activityType->name ?? $activity->activity_type ?? '-' }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ ui_phrase('Duration') }}</div>
                        <div>{{ $activity->duration_minutes }} min</div>
                        <div>{{ ui_phrase('Rate') }}</div>
                        <div>
                            @if ($activity->adult_contract_rate !== null)
                                <div>ACR: <x-money :amount="(float) $activity->adult_contract_rate" currency="IDR" /></div>
                            @endif
                            <div>
                                AM:
                                {{ ($activity->adult_markup_type ?? 'fixed') === 'percent'
                                    ? rtrim(rtrim(number_format((float) ($activity->adult_markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                    : \App\Support\Currency::format((float) ($activity->adult_markup ?? 0), 'IDR') }}
                            </div>
                            @if ($activity->adult_publish_rate !== null)
                                <div>APR: <x-money :amount="(float) $activity->adult_publish_rate" currency="IDR" /></div>
                            @endif
                            @if ($activity->child_contract_rate !== null)
                                <div>CCR: <x-money :amount="(float) $activity->child_contract_rate" currency="IDR" /></div>
                            @endif
                            <div>
                                CM:
                                {{ ($activity->child_markup_type ?? 'fixed') === 'percent'
                                    ? rtrim(rtrim(number_format((float) ($activity->child_markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                    : \App\Support\Currency::format((float) ($activity->child_markup ?? 0), 'IDR') }}
                            </div>
                            @if ($activity->child_publish_rate !== null)
                                <div>CPR: <x-money :amount="(float) $activity->child_publish_rate" currency="IDR" /></div>
                            @endif
                        </div>
                        <div>{{ ui_phrase('Status') }}</div>
                        <div><x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" /></div>
                    </div>
                </div>
            @empty
                <x-module-empty-state
                    :title="ui_phrase('No activities found.')"
                    :message="ui_phrase('Create a new activity or adjust your filters.')"
                />
            @endforelse
        </div>
        <div>{{ $activities->links() }}</div>
    </div>
</div>

