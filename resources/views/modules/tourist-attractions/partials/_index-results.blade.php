<div data-tourist-attractions-index-results>
    <div class="space-y-4">
        @php
            $canDeleteTouristAttraction = auth()->user()?->isSuperAdmin() ?? false;
        @endphp
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('attractions ideal duration') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('attractions rates per pax') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Location') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($touristAttractions as $index => $touristAttraction)
                            @php
                                $isActive = ! $touristAttraction->trashed();
                                $galleryImages = is_array($touristAttraction->gallery_images ?? null) ? $touristAttraction->gallery_images : [];
                                $hasGalleryImages = count($galleryImages) > 0;
                                $hasGoogleMapsUrl = trim((string) ($touristAttraction->google_maps_url ?? '')) !== '';
                                $needsDataAttention = ! $hasGalleryImages || ! $hasGoogleMapsUrl;
                            @endphp
                            <tr class="{{ $needsDataAttention ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                    <div>{{ $touristAttraction->name }}</div>
                                    <div class="text-xs text-indigo-600 dark:text-indigo-300">{{ $touristAttraction->destination?->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $touristAttraction->ideal_visit_minutes }} min</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <div>{{ ui_phrase('attractions contract') }}: {{ $touristAttraction->contract_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->contract_rate_per_pax, 'IDR') : '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('attractions markup') }}:
                                        {{ ($touristAttraction->markup_type ?? 'fixed') === 'percent'
                                            ? rtrim(rtrim(number_format((float) ($touristAttraction->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                            : \App\Support\Currency::format((float) ($touristAttraction->markup ?? 0), 'IDR') }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('attractions publish') }}:
                                        {{ $touristAttraction->publish_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->publish_rate_per_pax, 'IDR') : '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($touristAttraction->city ?? '') . (($touristAttraction->city && $touristAttraction->province) ? ', ' : '') . ($touristAttraction->province ?? '')) ?: '-' }}<div class="text-xs text-gray-500 dark:text-gray-400">{{ $touristAttraction->country ?? '-' }}</div></td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                        <a href="{{ route('tourist-attractions.edit', $touristAttraction) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Edit') }}</span>
                                        </a>
                                        <x-ui.confirm-action
                                            :action="route('tourist-attractions.toggle-status', $touristAttraction->id)"
                                            method="PATCH"
                                            :modal-name="'tourist-attractions-index-toggle-desktop-' . $touristAttraction->id"
                                            :title="$isActive ? ui_phrase('Deactivate') . ' ' . ui_phrase('Tourist Attraction') : ui_phrase('Activate') . ' ' . ui_phrase('Tourist Attraction')"
                                            :message="$isActive ? ui_phrase('attractions confirm deactivate') : ui_phrase('attractions confirm activate')"
                                            :notice-message="__('confirm.notification_after_action')"
                                            :confirm-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                            :trigger-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                            :trigger-icon="$isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'"
                                            :trigger-class="$isActive ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
                                            confirm-class="btn-primary-sm"
                                        />
                                        @if ($canDeleteTouristAttraction)
                                            <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                            <x-ui.confirm-action
                                                :action="route('tourist-attractions.destroy', $touristAttraction)"
                                                method="DELETE"
                                                :modal-name="'tourist-attractions-index-delete-desktop-' . $touristAttraction->id"
                                                :title="ui_phrase('Delete') . ' ' . ui_phrase('Tourist Attraction')"
                                                :message="ui_phrase('attractions ajax delete confirm')"
                                                :notice-message="__('confirm.notification_after_action')"
                                                notice-tone="danger"
                                                :confirm-label="ui_phrase('Delete')"
                                                :trigger-label="ui_phrase('Delete')"
                                                trigger-icon="fa-solid fa-trash w-4"
                                                trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                                confirm-class="btn-danger-sm"
                                            />
                                        @endif
                                    </x-ui.table-action-dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Tourist Attractions')]) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($touristAttractions as $touristAttraction)
                @php
                    $galleryImages = is_array($touristAttraction->gallery_images ?? null) ? $touristAttraction->gallery_images : [];
                    $hasGalleryImages = count($galleryImages) > 0;
                    $hasGoogleMapsUrl = trim((string) ($touristAttraction->google_maps_url ?? '')) !== '';
                    $needsDataAttention = ! $hasGalleryImages || ! $hasGoogleMapsUrl;
                @endphp
                <div class="app-card p-4 {{ $needsDataAttention ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }}">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $touristAttraction->name }}</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-300">{{ $touristAttraction->destination?->name ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('attractions ideal') }}: {{ $touristAttraction->ideal_visit_minutes }} min</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('attractions contract') }}: {{ $touristAttraction->contract_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->contract_rate_per_pax, 'IDR') : '-' }} / pax</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ ui_phrase('attractions markup') }}:
                        {{ ($touristAttraction->markup_type ?? 'fixed') === 'percent'
                            ? rtrim(rtrim(number_format((float) ($touristAttraction->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                            : \App\Support\Currency::format((float) ($touristAttraction->markup ?? 0), 'IDR') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('attractions publish') }}: {{ $touristAttraction->publish_rate_per_pax !== null ? \App\Support\Currency::format((float) $touristAttraction->publish_rate_per_pax, 'IDR') : '-' }} / pax</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ trim(($touristAttraction->city ?? '') . (($touristAttraction->city && $touristAttraction->province) ? ', ' : '') . ($touristAttraction->province ?? '')) ?: '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $touristAttraction->country ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}: <x-ui.status-badge :status="$touristAttraction->trashed() ? 'inactive' : 'active'" size="xs" /></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('tourist-attractions.edit', $touristAttraction) }}" class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                        <x-ui.confirm-action
                            :action="route('tourist-attractions.toggle-status', $touristAttraction->id)"
                            method="PATCH"
                            :modal-name="'tourist-attractions-index-toggle-mobile-' . $touristAttraction->id"
                            :title="$touristAttraction->trashed() ? ui_phrase('Activate') . ' ' . ui_phrase('Tourist Attraction') : ui_phrase('Deactivate') . ' ' . ui_phrase('Tourist Attraction')"
                            :message="$touristAttraction->trashed() ? ui_phrase('attractions confirm activate') : ui_phrase('attractions confirm deactivate')"
                            :notice-message="__('confirm.notification_after_action')"
                            :confirm-label="$touristAttraction->trashed() ? ui_phrase('Activate') : ui_phrase('Deactivate')"
                            :trigger-label="$touristAttraction->trashed() ? ui_phrase('Activate') : ui_phrase('Deactivate')"
                            :trigger-icon="$touristAttraction->trashed() ? 'fa-solid fa-toggle-on' : 'fa-solid fa-toggle-off'"
                            :trigger-class="$touristAttraction->trashed() ? 'btn-primary-sm' : 'btn-muted-sm'"
                            confirm-class="btn-primary-sm"
                        />
                        @if ($canDeleteTouristAttraction)
                            <x-ui.confirm-action
                                :action="route('tourist-attractions.destroy', $touristAttraction)"
                                method="DELETE"
                                :modal-name="'tourist-attractions-index-delete-mobile-' . $touristAttraction->id"
                                :title="ui_phrase('Delete') . ' ' . ui_phrase('Tourist Attraction')"
                                :message="ui_phrase('attractions ajax delete confirm')"
                                :notice-message="__('confirm.notification_after_action')"
                                notice-tone="danger"
                                :confirm-label="ui_phrase('Delete')"
                                :trigger-label="ui_phrase('Delete')"
                                trigger-icon="fa-solid fa-trash"
                                trigger-class="btn-danger-sm"
                                confirm-class="btn-danger-sm"
                            />
                        @endif
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Tourist Attractions')]) }}</div>
            @endforelse
        </div>
        <div>{{ $touristAttractions->links() }}</div>
    </div>
</div>

