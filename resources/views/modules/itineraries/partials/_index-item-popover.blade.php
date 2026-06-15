<div class="relative inline-block text-left itinerary-items-popover" data-popover-root>
    <button type="button" class="btn-outline-sm" data-popover-trigger aria-expanded="false" aria-haspopup="true">
        Desc
    </button>
    <div class="hidden w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900"
        data-popover-panel role="dialog" aria-label="{{ ui_phrase('Itinerary item list') }}"
        style="position: fixed; z-index: 9999;">
        <span
            class="pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700"
            data-popover-arrow aria-hidden="true"></span>
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ ui_phrase('Item List') }}
        </p>

        @if ($row['transport_items']->isNotEmpty())
            <div class="mb-2 space-y-1 border-b border-gray-200 pb-2 dark:border-gray-700">
                @foreach ($row['transport_items'] as $transportLabel)
                    <div class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                        <i class="fa-solid fa-van-shuttle w-3 text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                        <span>{{ $row['show_transport_day_prefix'] ? 'Day ' . ((int) ($transportLabel['day'] ?? 1)) . ' | ' : '' }}{{ $transportLabel['transport_name'] ?? '-' }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($row['flat_item_names']->isNotEmpty())
            <div class="max-h-64 space-y-2 overflow-auto overscroll-contain pr-1 text-xs text-gray-700 dark:text-gray-200">
                @if ($row['is_multi_day_popover'])
                    @foreach ($row['items_by_day'] as $day => $dayItemNames)
                        <div>
                            <p class="mb-1 font-semibold text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('Day') }} {{ $day }}
                            </p>
                            <ul class="space-y-1">
                                @foreach ($dayItemNames as $itemRow)
                                    @php
                                        $itemName = (string) ($itemRow['label'] ?? '-');
                                        $mealLabel = trim((string) ($itemRow['meal_label'] ?? ''));
                                        $itemKey = trim((string) ($itemRow['item_key'] ?? ''));
                                    @endphp
                                    <li class="flex items-center gap-2">
                                        <i class="fa-solid fa-caret-right w-3 text-[10px] text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                                        <span>{{ $itemName }}</span>
                                        @if ($mealLabel !== '')
                                            <span class="inline-flex items-center rounded border border-sky-300 bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 dark:border-sky-700 dark:bg-sky-900/40 dark:text-sky-200">{{ ui_phrase($mealLabel) }}</span>
                                        @endif
                                        @if ($itemKey !== '' && $itemKey === $row['highlighted_item_key'])
                                            <span class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">{{ ui_phrase('Highlighted') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @else
                    <ul class="space-y-1">
                        @foreach ($row['flat_item_names'] as $itemRow)
                            @php
                                $itemName = (string) ($itemRow['label'] ?? '-');
                                $mealLabel = trim((string) ($itemRow['meal_label'] ?? ''));
                                $itemKey = trim((string) ($itemRow['item_key'] ?? ''));
                            @endphp
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-caret-right w-3 text-[10px] text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                                <span>{{ $itemName }}</span>
                                @if ($mealLabel !== '')
                                    <span class="inline-flex items-center rounded border border-sky-300 bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 dark:border-sky-700 dark:bg-sky-900/40 dark:text-sky-200">{{ ui_phrase($mealLabel) }}</span>
                                @endif
                                @if ($itemKey !== '' && $itemKey === $row['highlighted_item_key'])
                                    <span class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">{{ ui_phrase('Highlighted') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @else
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ ui_phrase('No items available.') }}
            </p>
        @endif

        @if ($row['can_generate_quotation'])
            <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                <a href="{{ $row['generate_quotation_url'] }}" class="btn-primary-sm w-full justify-center">
                    {{ ui_phrase('Generate Quotation') }}
                </a>
            </div>
        @endif
    </div>
</div>
