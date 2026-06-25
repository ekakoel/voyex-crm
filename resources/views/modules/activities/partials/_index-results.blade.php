<div data-activities-index-results>
    <div class="space-y-4">
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Activity') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Type') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Duration') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('rate per pax') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                            <th class="actions-compact px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($activityRows as $row)
                            @php($activity = $row['activity'])
                            <tr class="{{ $row['needs_data_attention'] ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row['row_number'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                    <div>{{ $row['name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['vendor_name'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['type_label'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['duration_label'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    @foreach ($row['rate_lines'] as $rateLine)
                                        <div @class(['text-xs text-gray-500 dark:text-gray-400' => ! in_array($rateLine['label'], ['ACR', 'CCR'], true)])>
                                            {{ $rateLine['label'] }}:
                                            @if ($rateLine['is_money'])
                                                <x-money :amount="(float) $rateLine['value']" currency="IDR" />
                                            @else
                                                {{ rtrim(rtrim(number_format((float) $rateLine['value'], 2, '.', ''), '0'), '.') }}%
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <x-ui.status-badge :status="$row['status']" size="xs" />
                                </td>
                                <td class="actions-compact px-4 py-3 text-right text-sm">
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
                                                :title="$row['is_active'] ? ui_phrase('Deactivate') . ' ' . ui_phrase('Activity') : ui_phrase('Activate') . ' ' . ui_phrase('Activity')"
                                                :message="$row['is_active'] ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate')"
                                                :notice-message="__('confirm.notification_after_action')"
                                                :confirm-label="$row['is_active'] ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                                :trigger-label="$row['is_active'] ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                                :trigger-icon="$row['is_active'] ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'"
                                                :trigger-class="$row['is_active'] ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
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
        <div class="space-y-3 md:hidden">
            @forelse ($activityRows as $row)
                @php($activity = $row['activity'])
                <div class="app-card relative p-4 pt-5 {{ $row['needs_data_attention'] ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }}">
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
                                    :title="$row['is_active'] ? ui_phrase('Deactivate') . ' ' . ui_phrase('Activity') : ui_phrase('Activate') . ' ' . ui_phrase('Activity')"
                                    :message="$row['is_active'] ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate')"
                                    :notice-message="__('confirm.notification_after_action')"
                                    :confirm-label="$row['is_active'] ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                    :trigger-label="$row['is_active'] ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                    :trigger-icon="$row['is_active'] ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'"
                                    :trigger-class="$row['is_active'] ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
                                    confirm-class="btn-primary-sm"
                                />
                            @endif
                        </x-ui.table-action-dropdown>
                    </div>
                    <div class="flex items-start justify-between gap-3 pr-12">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $row['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['vendor_name'] }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $row['type_label'] }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ ui_phrase('Duration') }}</div>
                        <div>{{ $row['duration_label'] }}</div>
                        <div>{{ ui_phrase('Rate') }}</div>
                        <div>
                            @foreach ($row['rate_lines'] as $rateLine)
                                <div>
                                    {{ $rateLine['label'] }}:
                                    @if ($rateLine['is_money'])
                                        <x-money :amount="(float) $rateLine['value']" currency="IDR" />
                                    @else
                                        {{ rtrim(rtrim(number_format((float) $rateLine['value'], 2, '.', ''), '0'), '.') }}%
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div>{{ ui_phrase('Status') }}</div>
                        <div><x-ui.status-badge :status="$row['status']" size="xs" /></div>
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
