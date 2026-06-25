@extends('layouts.master')
@section('page_title', ui_phrase('Customers'))
@section('page_subtitle', ui_phrase('Manage customer and agent records with shared index insights.'))
@section('page_actions')
    <a href="{{ route('customers.import') }}" class="btn-outline">{{ ui_phrase('Import CSV') }}</a>
    <a href="{{ route('customers.create') }}" class="btn-primary">{{ ui_phrase('Add Customer') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--customers" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                @foreach (collect($statsCards ?? [])->take(4) as $stat)
                    <x-ui.metric-card :title="(string) ($stat['label'] ?? '-')" :value="(string) ($stat['value'] ?? 0)" />
                @endforeach
            </div>

            <div class="app-card p-4">
                <form method="GET" action="{{ route('customers.index') }}"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5" data-service-filter-form
                    data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                    <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search (min 3 characters)') }}"
                        class="app-input sm:col-span-2 lg:col-span-2" data-filter-min-text="3" data-service-filter-input>
                    <select name="customer_type" class="app-input" data-service-filter-input>
                        <option value="">{{ ui_phrase('Type') }}</option>
                        @foreach (['individual' => ui_phrase('type individual'), 'company' => ui_phrase('type company')] as $value => $label)
                            <option value="{{ $value }}" @selected(request('customer_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="per_page" class="app-input" data-service-filter-input>
                        @foreach ($perPageOptions as $size)
                            <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                {{ ui_phrase(':size/page', ['size' => $size]) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-1 filter-actions h-[42px]">
                        <a href="{{ route('customers.index') }}"
                            class="btn-secondary h-[42px] w-full rounded-[var(--app-radius-sm)] px-4"
                            data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Code') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Name') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Email/Phone') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Country') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Customer Type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Company') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($customerRows as $row)
                                    @php($customer = $row['customer'])
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row['row_number'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['code'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $row['name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $row['email'] }} <br>
                                            {{ $row['phone'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['country'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                                                {{ $row['customer_type_label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['company_name'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <x-ui.status-badge :status="$row['is_active'] ? 'active' : 'inactive'" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                                <a href="{{ $row['edit_url'] }}"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('Edit') }}</span>
                                                </a>
                                                @if ($row['can_manage_activation'])
                                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                                    <x-ui.confirm-action :action="$row['toggle_url']" method="PATCH" :modal-name="'customers-index-toggle-desktop-' . $customer->id"
                                                        :title="$row['toggle_modal_title']" :message="$row['toggle_message']" :notice-message="__('confirm.notification_after_action')" :confirm-label="$row['toggle_label']"
                                                        :trigger-label="$row['toggle_label']" :trigger-icon="$row['toggle_icon']" :trigger-class="$row['toggle_class']"
                                                        confirm-class="btn-primary-sm" />
                                                @endif
                                            </x-ui.table-action-dropdown>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6">
                                            <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Customers')])" :message="ui_phrase('Try changing filter criteria or add a new customer.')" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="md:hidden space-y-3">
                    @forelse ($customerRows as $row)
                        @php($customer = $row['customer'])
                        <div class="app-card relative p-4 pt-5">
                            <div class="absolute right-3 top-3 z-10">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ $row['edit_url'] }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                    @if ($row['can_manage_activation'])
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action :action="$row['toggle_url']" method="PATCH" :modal-name="'customers-index-toggle-mobile-' . $customer->id"
                                            :title="$row['toggle_modal_title']" :message="$row['toggle_message']" :notice-message="__('confirm.notification_after_action')" :confirm-label="$row['toggle_label']"
                                            :trigger-label="$row['toggle_label']" :trigger-icon="$row['toggle_icon']" :trigger-class="$row['toggle_class']"
                                            confirm-class="btn-primary-sm" />
                                    @endif
                                </x-ui.table-action-dropdown>
                            </div>
                            <div class="flex items-start gap-3 pr-12">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $row['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['code'] }} - {{ $row['email'] }}</p>
                                </div>
                                <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $row['customer_type_label'] }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('Phone') }}</div>
                                <div>{{ $row['phone'] }}</div>
                                <div>{{ ui_phrase('Country') }}</div>
                                <div>{{ $row['country'] }}</div>
                                <div>{{ ui_phrase('Company') }}</div>
                                <div>{{ $row['company_name'] }}</div>
                                <div>{{ ui_phrase('Status') }}</div>
                                <div><x-ui.status-badge :status="$row['is_active'] ? 'active' : 'inactive'" size="xs" /></div>
                            </div>
                        </div>
                    @empty
                        <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Customers')])" :message="ui_phrase('Try changing filter criteria or add a new customer.')" />
                    @endforelse
                </div>

            <div>{{ $customers->links() }}</div>
        </div>
    </div>
@endsection
