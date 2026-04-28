<div data-activities-index-results>
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
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_activities_activity') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_type') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_duration') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_activities_rate_per_pax') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('common_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($activities as $index => $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                @php($isActive = ! $activity->trashed())
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
                                    <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('activities.show', $activity->id) }}" class="btn-outline-sm" title="{{ ui_phrase('common_detail') }}" aria-label="{{ ui_phrase('common_detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('common_detail') }}</span></a>
                                        <a href="{{ route('activities.edit', $activity) }}" class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                                        <form action="{{ route('activities.toggle-status', $activity->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('{{ $isActive ? ui_phrase('modules_activities_confirm_deactivate') : ui_phrase('modules_activities_confirm_activate') }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? ui_phrase('common_deactivate') : ui_phrase('common_activate') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_activities')]) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($activities as $activity)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $activity->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->vendor->name ?? '-' }}</p>
                        </div>
                        <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $activity->activityType->name ?? $activity->activity_type ?? '-' }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ ui_phrase('common_duration') }}</div>
                        <div>{{ $activity->duration_minutes }} min</div>
                        <div>{{ ui_phrase('common_rate') }}</div>
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
                        <div>{{ ui_phrase('common_status') }}</div>
                        <div><x-status-badge :status="$activity->trashed() ? 'inactive' : 'active'" size="xs" /></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('activities.show', $activity->id) }}" class="btn-outline-sm" title="{{ ui_phrase('common_detail') }}" aria-label="{{ ui_phrase('common_detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('common_detail') }}</span></a>
                        <a href="{{ route('activities.edit', $activity) }}" class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                        <form action="{{ route('activities.toggle-status', $activity->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" onclick="return confirm('{{ $activity->trashed() ? ui_phrase('modules_activities_confirm_activate') : ui_phrase('modules_activities_confirm_deactivate') }}')" class="{{ $activity->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $activity->trashed() ? ui_phrase('common_activate') : ui_phrase('common_deactivate') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_activities')]) }}</div>
            @endforelse
        </div>
        <div>{{ $activities->links() }}</div>
    </div>
</div>
