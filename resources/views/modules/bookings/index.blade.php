@extends('layouts.master')

@section('page_title', ui_phrase('Bookings'))
@section('page_subtitle', ui_phrase('Manage booking records.'))

@section('page_actions')
    <a href="{{ route('bookings.export', request()->query()) }}" class="btn-secondary">{{ ui_phrase('Export CSV') }}</a>
    <a href="{{ route('bookings.create') }}" class="btn-primary">{{ ui_phrase('Add Booking') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--bookings" data-service-filter-page
        data-service-filter-text-debounce-ms="900" data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <x-ui.metric-card :title="ui_phrase('Total Booking')" :value="(int) ($bookingMetrics['total'] ?? 0)" icon="fa-solid fa-suitcase-rolling" />
                    <x-ui.metric-card :title="ui_phrase('Vendor Confirmation')" :value="(int) ($bookingMetrics['vendor_confirmation'] ?? 0)" icon="fa-solid fa-phone-volume" />
                    <x-ui.metric-card :title="ui_phrase('Voucher Preparation')" :value="(int) ($bookingMetrics['voucher_preparation'] ?? 0)" icon="fa-solid fa-file-lines" />
                    <x-ui.metric-card :title="ui_phrase('In Operation')" :value="(int) ($bookingMetrics['in_operation'] ?? 0)" icon="fa-solid fa-route" />
                </div>
                <div class="app-card p-4">
                    <form method="GET" action="{{ route('bookings.index') }}"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" data-service-filter-form
                        data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <div>
                            <input name="order_number" value="{{ request('order_number') }}"
                                list="booking-order-number-suggestions" placeholder="{{ ui_phrase('Order Number') }}"
                                class="app-input" data-filter-min-text="3" data-service-filter-input>
                            <datalist id="booking-order-number-suggestions">
                                @foreach ($quotationSuggestions as $quotationSuggestion)
                                    @if ($quotationSuggestion['order_number'] !== '')
                                        <option value="{{ $quotationSuggestion['order_number'] }}">
                                            {{ $quotationSuggestion['quotation_number'] !== '' ? $quotationSuggestion['quotation_number'] : '-' }} - {{ $quotationSuggestion['customer_name'] }}
                                        </option>
                                    @endif
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <input name="quotation" value="{{ request('quotation') }}" list="booking-quotation-suggestions"
                                placeholder="{{ ui_phrase('Quotation') }}" class="app-input" data-filter-min-text="3"
                                data-service-filter-input>
                            <datalist id="booking-quotation-suggestions">
                                @foreach ($quotationSuggestions as $quotationSuggestion)
                                    <option value="{{ $quotationSuggestion['quotation_number'] }}">
                                        {{ $quotationSuggestion['order_number'] !== '' ? $quotationSuggestion['order_number'] : '-' }} - {{ $quotationSuggestion['customer_name'] }}
                                    </option>
                                @endforeach
                            </datalist>
                        </div>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ($perPageOptions as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                    {{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Status') }}</option>
                            @foreach (\App\Models\Booking::STATUS_OPTIONS as $statusOption)
                                <option value="{{ $statusOption }}" @selected((string) request('status') === (string) $statusOption)>
                                    {{ ui_phrase((string) $statusOption) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-4 filter-actions h-[42px]">
                            <a href="{{ route('bookings.index') }}"
                                class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4" data-service-filter-reset
                                data-page-spinner="off">{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
                <div class="md:hidden space-y-3">
                    @forelse ($bookingRows as $row)
                        @php($booking = $row['booking'])
                        <div class="app-card relative p-4 pt-5">
                            <div class="absolute right-3 top-3 z-10">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ $row['show_url'] }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Detail') }}</span>
                                    </a>
                                    @if ($row['can_edit'])
                                        <a href="{{ $row['edit_url'] }}"
                                            class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Edit') }}</span>
                                        </a>
                                    @endif
                                    @if ($row['can_cancel'])
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action :action="$row['cancel_url']" method="POST" :modal-name="'bookings-index-cancel-mobile-' . $booking->id"
                                            :title="ui_phrase('Cancel Booking')" :message="ui_phrase('confirm cancel booking')" :impact-title="__('confirm.what_will_happen')" :impact-items="[
                                                ui_phrase('Booking status will change to cancelled.'),
                                                ui_phrase('Cancelled booking cannot continue to operation flow.'),
                                            ]"
                                            :notice-message="__('confirm.notification_after_action')" notice-tone="warning" :confirm-label="ui_phrase('Cancel Booking')" :trigger-label="ui_phrase('Cancel Booking')"
                                            trigger-icon="fa-solid fa-ban w-4"
                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20"
                                            confirm-class="btn-danger-sm" />
                                    @endif
                                    @if ($row['can_delete'])
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action :action="$row['delete_url']" method="DELETE" :modal-name="'bookings-index-delete-mobile-' . $booking->id"
                                            :title="ui_phrase('Delete') . ' ' . ui_phrase('Booking')" :message="ui_phrase('confirm delete')" :impact-title="__('confirm.important_warning')" :impact-items="[
                                                __('confirm.delete_itinerary_info_1'),
                                                __('confirm.delete_itinerary_info_2'),
                                            ]"
                                            :notice-message="__('confirm.notification_after_action')" notice-tone="danger" :confirm-label="ui_phrase('Delete')" :trigger-label="ui_phrase('Delete')"
                                            trigger-icon="fa-solid fa-trash w-4"
                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                            confirm-class="btn-danger-sm" />
                                    @endif
                                </x-ui.table-action-dropdown>
                            </div>
                            <div class="flex items-start gap-3 pr-12">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $row['booking_number'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row['order_number'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row['quotation_number'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('Service') }}: {{ $row['service_total'] }} |
                                        {{ $row['voucher_total'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('Travel Date') }}</div>
                                <div><x-ui.date-display :date="$booking->travel_date" format="Y-m-d" /></div>
                                <div>{{ ui_phrase('Handled By') }}</div>
                                <div>{{ $row['handled_by_name'] }}</div>
                                <div>{{ ui_phrase('Status') }}</div>
                                <div><x-ui.status-badge :status="$row['status']" size="xs" /></div>
                            </div>
                        </div>
                    @empty
                        <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Bookings')])" :message="ui_phrase('Try changing filter criteria or add a new booking.')" />
                    @endforelse
                </div>
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="table-header">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Booking No') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Service') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Travel Date') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Handled By') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Status') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
                                        {{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($bookingRows as $row)
                                    @php($booking = $row['booking'])
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $row['row_number'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            <div class="space-y-0.5">
                                                <p class="font-semibold">{{ $row['booking_number'] }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $row['order_number'] }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $row['quotation_number'] }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $row['service_total'] }} |
                                            {{ $row['voucher_total'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-ui.date-display
                                                :date="$booking->travel_date" format="Y-m-d" /></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $row['handled_by_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <x-ui.status-badge :status="$row['status']" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                                <a href="{{ $row['show_url'] }}"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('Detail') }}</span>
                                                </a>

                                                @if ($row['can_edit'])
                                                    <a href="{{ $row['edit_url'] }}"
                                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                                        <span>{{ ui_phrase('Edit') }}</span>
                                                    </a>
                                                @endif

                                                @if ($row['can_cancel'])
                                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                                    <x-ui.confirm-action :action="$row['cancel_url']" method="POST"
                                                        :modal-name="'bookings-index-cancel-desktop-' . $booking->id" :title="ui_phrase('Cancel Booking')" :message="ui_phrase('confirm cancel booking')"
                                                        :impact-title="__('confirm.what_will_happen')" :impact-items="[
                                                            ui_phrase('Booking status will change to cancelled.'),
                                                            ui_phrase(
                                                                'Cancelled booking cannot continue to operation flow.',
                                                            ),
                                                        ]" :notice-message="__('confirm.notification_after_action')"
                                                        notice-tone="warning" :confirm-label="ui_phrase('Cancel Booking')" :trigger-label="ui_phrase('Cancel Booking')"
                                                        trigger-icon="fa-solid fa-ban w-4"
                                                        trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                                        confirm-class="btn-danger-sm" />
                                                @endif

                                                @if ($row['can_delete'])
                                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                                    <x-ui.confirm-action :action="$row['delete_url']" method="DELETE" :modal-name="'bookings-index-delete-desktop-' . $booking->id"
                                                        :title="ui_phrase('Delete') . ' ' . ui_phrase('Booking')" :message="ui_phrase('confirm delete')" :impact-title="__('confirm.important_warning')"
                                                        :impact-items="[
                                                            __('confirm.delete_itinerary_info_1'),
                                                            __('confirm.delete_itinerary_info_2'),
                                                        ]" :notice-message="__('confirm.notification_after_action')" notice-tone="danger"
                                                        :confirm-label="ui_phrase('Delete')" :trigger-label="ui_phrase('Delete')"
                                                        trigger-icon="fa-solid fa-trash w-4"
                                                        trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                                        confirm-class="btn-danger-sm" />
                                                @endif
                                            </x-ui.table-action-dropdown>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6">
                                            <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Bookings')])" :message="ui_phrase('Try changing filter criteria or add a new booking.')" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            <div>{{ $bookings->links() }}</div>
        </div>
    </div>
@endsection
