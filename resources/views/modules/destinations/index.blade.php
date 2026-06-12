@extends('layouts.master')
@section('page_title', ui_phrase('Destinations'))
@section('page_subtitle', ui_phrase('Manage destination master data and linked modules.'))
@section('page_actions')
    <a href="{{ route('destinations.create') }}" class="btn-primary">{{ ui_phrase('Add Destination') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--destinations" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
            <div class="app-card p-4">
                <form method="GET" action="{{ route('destinations.index') }}"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form
                    data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                    <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}"
                        class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
                    <select name="status" class="app-input" data-service-filter-input>
                        <option value="">{{ ui_phrase('Status') }}</option>
                        <option value="active" @selected((string) request('status') === 'active')>{{ ui_phrase('active') }}</option>
                        <option value="inactive" @selected((string) request('status') === 'inactive')>{{ ui_phrase('inactive') }}</option>
                    </select>
                    <select name="per_page" class="app-input" data-service-filter-input>
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>
                                {{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                        <a href="{{ route('destinations.index') }}"
                            class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4"
                            data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                    </div>
                </form>
            </div>
            <div class="hidden md:block app-card overflow-hidden">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="table-header">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                #</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Destination') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('City / Province') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Linked Data') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Status') }}</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
                                {{ ui_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($destinations as $index=>$destination)
                        @php($isActive = !$destination->trashed())
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                                {{ ($destinations->firstItem() ?? 1) + $index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $destination->province ?: $destination->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $destination->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                {{ trim(($destination->city ?? '') . ($destination->city && $destination->province ? ', ' : '') . ($destination->province ?? '')) ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-200">
                                {{ ui_phrase('Vendors') }}: {{ (int) ($destination->vendors_count ?? 0) }} |
                                {{ ui_phrase('Hotels') }}: {{ (int) ($destination->hotels_count ?? 0) }} |
                                {{ ui_phrase('Attractions') }}: {{ (int) ($destination->tourist_attractions_count ?? 0) }} |
                                {{ ui_phrase('Airports') }}: {{ (int) ($destination->airports_count ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ route('destinations.show', $destination) }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('View') }}</span>
                                    </a>
                                    <a href="{{ route('destinations.edit', $destination) }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                    <x-ui.confirm-action :action="route('destinations.toggle-status', $destination->id)" method="PATCH" :modal-name="'destinations-index-toggle-desktop-' . $destination->id" :title="$isActive
                                        ? ui_phrase('Deactivate') . ' ' . ui_phrase('Destination')
                                        : ui_phrase('Activate') . ' ' . ui_phrase('Destination')"
                                        :message="$isActive
                                            ? ui_phrase('confirm deactivate')
                                            : ui_phrase('confirm activate')" :notice-message="__('confirm.notification_after_action')" :confirm-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')" :trigger-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                        :trigger-icon="$isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'" :trigger-class="$isActive
                                            ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20'
                                            : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'" confirm-class="btn-primary-sm" />
                                </x-ui.table-action-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6">
                                <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Destinations')])" :message="ui_phrase('Try changing filter criteria or add a new destination.')" />
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="md:hidden space-y-3">
                    @forelse ($destinations as $destination)
                        @php($isActive = !$destination->trashed())
                        <div class="app-card relative p-4 pt-5">
                            <div class="absolute right-3 top-3 z-10">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ route('destinations.show', $destination) }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('View') }}</span>
                                    </a>
                                    <a href="{{ route('destinations.edit', $destination) }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                    <x-ui.confirm-action :action="route('destinations.toggle-status', $destination->id)" method="PATCH" :modal-name="'destinations-index-toggle-mobile-' . $destination->id" :title="$isActive
                                        ? ui_phrase('Deactivate') . ' ' . ui_phrase('Destination')
                                        : ui_phrase('Activate') . ' ' . ui_phrase('Destination')"
                                        :message="$isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate')" :notice-message="__('confirm.notification_after_action')" :confirm-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')" :trigger-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                                        :trigger-icon="$isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4'" :trigger-class="$isActive ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20' : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20'" confirm-class="btn-primary-sm" />
                                </x-ui.table-action-dropdown>
                            </div>
                            <div class="flex items-start gap-3 pr-12">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $destination->code }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $destination->province ?: $destination->name }}</p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('Location') }}</div>
                                <div>
                                    {{ trim(($destination->city ?? '') . ($destination->city && $destination->province ? ', ' : '') . ($destination->province ?? '')) ?: '-' }}
                                </div>
                                <div>{{ ui_phrase('Linked') }}</div>
                                <div>
                                    {{ ui_phrase('Vendors') }}: {{ (int) ($destination->vendors_count ?? 0) }} |
                                    {{ ui_phrase('Hotels') }}: {{ (int) ($destination->hotels_count ?? 0) }} |
                                    {{ ui_phrase('Attractions') }}: {{ (int) ($destination->tourist_attractions_count ?? 0) }}
                                    |
                                    {{ ui_phrase('Airports') }}: {{ (int) ($destination->airports_count ?? 0) }}
                                </div>
                                <div>{{ ui_phrase('Status') }}</div>
                                <div><x-ui.status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" /></div>
                            </div>
                        </div>
                    @empty
                        <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Destinations')])" :message="ui_phrase('Try changing filter criteria or add a new destination.')" />
                    @endforelse
                </div>
            </div>
            <div>{{ $destinations->links() }}</div>
        </div>
    </div>
@endsection
