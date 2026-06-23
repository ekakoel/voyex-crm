<div class="app-card relative p-4 pt-5">
    <div class="absolute right-3 top-3 z-10">
        @include('modules.hotels.partials._index-actions', [
            'row' => $row,
            'modalName' => $row['toggle_modal_name_mobile'],
        ])
    </div>
    <div class="flex items-start justify-between gap-3 pr-12">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $row['name'] }}</p>
        </div>
        <x-ui.status-badge :status="$row['status_badge']" size="xs" />
    </div>
    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
        <div>{{ ui_phrase('Location') }}</div>
        <div>{{ $row['location_label'] }}</div>
        <div>{{ ui_phrase('Country') }}</div>
        <div>{{ $row['country_label'] }}</div>
        <div>{{ ui_phrase('Destination') }}</div>
        <div>{{ $row['destination_label'] }}</div>
        <div>{{ ui_phrase('Rooms') }}</div>
        <div>{{ $row['rooms_count'] }}</div>
        <div>{{ ui_phrase('Rates') }}</div>
        <div>{{ $row['prices_count'] }}</div>
    </div>
</div>
