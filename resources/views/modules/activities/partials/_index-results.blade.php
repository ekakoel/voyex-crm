<div data-activities-index-results>
    <div class="space-y-4">
        @php
            $manualSeedMarker = 'draft created from itinerary day planner quick add';
        @endphp
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Activity') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Type') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Duration') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('rate per pax') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
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
                                    <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('activities.show', $activity->id) }}" class="btn-outline-sm" title="{{ ui_phrase('Detail') }}" aria-label="{{ ui_phrase('Detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('Detail') }}</span></a>
                                        <a href="{{ route('activities.edit', $activity) }}" class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                                        <form action="{{ route('activities.toggle-status', $activity->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('{{ $isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate') }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? ui_phrase('Deactivate') : ui_phrase('Activate') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Activities')]) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($activities as $activity)
                @php
                    $galleryImages = is_array($activity->gallery_images ?? null) ? $activity->gallery_images : [];
                    $hasGalleryImages = count($galleryImages) > 0;
                    $hasDestination = (int) ($activity->vendor?->destination_id ?? 0) > 0;
                    $hasActivityType = (int) ($activity->activity_type_id ?? 0) > 0
                        || trim((string) ($activity->activity_type ?? '')) !== '';
                    $needsDataAttention = ! $hasGalleryImages || ! $hasDestination || ! $hasActivityType;
                @endphp
                <div class="app-card p-4 {{ $needsDataAttention ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
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
                        <div><x-status-badge :status="$activity->trashed() ? 'inactive' : 'active'" size="xs" /></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('activities.show', $activity->id) }}" class="btn-outline-sm" title="{{ ui_phrase('Detail') }}" aria-label="{{ ui_phrase('Detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('Detail') }}</span></a>
                        <a href="{{ route('activities.edit', $activity) }}" class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                        <form action="{{ route('activities.toggle-status', $activity->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" onclick="return confirm('{{ $activity->trashed() ? ui_phrase('confirm activate') : ui_phrase('confirm deactivate') }}')" class="{{ $activity->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $activity->trashed() ? ui_phrase('Activate') : ui_phrase('Deactivate') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Activities')]) }}</div>
            @endforelse
        </div>
        <div>{{ $activities->links() }}</div>
    </div>
</div>
