@extends('layouts.master')
@section('page_title', ui_phrase('Transports'))
@section('page_subtitle', ui_phrase('Manage transport services, rates, and provider availability.'))
@section('page_actions')
    <a href="{{ route('transports.create') }}" class="btn-primary">{{ ui_phrase('Add Transport') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--transports" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
                <div class="app-card p-5">
                    <form method="GET" action="{{ route('transports.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}" class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
                        <select name="vendor_id" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('All Vendors') }}</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        <select name="transport_type" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('All Types') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected((string) request('transport_type') === (string) $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Status') }}</option>
                            <option value="active" @selected((string) request('status') === 'active')>{{ ui_phrase('Active') }}</option>
                            <option value="inactive" @selected((string) request('status') === 'inactive')>{{ ui_phrase('Inactive') }}</option>
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ($perPageOptions as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
                            <a href="{{ route('transports.index') }}" class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Unit Spec') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('rate per day') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($transportRows as $row)
                        @php($transport = $row['transport'])
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row['row_number'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $transport->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['vendor_name'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['transport_type_label'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                {{ $row['unit_spec_label'] }}<br>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $row['seat_capacity_label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @if ($row['has_rates'])
                                    <div>
                                        Contract: <x-money :amount="(float) $transport->contract_rate" currency="IDR" />
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Markup: {{ $row['markup_display'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Publish: <x-money :amount="(float) $transport->publish_rate" currency="IDR" />
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <x-ui.status-badge :status="$row['is_active'] ? 'active' : 'inactive'" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ $row['show_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('View') }}</span>
                                    </a>
                                    <a href="{{ $row['edit_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                    @if ($row['can_manage_activation'])
                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                    <x-ui.confirm-action
                                        :action="$row['toggle_url']"
                                        method="PATCH"
                                        :modal-name="'transports-index-toggle-desktop-' . $transport->id"
                                        :title="$row['toggle_title']"
                                        :message="$row['toggle_message_desktop']"
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
                                <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('transport services')])" :message="ui_phrase('Try changing filter criteria or add a new transport service.')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($transportRows as $row)
                @php($transport = $row['transport'])
                <div class="app-card relative p-4 pt-5">
                    <div class="absolute right-3 top-3 z-10">
                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                            <a href="{{ $row['show_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('View') }}</span>
                            </a>
                            <a href="{{ $row['edit_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Edit') }}</span>
                            </a>
                            @if ($row['can_manage_activation'])
                            <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                            <x-ui.confirm-action
                                :action="$row['toggle_url']"
                                method="PATCH"
                                :modal-name="'transports-index-toggle-mobile-' . $transport->id"
                                :title="$row['toggle_title']"
                                :message="$row['toggle_message_mobile']"
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
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $transport->code }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transport->name }}</p>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ ui_phrase('Provider') }}</div>
                        <div>{{ $row['vendor_name'] }}</div>
                        <div>{{ ui_phrase('Unit') }}</div>
                        <div>{{ $row['unit_spec_label'] }}</div>
                        <div>{{ ui_phrase('Rate') }}</div>
                        <div>
                            @if ($row['has_rates'])
                                <div><x-money :amount="(float) $transport->contract_rate" currency="IDR" /></div>
                                <div class="text-gray-500 dark:text-gray-400">
                                    {{ $row['markup_display'] }}
                                </div>
                            @else
                                -
                            @endif
                        </div>
                        <div>{{ ui_phrase('Status') }}</div>
                        <div><x-ui.status-badge :status="$row['is_active'] ? 'active' : 'inactive'" size="xs" /></div>
                    </div>
                </div>
            @empty
                <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('transport services')])" :message="ui_phrase('Try changing filter criteria or add a new transport service.')" />
            @endforelse
        </div>
        <div>{{ $transports->links() }}</div>
        </div>
    </div>
@endsection








