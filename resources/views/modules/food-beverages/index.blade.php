@extends('layouts.master')
@section('page_title', ui_phrase('Food & Beverage'))
@section('page_subtitle', ui_phrase('Manage food & beverage services, vendors, and active listing status.'))
@section('page_actions')
    <a href="{{ route('food-beverages.create') }}" class="btn-primary">{{ ui_phrase('Add F&B') }}</a>
@endsection
@section('content')
    @php
        $resolveMealSessionBadges = static function (?string $mealPeriod): array {
            $tokens = array_values(array_filter(array_map(
                static fn ($item) => strtolower(trim((string) $item)),
                preg_split('/[\s,;\/|]+/', (string) $mealPeriod) ?: []
            )));

            $sessions = [];
            foreach (['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'] as $key => $label) {
                if (in_array($key, $tokens, true)) {
                    $sessions[] = ['key' => $key, 'label' => $label];
                }
            }

            return $sessions;
        };
    @endphp
    <div class="space-y-6 module-page module-page--food-beverages" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
                <div class="app-card p-5">
                    <form method="GET" action="{{ route('food-beverages.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}" class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
                        <select name="vendor_id" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('All Vendors') }}</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        <select name="service_type" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('All Types') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type['value'] }}" @selected((string) request('service_type') === (string) $type['value'])>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Status') }}</option>
                            <option value="active" @selected((string) request('status') === 'active')>{{ ui_phrase('Active') }}</option>
                            <option value="inactive" @selected((string) request('status') === 'inactive')>{{ ui_phrase('Inactive') }}</option>
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                            <a href="{{ route('food-beverages.index') }}" class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Service') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Duration') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Meal Period') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Adult Rate / Pax') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Child Rate / Pax') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($foodBeverages as $index => $foodBeverage)
                        @php
                            $isActive = ! $foodBeverage->trashed();
                            $galleryImages = is_array($foodBeverage->gallery_images ?? null) ? $foodBeverage->gallery_images : [];
                            $hasGalleryImages = count($galleryImages) > 0;
                            $hasDestination = (int) ($foodBeverage->vendor?->destination_id ?? 0) > 0;
                            $hasServiceName = trim((string) ($foodBeverage->name ?? '')) !== '';
                            $hasServiceType = trim((string) ($foodBeverage->service_type ?? '')) !== '';
                            $hasActivityType = trim((string) ($foodBeverage->activity_type ?? $foodBeverage->service_type ?? '')) !== '';
                            $needsDataAttention = ! $hasGalleryImages || ! $hasDestination || ! $hasServiceName || ! $hasServiceType || ! $hasActivityType;
                        @endphp
                        <tr class="{{ $needsDataAttention ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                <div>{{ $foodBeverage->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $foodBeverage->vendor->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ ucwords(str_replace('_', ' ', (string) $foodBeverage->service_type)) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $foodBeverage->duration_minutes }} min</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @php
                                    $mealSessions = $resolveMealSessionBadges($foodBeverage->meal_period);
                                @endphp
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($mealSessions as $session)
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium
                                            {{ $session['key'] === 'breakfast' ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300' : '' }}
                                            {{ $session['key'] === 'lunch' ? 'border-sky-300 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300' : '' }}
                                            {{ $session['key'] === 'dinner' ? 'border-violet-300 bg-violet-50 text-violet-700 dark:border-violet-700 dark:bg-violet-900/20 dark:text-violet-300' : '' }}">
                                            {{ $session['label'] }}
                                        </span>
                                    @empty
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('-') }}</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>
                                    Contract: <x-money :amount="(float) ($foodBeverage->adult_contract_rate ?? $foodBeverage->contract_rate ?? 0)" currency="IDR" />
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Markup:
                                    {{ ($foodBeverage->adult_markup_type ?? $foodBeverage->markup_type ?? 'fixed') === 'percent'
                                        ? rtrim(rtrim(number_format((float) ($foodBeverage->adult_markup ?? $foodBeverage->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                        : \App\Support\Currency::format((float) ($foodBeverage->adult_markup ?? $foodBeverage->markup ?? 0), 'IDR') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Publish: <x-money :amount="(float) ($foodBeverage->adult_publish_rate ?? $foodBeverage->publish_rate ?? 0)" currency="IDR" />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>
                                    Contract: <x-money :amount="(float) ($foodBeverage->child_contract_rate ?? 0)" currency="IDR" />
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Markup:
                                    {{ ($foodBeverage->child_markup_type ?? 'fixed') === 'percent'
                                        ? rtrim(rtrim(number_format((float) ($foodBeverage->child_markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                        : \App\Support\Currency::format((float) ($foodBeverage->child_markup ?? 0), 'IDR') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Publish: <x-money :amount="(float) ($foodBeverage->child_publish_rate ?? 0)" currency="IDR" />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ route('food-beverages.edit', $foodBeverage) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                    <a href="{{ route('food-beverages.create', ['copy' => $foodBeverage->id]) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-regular fa-copy w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Copy') }}</span>
                                    </a>
                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                    <x-ui.confirm-action
                                        :action="route('food-beverages.toggle-status', $foodBeverage->id)"
                                        method="PATCH"
                                        :modal-name="'food-beverages-index-toggle-desktop-' . $foodBeverage->id"
                                        :title="$isActive ? ui_phrase('Deactivate') . ' F&B' : ui_phrase('Activate') . ' F&B'"
                                        :message="$isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate')"
                                        :notice-message="__('confirm.notification_after_action')"
                                        :confirm-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                        :trigger-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                        :trigger-icon="$isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'"
                                        :trigger-class="$isActive ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
                                        confirm-class="btn-primary-sm"
                                    />
                                </x-ui.table-action-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6">
                                <x-module-empty-state :title="ui_phrase('No F&B service available.')" :message="ui_phrase('Try changing filter criteria or add a new F&B service.')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($foodBeverages as $foodBeverage)
                @php
                    $isActive = ! $foodBeverage->trashed();
                    $galleryImages = is_array($foodBeverage->gallery_images ?? null) ? $foodBeverage->gallery_images : [];
                    $hasGalleryImages = count($galleryImages) > 0;
                    $hasDestination = (int) ($foodBeverage->vendor?->destination_id ?? 0) > 0;
                    $hasServiceName = trim((string) ($foodBeverage->name ?? '')) !== '';
                    $hasServiceType = trim((string) ($foodBeverage->service_type ?? '')) !== '';
                    $hasActivityType = trim((string) ($foodBeverage->activity_type ?? $foodBeverage->service_type ?? '')) !== '';
                    $needsDataAttention = ! $hasGalleryImages || ! $hasDestination || ! $hasServiceName || ! $hasServiceType || ! $hasActivityType;
                    $mealSessions = $resolveMealSessionBadges($foodBeverage->meal_period);
                    $serviceTypeLabel = ucwords(str_replace('_', ' ', (string) $foodBeverage->service_type));
                @endphp
                <div class="app-card relative p-4 pt-5 {{ $needsDataAttention ? 'bg-amber-50/70 dark:bg-amber-900/15' : '' }}">
                    <div class="absolute right-3 top-3 z-10">
                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                            <a href="{{ route('food-beverages.edit', $foodBeverage) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Edit') }}</span>
                            </a>
                            <a href="{{ route('food-beverages.create', ['copy' => $foodBeverage->id]) }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-regular fa-copy w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Copy') }}</span>
                            </a>
                            <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                            <x-ui.confirm-action
                                :action="route('food-beverages.toggle-status', $foodBeverage->id)"
                                method="PATCH"
                                :modal-name="'food-beverages-index-toggle-mobile-' . $foodBeverage->id"
                                :title="$isActive ? ui_phrase('Deactivate') . ' F&B' : ui_phrase('Activate') . ' F&B'"
                                :message="$isActive ? ui_phrase('Deactivate this F&B service?') : ui_phrase('Activate this F&B service?')"
                                :notice-message="__('confirm.notification_after_action')"
                                :confirm-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                :trigger-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                :trigger-icon="$isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'"
                                :trigger-class="$isActive ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
                                confirm-class="btn-primary-sm"
                            />
                        </x-ui.table-action-dropdown>
                    </div>
                    <div class="flex items-start justify-between gap-3 pr-12">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $foodBeverage->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $foodBeverage->vendor->name ?? '-' }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                            {{ $serviceTypeLabel !== '' ? $serviceTypeLabel : '-' }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ ui_phrase('Duration') }}</div>
                        <div>{{ $foodBeverage->duration_minutes }} min</div>
                        <div>{{ ui_phrase('Meal Period') }}</div>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($mealSessions as $session)
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium
                                    {{ $session['key'] === 'breakfast' ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300' : '' }}
                                    {{ $session['key'] === 'lunch' ? 'border-sky-300 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300' : '' }}
                                    {{ $session['key'] === 'dinner' ? 'border-violet-300 bg-violet-50 text-violet-700 dark:border-violet-700 dark:bg-violet-900/20 dark:text-violet-300' : '' }}">
                                    {{ $session['label'] }}
                                </span>
                            @empty
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('-') }}</span>
                            @endforelse
                        </div>
                        <div>{{ ui_phrase('Adult Rate') }}</div>
                        <div>
                            <div>{{ ui_phrase('Contract') }}: <x-money :amount="(float) ($foodBeverage->adult_contract_rate ?? $foodBeverage->contract_rate ?? 0)" currency="IDR" /></div>
                            <div class="text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('Markup') }}:
                                {{ ($foodBeverage->adult_markup_type ?? $foodBeverage->markup_type ?? 'fixed') === 'percent'
                                    ? rtrim(rtrim(number_format((float) ($foodBeverage->adult_markup ?? $foodBeverage->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                    : \App\Support\Currency::format((float) ($foodBeverage->adult_markup ?? $foodBeverage->markup ?? 0), 'IDR') }}
                            </div>
                            <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Publish') }}: <x-money :amount="(float) ($foodBeverage->adult_publish_rate ?? $foodBeverage->publish_rate ?? 0)" currency="IDR" /></div>
                        </div>
                        <div>{{ ui_phrase('Child Rate') }}</div>
                        <div>
                            <div>{{ ui_phrase('Contract') }}: <x-money :amount="(float) ($foodBeverage->child_contract_rate ?? 0)" currency="IDR" /></div>
                            <div class="text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('Markup') }}:
                                {{ ($foodBeverage->child_markup_type ?? 'fixed') === 'percent'
                                    ? rtrim(rtrim(number_format((float) ($foodBeverage->child_markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                    : \App\Support\Currency::format((float) ($foodBeverage->child_markup ?? 0), 'IDR') }}
                            </div>
                            <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Publish') }}: <x-money :amount="(float) ($foodBeverage->child_publish_rate ?? 0)" currency="IDR" /></div>
                        </div>
                        <div>{{ ui_phrase('Status') }}</div>
                        <div><x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" /></div>
                    </div>
                </div>
            @empty
                <x-module-empty-state :title="ui_phrase('No F&B service available.')" :message="ui_phrase('Try changing filter criteria or add a new F&B service.')" />
            @endforelse
        </div>
        <div>{{ $foodBeverages->links() }}</div>
        </div>
    </div>
@endsection







