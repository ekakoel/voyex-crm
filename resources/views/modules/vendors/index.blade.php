@extends('layouts.master')

@section('page_title', ui_phrase('Vendors / Providers'))
@section('page_subtitle', ui_phrase('Centralized provider records for services, vouchers, and operational workflow.'))
@section('page_actions')
    <a href="{{ route('vendors.create') }}" class="btn-primary">{{ ui_phrase('Add Vendor') }}</a>
@endsection

@section('content')
    <div class="space-y-5 module-page module-page--vendors" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.metric-card :title="ui_phrase('Total Vendors')" :value="(string) ($summaries['total'] ?? 0)" />
                <x-ui.metric-card :title="ui_phrase('Active Vendors')" :value="(string) ($summaries['active'] ?? 0)" />
                <x-ui.metric-card :title="ui_phrase('Inactive Vendors')" :value="(string) ($summaries['inactive'] ?? 0)" />
                <x-ui.metric-card :title="ui_phrase('Vendors With Services')" :value="(string) ($summaries['with_services'] ?? 0)" />
            </div>
            <div class="app-card p-4">
                <form method="GET" action="{{ route('vendors.index') }}"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" data-service-filter-form data-filter-min-text="3"
                    data-disable-submit-lock="1" data-page-spinner="off">
                    <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}"
                        class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
                    <select name="service_type" class="app-input" data-service-filter-input>
                        <option value="">{{ ui_phrase('Service Type') }}</option>
                        <option value="activities" @selected(request('service_type') === 'activities')>{{ ui_phrase('Activities') }}</option>
                        <option value="food_beverages" @selected(request('service_type') === 'food_beverages')>{{ ui_phrase('Food & Beverage') }}</option>
                        <option value="transports" @selected(request('service_type') === 'transports')>{{ ui_phrase('Transports') }}</option>
                        <option value="island_transfers" @selected(request('service_type') === 'island_transfers')>{{ ui_phrase('Island Transfers') }}
                        </option>
                    </select>
                    <select name="status" class="app-input" data-service-filter-input>
                        <option value="">{{ ui_phrase('Status') }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ ui_phrase('Active') }}</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>{{ ui_phrase('Inactive') }}</option>
                    </select>
                    <label class="sr-only" for="vendor-per-page">{{ ui_phrase('Per Page') }}</label>
                    <select id="vendor-per-page" name="per_page" class="app-input" data-service-filter-input>
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>
                                {{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-4 filter-actions h-[42px]">
                        <a href="{{ route('vendors.index') }}"
                            class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4"
                            data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                    </div>
                </form>
            </div>
            <div data-service-filter-results>
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="table-header">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Vendor Name') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Type') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Contact Person') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Phone') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Email') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Service Count') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Status') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
                                        {{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($vendors as $vendor)
                                    @php
                                        $serviceCount =
                                            (int) ($vendor->activities_count ?? 0) +
                                            (int) ($vendor->food_beverages_count ?? 0) +
                                            (int) ($vendor->transports_count ?? 0) +
                                            (int) ($vendor->island_transfers_count ?? 0);
                                        $vendorType = $serviceCount > 0 ? ui_phrase('Service Provider') : ui_phrase('General');
                                        $serviceBadges = [
                                            [
                                                'label' => ui_phrase('Act'),
                                                'value' => (int) ($vendor->activities_count ?? 0),
                                                'class' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
                                            ],
                                            [
                                                'label' => ui_phrase('F&B'),
                                                'value' => (int) ($vendor->food_beverages_count ?? 0),
                                                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                                            ],
                                            [
                                                'label' => ui_phrase('Trf'),
                                                'value' => (int) ($vendor->transports_count ?? 0),
                                                'class' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300',
                                            ],
                                            [
                                                'label' => ui_phrase('Isl'),
                                                'value' => (int) ($vendor->island_transfers_count ?? 0),
                                                'class' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                                            ],
                                        ];
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <p class="font-medium text-gray-800 dark:text-gray-100">{{ $vendor->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->destination?->name ?? '-' }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $vendorType }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $vendor->contact_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $vendor->contact_phone ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $vendor->contact_email ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="grid max-w-[220px] grid-cols-2 gap-1">
                                                @foreach ($serviceBadges as $badge)
                                                    @continue($badge['value'] <= 0)
                                                    <span
                                                        class="inline-flex items-center justify-between rounded-md border py-[2px] px-[4px] text-[10px] font-semibold leading-none {{ $badge['class'] }}">
                                                        <span>{{ $badge['label'] }}</span>
                                                        <span class="ml-1 rounded-sm bg-white/70 px-1 py-[1px] text-[9px] font-bold dark:bg-slate-950/30">
                                                            {{ $badge['value'] }}
                                                        </span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.status-badge :status="$vendor->is_active ? 'active' : 'inactive'"
                                                size="xs" /></td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                                <a href="{{ route('vendors.edit', $vendor) }}"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('Edit') }}</span>
                                                </a>
                                                <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                                <x-ui.confirm-action :action="route('vendors.toggle-status', $vendor)" method="PATCH" :modal-name="'vendors-index-toggle-' . $vendor->id" :title="$vendor->is_active
                                                    ? ui_phrase('Deactivate') . ' ' . ui_phrase('Vendor')
                                                    : ui_phrase('Activate') . ' ' . ui_phrase('Vendor')"
                                                    :message="$vendor->is_active
                                                        ? ui_phrase('confirm deactivate')
                                                        : ui_phrase('confirm activate')" :impact-title="__('confirm.what_will_happen')" :impact-items="[
                                                        $vendor->is_active
                                                            ? ui_phrase('Vendor will be set as inactive and hidden from active options.')
                                                            : ui_phrase('Vendor will be set as active and available for selection.'),
                                                    ]" :notice-message="__('confirm.notification_after_action')"
                                                    :confirm-label="$vendor->is_active ? ui_phrase('Deactivate') : ui_phrase('Activate')" :trigger-label="$vendor->is_active ? ui_phrase('Deactivate') : ui_phrase('Activate')" :trigger-icon="$vendor->is_active
                                                        ? 'fa-solid fa-toggle-off w-4'
                                                        : 'fa-solid fa-toggle-on w-4'" :trigger-class="$vendor->is_active
                                                        ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20'
                                                        : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
                                                    confirm-class="btn-primary-sm" />
                                            </x-ui.table-action-dropdown>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6">
                                            <x-ui.empty-state :title="ui_phrase('No vendors found.')" :description="ui_phrase('Create a new vendor/provider or adjust your filters.')" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="md:hidden space-y-3">
                    @forelse ($vendors as $vendor)
                        @php
                            $serviceCount =
                                (int) ($vendor->activities_count ?? 0) +
                                (int) ($vendor->food_beverages_count ?? 0) +
                                (int) ($vendor->transports_count ?? 0) +
                                (int) ($vendor->island_transfers_count ?? 0);
                            $vendorType = $serviceCount > 0 ? ui_phrase('Service Provider') : ui_phrase('General');
                            $serviceBadges = [
                                [
                                    'label' => ui_phrase('Act'),
                                    'value' => (int) ($vendor->activities_count ?? 0),
                                    'class' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
                                ],
                                [
                                    'label' => ui_phrase('F&B'),
                                    'value' => (int) ($vendor->food_beverages_count ?? 0),
                                    'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                                ],
                                [
                                    'label' => ui_phrase('Trf'),
                                    'value' => (int) ($vendor->transports_count ?? 0),
                                    'class' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300',
                                ],
                                [
                                    'label' => ui_phrase('Isl'),
                                    'value' => (int) ($vendor->island_transfers_count ?? 0),
                                    'class' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                                ],
                            ];
                        @endphp
                        <div class="app-card relative p-4 pt-5">
                            <div class="absolute right-3 top-3 z-10">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ route('vendors.edit', $vendor) }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                    <x-ui.confirm-action :action="route('vendors.toggle-status', $vendor)" method="PATCH" :modal-name="'vendors-index-toggle-mobile-' . $vendor->id" :title="$vendor->is_active
                                        ? ui_phrase('Deactivate') . ' ' . ui_phrase('Vendor')
                                        : ui_phrase('Activate') . ' ' . ui_phrase('Vendor')"
                                        :message="$vendor->is_active ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate')" :impact-title="__('confirm.what_will_happen')" :impact-items="[
                                            $vendor->is_active
                                                ? ui_phrase('Vendor will be set as inactive and hidden from active options.')
                                                : ui_phrase('Vendor will be set as active and available for selection.'),
                                        ]" :notice-message="__('confirm.notification_after_action')" :confirm-label="$vendor->is_active ? ui_phrase('Deactivate') : ui_phrase('Activate')" :trigger-label="$vendor->is_active ? ui_phrase('Deactivate') : ui_phrase('Activate')" :trigger-icon="$vendor->is_active ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'"
                                        :trigger-class="$vendor->is_active ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'"
                                        confirm-class="btn-primary-sm" />
                                </x-ui.table-action-dropdown>
                            </div>
                            <div class="flex items-start gap-3 pr-12">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $vendor->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->destination?->name ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('Type') }}</div>
                                <div>{{ $vendorType }}</div>
                                <div>{{ ui_phrase('Contact Person') }}</div>
                                <div>{{ $vendor->contact_name ?? '-' }}</div>
                                <div>{{ ui_phrase('Phone') }}</div>
                                <div>{{ $vendor->contact_phone ?? '-' }}</div>
                                <div>{{ ui_phrase('Email') }}</div>
                                <div>{{ $vendor->contact_email ?? '-' }}</div>
                                <div>{{ ui_phrase('Status') }}</div>
                                <div><x-ui.status-badge :status="$vendor->is_active ? 'active' : 'inactive'" size="xs" /></div>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-1.5">
                                @foreach ($serviceBadges as $badge)
                                    @continue($badge['value'] <= 0)
                                    <span class="inline-flex items-center justify-between rounded-md border py-[4px] px-[8px] text-[10px] font-semibold leading-none {{ $badge['class'] }}">
                                        <span>{{ $badge['label'] }}</span>
                                        <span class="ml-1 rounded-sm bg-white/70 px-1 py-[1px] text-[9px] font-bold dark:bg-slate-950/30">
                                            {{ $badge['value'] }}
                                        </span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <x-module-empty-state :title="ui_phrase('No vendors found.')" :message="ui_phrase('Create a new vendor/provider or adjust your filters.')" />
                    @endforelse
                </div>
                <div>{{ $vendors->links() }}</div>
            </div>
        </div>
    </div>
@endsection
