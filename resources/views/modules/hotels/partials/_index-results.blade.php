<div data-hotels-index-results>
    <div class="space-y-4">
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="table-header">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                #</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Hotel') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Location') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Rooms') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Rates') }}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                {{ ui_phrase('Status') }}</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
                                {{ ui_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($hotelRows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row['row_number'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <div>{{ $row['name'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <div>{{ $row['location_label'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['country_label'] }}</div>
                                    <div class="text-xs text-indigo-600 dark:text-indigo-300">{{ $row['destination_label'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-200">
                                    {{ $row['rooms_count'] }}</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-200">
                                    {{ $row['prices_count'] }}</td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <x-ui.status-badge :status="$row['status_badge']" size="xs" />
                                </td>
                                <td class="px-4 py-3 text-right text-sm actions-compact">
                                    @include('modules.hotels.partials._index-actions', [
                                        'row' => $row,
                                        'modalName' => $row['toggle_modal_name_desktop'],
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6">
                                    <x-ui.empty-state
                                        :title="ui_phrase('No hotels found.')"
                                        :description="ui_phrase('Create a new hotel or adjust your filters.')"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($hotelRows as $row)
                @include('modules.hotels.partials._index-mobile-card', ['row' => $row])
            @empty
                <x-module-empty-state
                    :title="ui_phrase('No hotels found.')"
                    :message="ui_phrase('Create a new hotel or adjust your filters.')"
                />
            @endforelse
        </div>
        <div>{{ $hotels->links() }}</div>
    </div>
</div>


