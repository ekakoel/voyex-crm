@extends('layouts.master')

@section('page_title', ui_phrase('transfers page title'))
@section('page_subtitle', ui_phrase('transfers page subtitle'))

@section('page_actions')
    <a href="{{ route('island-transfers.create') }}" class="btn-primary">{{ ui_phrase('transfers add transfer') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--island-transfers" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
                <div class="app-card p-5">
                    <form method="GET" action="{{ route('island-transfers.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}" class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
                        <select name="vendor_id" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('transfers all vendors') }}</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>

                        <select name="transfer_type" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('transfers all types') }}</option>
                            @foreach (($transferTypeOptions ?? []) as $type)
                                <option value="{{ $type['value'] }}" @selected((string) request('transfer_type') === (string) $type['value'])>{{ $type['label'] }}</option>
                            @endforeach
                        </select>

                        <select name="status" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Status') }}</option>
                            <option value="active" @selected((string) request('status') === 'active')>{{ ui_phrase('Active') }}</option>
                            <option value="inactive" @selected((string) request('status') === 'inactive')>{{ ui_phrase('Inactive') }}</option>
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ($perPageOptions as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase('transfers per page option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>

                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                            <a href="{{ route('island-transfers.index') }}" class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-service-filter-reset>{{ ui_phrase('transfers reset') }}</a>
                        </div>
                    </form>
                </div>

                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('transfers transfer') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('transfers type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('transfers vendor') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('transfers duration') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('transfers distance') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('transfers status') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">{{ ui_phrase('transfers actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($transferRows as $row)
                                    @php($transfer = $row['transfer'])
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            <div class="flex items-center gap-2">
                                                @if (!empty($row['thumbnail_url']))
                                                    <img src="{{ $row['thumbnail_url'] }}" alt="Island transfer image" class="h-10 w-14 rounded-md border border-gray-200 object-cover dark:border-gray-700">
                                                @endif
                                                <span class="font-semibold">{{ $transfer->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $row['transfer_type_label'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div>{{ $row['vendor_name'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['vendor_location'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['duration_label'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['distance_label'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <x-ui.status-badge :status="$row['status_badge']" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                                <a href="{{ $row['show_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('transfers view details') }}</span>
                                                </a>
                                                <a href="{{ $row['edit_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('transfers edit') }}</span>
                                                </a>
                                                <x-ui.confirm-action
                                                    :action="$row['duplicate_url']"
                                                    method="POST"
                                                    :modal-name="'island-transfers-index-duplicate-desktop-' . $transfer->id"
                                                    :title="ui_phrase('Duplicate') . ' ' . ui_phrase('Island Transfer')"
                                                    :message="ui_phrase('transfers confirm duplicate')"
                                                    :notice-message="__('confirm.notification_after_action')"
                                                    :confirm-label="ui_phrase('Duplicate')"
                                                    :trigger-label="ui_phrase('Duplicate')"
                                                    trigger-icon="fa-solid fa-copy w-4 text-gray-500 dark:text-gray-400"
                                                    trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                                                    confirm-class="btn-primary-sm"
                                                />
                                                @if ($canManageActivationActions)
                                                <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                                <x-ui.confirm-action
                                                    :action="$row['toggle_url']"
                                                    method="PATCH"
                                                    :modal-name="'island-transfers-index-toggle-desktop-' . $transfer->id"
                                                    :title="$row['toggle_title']"
                                                    :message="$row['toggle_message']"
                                                    :notice-message="__('confirm.notification_after_action')"
                                                    :confirm-label="$row['toggle_label']"
                                                    :trigger-label="$row['toggle_label']"
                                                    :trigger-icon="$row['toggle_icon']"
                                                    :trigger-class="$row['toggle_class']"
                                                    confirm-class="btn-primary-sm"
                                                />
                                                @endif
                                            </x-ui.table-action-dropdown>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6">
                                            <x-module-empty-state :title="ui_phrase('transfers no data')" :message="ui_phrase('Try changing filter criteria or add a new transfer service.')" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="md:hidden space-y-3">
                    @forelse ($transferRows as $row)
                        @php($transfer = $row['transfer'])
                        <div class="app-card p-4">
                            @if (!empty($row['thumbnail_url']))
                                <div class="mb-3 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                                    <img src="{{ $row['thumbnail_url'] }}" alt="Island transfer image" class="h-28 w-full object-cover">
                                </div>
                            @endif

                            <div class="relative">
                                <div class="absolute right-0 top-0 z-10">
                                    <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                        <a href="{{ $row['show_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('transfers view details') }}</span>
                                        </a>
                                        <a href="{{ $row['edit_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('transfers edit') }}</span>
                                        </a>
                                        <x-ui.confirm-action
                                            :action="$row['duplicate_url']"
                                            method="POST"
                                            :modal-name="'island-transfers-index-duplicate-mobile-' . $transfer->id"
                                            :title="ui_phrase('Duplicate') . ' ' . ui_phrase('Island Transfer')"
                                            :message="ui_phrase('transfers confirm duplicate')"
                                            :notice-message="__('confirm.notification_after_action')"
                                            :confirm-label="ui_phrase('Duplicate')"
                                            :trigger-label="ui_phrase('Duplicate')"
                                            trigger-icon="fa-solid fa-copy w-4"
                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                                            confirm-class="btn-primary-sm"
                                        />
                                        @if ($canManageActivationActions)
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action
                                            :action="$row['toggle_url']"
                                            method="PATCH"
                                            :modal-name="'island-transfers-index-toggle-mobile-' . $transfer->id"
                                            :title="$row['toggle_title']"
                                            :message="$row['toggle_message']"
                                            :notice-message="__('confirm.notification_after_action')"
                                            :confirm-label="$row['toggle_label']"
                                            :trigger-label="$row['toggle_label']"
                                            :trigger-icon="$row['toggle_icon']"
                                            :trigger-class="$row['toggle_class']"
                                            confirm-class="btn-primary-sm"
                                        />
                                        @endif
                                    </x-ui.table-action-dropdown>
                                </div>
                                <div class="flex items-start gap-3 pr-12">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $transfer->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['vendor_name'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('transfers duration') }}</div>
                                <div>{{ $row['duration_label'] }}</div>
                                <div>{{ ui_phrase('transfers distance') }}</div>
                                <div>{{ $row['distance_label'] }}</div>
                                <div>{{ ui_phrase('transfers status') }}</div>
                                <div><x-ui.status-badge :status="$row['status_badge']" size="xs" /></div>
                            </div>

                        </div>
                    @empty
                        <x-module-empty-state :title="ui_phrase('transfers no data')" :message="ui_phrase('Try changing filter criteria or add a new transfer service.')" />
                    @endforelse
                </div>

                <div>{{ $islandTransfers->links() }}</div>
        </div>
    </div>
@endsection

