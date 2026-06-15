@php($booking = $row['booking'])

<div class="app-card relative p-4 pt-5">
    <div class="absolute right-3 top-3 z-10">
        @include('modules.bookings.partials._index-actions', [
            'row' => $row,
            'cancelModalName' => 'bookings-index-cancel-mobile-' . $booking->id,
            'deleteModalName' => 'bookings-index-delete-mobile-' . $booking->id,
        ])
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
