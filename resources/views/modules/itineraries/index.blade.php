@extends('layouts.master')
@section('page_title', ui_phrase('Itineraries'))
@section('page_subtitle', ui_phrase('Manage itinerary records.'))
@section('page_actions')
    <a href="{{ route('itineraries.create') }}" class="btn-primary">
        {{ ui_phrase('Create Itinerary') }}
    </a>
@endsection
@section('content')
    @php
        $currentUser = auth()->user();
        $canGenerateQuotationFromItinerary = static function ($itinerary) use ($currentUser): bool {
            return Route::has('quotations.create') &&
                $currentUser &&
                $currentUser->can('module.quotations.access') &&
                $currentUser->hasAnyRole(['Reservation', 'Manager', 'Director']) &&
                (int) ($itinerary->created_by ?? 0) === (int) $currentUser->id;
        };
        $resolveMealLabel = static function (?string $mealType, ?string $mealPeriod = null): ?string {
            $value = trim((string) ($mealType ?: $mealPeriod ?: ''));
            if ($value === '') {
                return null;
            }

            return match (strtolower($value)) {
                'breakfast' => 'Breakfast',
                'lunch' => 'Lunch',
                'dinner' => 'Dinner',
                default => \Illuminate\Support\Str::headline($value),
            };
        };
        $buildPopupItemKey = static function (array $row): string {
            return implode('|', [
                max(1, (int) ($row['day'] ?? 1)),
                trim((string) ($row['start_time'] ?? '')),
                (int) ($row['sort_order'] ?? 0),
                strtolower(trim((string) ($row['item_name'] ?? ($row['label'] ?? '')))),
                strtolower(trim((string) ($row['vendor_name'] ?? ''))),
                strtolower(trim((string) ($row['meal_label'] ?? ''))),
            ]);
        };
        $normalizePopupItemRow = static function (array $row) use ($buildPopupItemKey): array {
            $row['label'] = trim((string) ($row['label'] ?? ''));
            $row['item_name'] = trim((string) ($row['item_name'] ?? ''));
            $row['vendor_name'] = trim((string) ($row['vendor_name'] ?? ''));
            $row['meal_label'] = trim((string) ($row['meal_label'] ?? ''));
            $row['item_key'] = $buildPopupItemKey($row);

            return $row;
        };
        $resolveHighlightedPopupItemKey = static function ($dayItems, $highlightedDayPoint) use (
            $resolveMealLabel,
        ): string {
            if (!$highlightedDayPoint || !$dayItems instanceof \Illuminate\Support\Collection || $dayItems->isEmpty()) {
                return '';
            }

            $highlightedDay = max(1, (int) ($highlightedDayPoint->day_number ?? 1));
            $mainType = strtolower(trim((string) ($highlightedDayPoint->main_experience_type ?? '')));
            $mainAttraction = $highlightedDayPoint->mainTouristAttraction;
            $mainActivity = $highlightedDayPoint->mainActivity;
            $mainFoodBeverage = $highlightedDayPoint->mainFoodBeverage;

            $highlightedItemName = '';
            $highlightedItemVendorName = '';
            $highlightedMealLabel = '';

            if (in_array($mainType, ['attraction', 'tourist_attraction'], true)) {
                $highlightedItemName = trim((string) ($mainAttraction?->name ?? ''));
            } elseif (in_array($mainType, ['activity'], true)) {
                $highlightedItemName = trim((string) ($mainActivity?->name ?? ''));
                $highlightedItemVendorName = trim((string) ($mainActivity?->vendor?->name ?? ''));
            } elseif (in_array($mainType, ['fnb', 'food_beverage'], true)) {
                $highlightedItemName = trim((string) ($mainFoodBeverage?->name ?? ''));
                $highlightedItemVendorName = trim((string) ($mainFoodBeverage?->vendor?->name ?? ''));
                $highlightedMealLabel = trim(
                    (string) $resolveMealLabel(null, $mainFoodBeverage?->meal_period ?? null),
                );
            }

            if ($highlightedItemName === '') {
                if ($mainAttraction) {
                    $highlightedItemName = trim((string) ($mainAttraction->name ?? ''));
                } elseif ($mainActivity) {
                    $highlightedItemName = trim((string) ($mainActivity->name ?? ''));
                    $highlightedItemVendorName = trim((string) ($mainActivity->vendor?->name ?? ''));
                } elseif ($mainFoodBeverage) {
                    $highlightedItemName = trim((string) ($mainFoodBeverage->name ?? ''));
                    $highlightedItemVendorName = trim((string) ($mainFoodBeverage->vendor?->name ?? ''));
                    $highlightedMealLabel = trim(
                        (string) $resolveMealLabel(null, $mainFoodBeverage->meal_period ?? null),
                    );
                }
            }

            if ($highlightedItemName === '') {
                return '';
            }

            $findHighlightedRow = static function (string $expectedMealLabel = '') use (
                $dayItems,
                $highlightedDay,
                $highlightedItemName,
                $highlightedItemVendorName,
            ) {
                return $dayItems->first(function ($row) use (
                    $highlightedDay,
                    $highlightedItemName,
                    $highlightedItemVendorName,
                    $expectedMealLabel,
                ) {
                    if ((int) ($row['day'] ?? 0) !== $highlightedDay) {
                        return false;
                    }
                    if (trim((string) ($row['item_name'] ?? '')) !== $highlightedItemName) {
                        return false;
                    }
                    if (trim((string) ($row['vendor_name'] ?? '')) !== $highlightedItemVendorName) {
                        return false;
                    }
                    if (
                        $expectedMealLabel !== '' &&
                        trim((string) ($row['meal_label'] ?? '')) !== $expectedMealLabel
                    ) {
                        return false;
                    }

                    return true;
                });
            };

            $highlightedRow = $findHighlightedRow($highlightedMealLabel);
            if (!is_array($highlightedRow) && $highlightedMealLabel !== '') {
                $highlightedRow = $findHighlightedRow();
            }

            return is_array($highlightedRow) ? trim((string) ($highlightedRow['item_key'] ?? '')) : '';
        };
    @endphp
    <div class="space-y-5 module-page module-page--itineraries" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main min-w-0">
            <div data-service-filter-results>
                <div class="app-card p-4 mb-3">
                    <form method="GET" action="{{ route('itineraries.index') }}"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" data-service-filter-form
                        data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input type="text" value="{{ request('title') }}"
                            placeholder="{{ ui_phrase('Search') }}"
                            class="app-input sm:col-span-2 lg:col-span-2" data-filter-title-visible
                            data-filter-min-text="3">
                        <input type="hidden" name="title" value="{{ request('title') }}"
                            data-service-filter-input data-filter-title-hidden>
                        <select name="destination_id" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('All destinations') }}</option>
                            @foreach ($destinations as $destination)
                                <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>
                                    {{ $destination->name }}</option>
                            @endforeach
                        </select>
                        <input name="duration" type="number" min="1" value="{{ request('duration') }}"
                            placeholder="{{ ui_phrase('Duration (days)') }}" class="app-input"
                            data-service-filter-input>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                    {{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div
                            class="flex items-center gap-2 sm:col-span-2 lg:col-span-4 filter-actions h-[42px]">
                            <a href="{{ route('itineraries.index', ['reset' => 1]) }}"
                                class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4"
                                data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="table-header">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="w-1/2 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Title') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Duration') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Quotation') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Capacity') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Item List') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
                                        {{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($itineraries as $index => $itinerary)
                                    @php
                                        $formatItemWithVendor = static function (
                                            ?string $itemName,
                                            ?string $vendorName,
                                        ): ?string {
                                            $name = trim((string) $itemName);
                                            if ($name === '') {
                                                return null;
                                            }
                                            $vendor = trim((string) $vendorName);
                                            return $vendor !== '' ? $name . ' | ' . $vendor : $name;
                                        };

                                        $dayItems = collect()
                                            ->merge(
                                                $itinerary->touristAttractions->map(
                                                    fn($item) => [
                                                        'day' => max(1, (int) ($item->pivot->day_number ?? 1)),
                                                        'start_time' => trim(
                                                            (string) ($item->pivot->start_time ?? ''),
                                                        ),
                                                        'sort_order' => (int) ($item->pivot->visit_order ?? 0),
                                                        'label' => trim((string) ($item->name ?? '')),
                                                        'item_name' => trim((string) ($item->name ?? '')),
                                                        'vendor_name' => '',
                                                    ],
                                                ),
                                            )
                                            ->merge(
                                                $itinerary->itineraryActivities->map(
                                                    fn($item) => [
                                                        'day' => max(1, (int) ($item->day_number ?? 1)),
                                                        'start_time' => trim((string) ($item->start_time ?? '')),
                                                        'sort_order' => (int) ($item->visit_order ?? 0),
                                                        'label' => (string) $formatItemWithVendor(
                                                            $item->activity?->name,
                                                            $item->activity?->vendor?->name,
                                                        ),
                                                        'item_name' => trim(
                                                            (string) ($item->activity?->name ?? ''),
                                                        ),
                                                        'vendor_name' => trim(
                                                            (string) ($item->activity?->vendor?->name ?? ''),
                                                        ),
                                                    ],
                                                ),
                                            )
                                            ->merge(
                                                $itinerary->itineraryIslandTransfers->map(
                                                    fn($item) => [
                                                        'day' => max(1, (int) ($item->day_number ?? 1)),
                                                        'start_time' => trim((string) ($item->start_time ?? '')),
                                                        'sort_order' => (int) ($item->visit_order ?? 0),
                                                        'label' => (string) $formatItemWithVendor(
                                                            $item->islandTransfer?->name,
                                                            $item->islandTransfer?->vendor?->name,
                                                        ),
                                                        'item_name' => trim(
                                                            (string) ($item->islandTransfer?->name ?? ''),
                                                        ),
                                                        'vendor_name' => trim(
                                                            (string) ($item->islandTransfer?->vendor?->name ?? ''),
                                                        ),
                                                    ],
                                                ),
                                            )
                                            ->merge(
                                                $itinerary->itineraryFoodBeverages->map(
                                                    fn($item) => [
                                                        'day' => max(1, (int) ($item->day_number ?? 1)),
                                                        'start_time' => trim((string) ($item->start_time ?? '')),
                                                        'sort_order' => (int) ($item->visit_order ?? 0),
                                                        'label' => (string) $formatItemWithVendor(
                                                            $item->foodBeverage?->name,
                                                            $item->foodBeverage?->vendor?->name,
                                                        ),
                                                        'item_name' => trim(
                                                            (string) ($item->foodBeverage?->name ?? ''),
                                                        ),
                                                        'vendor_name' => trim(
                                                            (string) ($item->foodBeverage?->vendor?->name ?? ''),
                                                        ),
                                                        'meal_label' => $resolveMealLabel(
                                                            $item->meal_type ?? null,
                                                            $item->foodBeverage?->meal_period ?? null,
                                                        ),
                                                    ],
                                                ),
                                            )
                                            ->filter(fn($row) => filled($row['label'] ?? null))
                                            ->sort(function ($left, $right) {
                                                $dayComparison =
                                                    ((int) ($left['day'] ?? 0)) <=> ((int) ($right['day'] ?? 0));
                                                if ($dayComparison !== 0) {
                                                    return $dayComparison;
                                                }

                                                $leftTime = (string) ($left['start_time'] ?? '');
                                                $rightTime = (string) ($right['start_time'] ?? '');
                                                if (
                                                    $leftTime !== '' &&
                                                    $rightTime !== '' &&
                                                    $leftTime !== $rightTime
                                                ) {
                                                    return strcmp($leftTime, $rightTime);
                                                }
                                                if ($leftTime !== '' && $rightTime === '') {
                                                    return -1;
                                                }
                                                if ($leftTime === '' && $rightTime !== '') {
                                                    return 1;
                                                }

                                                return ((int) ($left['sort_order'] ?? 0)) <=>
                                                    ((int) ($right['sort_order'] ?? 0));
                                            })
                                            ->map($normalizePopupItemRow)
                                            ->values();

                                        $itemsByDay = $dayItems
                                            ->groupBy('day')
                                            ->map(fn($items) => $items
                                                ->map(fn($row) => [
                                                    'label' => trim((string) ($row['label'] ?? '')),
                                                    'meal_label' => trim((string) ($row['meal_label'] ?? '')),
                                                    'item_key' => trim((string) ($row['item_key'] ?? '')),
                                                ])
                                                ->filter(fn($row) => $row['label'] !== '')
                                                ->unique(fn($row) => (string) ($row['item_key'] ?? ''))
                                                ->values())
                                            ->sortKeys();

                                        $isMultiDayPopover = (int) ($itinerary->duration_days ?? 1) > 1;
                                        $flatItemNames = $itemsByDay
                                            ->flatten(1)
                                            ->unique(fn($row) => (string) ($row['item_key'] ?? ''))
                                            ->values();
                                        $highlightedDayPoint = $itinerary->dayPoints
                                            ->filter(function ($point) {
                                                return filled($point->main_experience_type) ||
                                                    (int) ($point->main_tourist_attraction_id ?? 0) > 0 ||
                                                    (int) ($point->main_activity_id ?? 0) > 0 ||
                                                    (int) ($point->main_food_beverage_id ?? 0) > 0;
                                            })
                                            ->sortBy(fn($point) => (int) ($point->day_number ?? 0))
                                            ->first();
                                        $highlightedItemKey = $resolveHighlightedPopupItemKey(
                                            $dayItems,
                                            $highlightedDayPoint,
                                        );
                                        $resolveHotelRegion = static function ($hotel): string {
                                            if (!$hotel) {
                                                return '-';
                                            }
                                            $region = trim((string) ($hotel->region ?? ''));
                                            if ($region !== '') {
                                                return $region;
                                            }
                                            $region = trim((string) ($hotel->city ?? ''));
                                            if ($region !== '') {
                                                return $region;
                                            }
                                            $region = trim((string) ($hotel->province ?? ''));
                                            if ($region !== '') {
                                                return $region;
                                            }
                                            $region = trim(
                                                (string) ($hotel->destination?->province ??
                                                    ($hotel->destination?->name ?? '')),
                                            );
                                            return $region !== '' ? $region : '-';
                                        };
                                        $resolveStartLabelFromPoint = static function ($point) use (
                                            $resolveHotelRegion,
                                        ): string {
                                            if (!$point) {
                                                return '';
                                            }
                                            $startType = strtolower(
                                                trim((string) ($point->start_point_type ?? '')),
                                            );
                                            if ($startType === 'airport') {
                                                return trim((string) ($point->startAirport?->name ?? ''));
                                            }
                                            if ($startType !== 'hotel' && $startType !== 'previous_day_end') {
                                                return '';
                                            }
                                            $isSelfBooked =
                                                strtolower(
                                                    trim((string) ($point->start_hotel_booking_mode ?? '')),
                                                ) === 'self';
                                            if ($isSelfBooked) {
                                                return trim((string) ($point->start_hotel_area ?? ''));
                                            }
                                            return trim((string) ($point->startHotel?->name ?? ''));
                                        };
                                        $resolveEndLabelFromPoint = static function ($point) use (
                                            $resolveHotelRegion,
                                        ): string {
                                            if (!$point) {
                                                return '';
                                            }
                                            $endType = strtolower(trim((string) ($point->end_point_type ?? '')));
                                            if ($endType === 'airport') {
                                                return trim((string) ($point->endAirport?->name ?? ''));
                                            }
                                            if ($endType !== 'hotel') {
                                                return '';
                                            }
                                            $isSelfBooked =
                                                strtolower(
                                                    trim((string) ($point->end_hotel_booking_mode ?? '')),
                                                ) === 'self';
                                            if ($isSelfBooked) {
                                                return trim((string) ($point->end_hotel_area ?? ''));
                                            }
                                            return trim((string) ($point->endHotel?->name ?? ''));
                                        };
                                        $sortedDayPoints = $itinerary->dayPoints
                                            ->sortBy(fn($point) => (int) ($point->day_number ?? 0))
                                            ->values();
                                        $firstDayPoint = $sortedDayPoints->first();
                                        $lastDayPoint = $sortedDayPoints->last();
                                        $startPointLabel = $resolveStartLabelFromPoint($firstDayPoint);
                                        $endPointLabel = $resolveEndLabelFromPoint($lastDayPoint);
                                        if ($startPointLabel === '') {
                                            $startPointLabel =
                                                (string) ($sortedDayPoints
                                                    ->map(fn($point) => $resolveStartLabelFromPoint($point))
                                                    ->first(fn($label) => trim((string) $label) !== '') ?? '');
                                        }
                                        if ($startPointLabel === '') {
                                            $startPointLabel =
                                                (string) ($sortedDayPoints
                                                    ->map(fn($point) => $resolveEndLabelFromPoint($point))
                                                    ->first(fn($label) => trim((string) $label) !== '') ?? '');
                                        }
                                        if ($endPointLabel === '') {
                                            $endPointLabel =
                                                (string) ($sortedDayPoints
                                                    ->reverse()
                                                    ->map(fn($point) => $resolveEndLabelFromPoint($point))
                                                    ->first(fn($label) => trim((string) $label) !== '') ?? '');
                                        }
                                        if ($endPointLabel === '') {
                                            $endPointLabel =
                                                (string) ($sortedDayPoints
                                                    ->reverse()
                                                    ->map(fn($point) => $resolveStartLabelFromPoint($point))
                                                    ->first(fn($label) => trim((string) $label) !== '') ?? '');
                                        }
                                        $startPointLabel = $startPointLabel !== '' ? $startPointLabel : '-';
                                        $endPointLabel = $endPointLabel !== '' ? $endPointLabel : '-';
                                        $titleWithHighlight =
                                            trim((string) ($itinerary->title ?? '')) .
                                            ' | Start: ' .
                                            $startPointLabel .
                                            ' - End: ' .
                                            $endPointLabel;
                                        $capacityByDay = $itinerary->itineraryTransportUnits
                                            ->groupBy(fn($row) => max(1, (int) ($row->day_number ?? 1)))
                                            ->map(
                                                fn($rows) => (int) $rows->sum(
                                                    fn($row) => max(
                                                        0,
                                                        (int) ($row->transportUnit?->seat_capacity ?? 0),
                                                    ),
                                                ),
                                            );
                                        $totalCapacity = (int) ($capacityByDay->max() ?? 0);
                                        $showTransportDayPrefix = (int) ($itinerary->duration_days ?? 1) > 1;
                                        $transportItems = $itinerary->itineraryTransportUnits
                                            ->map(function ($row) {
                                                $dayNumber = max(1, (int) ($row->day_number ?? 1));
                                                $unitName = trim((string) ($row->transportUnit?->name ?? ''));
                                                $brandName = trim(
                                                    (string) ($row->transportUnit?->brand_model ?? ''),
                                                );
                                                if ($unitName === '' && $brandName === '') {
                                                    return null;
                                                }
                                                $transportName = trim($brandName . ' ' . $unitName);
                                                if ($transportName === '') {
                                                    $transportName = '-';
                                                }
                                                return [
                                                    'day' => $dayNumber,
                                                    'transport_name' => $transportName,
                                                ];
                                            })
                                            ->filter(
                                                fn($row) => is_array($row) &&
                                                    filled($row['transport_name'] ?? null),
                                            )
                                            ->unique(
                                                fn($row) => strtolower((string) ($row['transport_name'] ?? '')) .
                                                    '|' .
                                                    (int) ($row['day'] ?? 0),
                                            )
                                            ->sortBy(
                                                fn($row) => [
                                                    (int) ($row['day'] ?? 0),
                                                    strtolower((string) ($row['transport_name'] ?? '')),
                                                ],
                                            )
                                            ->values();
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                                            {{ ++$index }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            <div class="font-medium">{{ $titleWithHighlight }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ ui_phrase('by :name', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), ui_phrase('system')) ?: '-']) }}
                                            </div>
                                            {{-- <div class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('by -', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), ui_phrase('system')) ?: '-']) }}</div> --}}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div>
                                                {{ $itinerary->duration_days }}D{{ $itinerary->duration_nights > 0 ? '/' . $itinerary->duration_nights . 'N' : '' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $itinerary->destination?->name ?? ($itinerary->destination ?? '-') }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            @php
                                                $quotationCount =
                                                    (int) ($itinerary->quotations_count ??
                                                        ($itinerary->quotations?->count() ?? 0));
                                            @endphp
                                            @if ($quotationCount > 0)
                                                <div class="relative inline-block text-left itinerary-items-popover"
                                                    data-popover-root>
                                                    <button type="button"
                                                        class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200"
                                                        data-popover-trigger aria-expanded="false" aria-haspopup="true">
                                                        {{ $quotationCount }}
                                                    </button>
                                                    <div class="hidden w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                                        data-popover-panel role="dialog"
                                                        aria-label="{{ ui_phrase('Quotation list') }}"
                                                        style="position: fixed; z-index: 9999;">
                                                        <span
                                                            class="pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700"
                                                            data-popover-arrow aria-hidden="true"></span>
                                                        <p
                                                            class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                            {{ ui_phrase('Quotation') }}</p>
                                                        <ul class="space-y-1 text-xs text-gray-700 dark:text-gray-200">
                                                            @foreach ($itinerary->quotations as $quotation)
                                                                @php
                                                                    $orderNumber = trim(
                                                                        (string) ($quotation->order_number ?? ''),
                                                                    );
                                                                    $fallbackNumber = trim(
                                                                        (string) ($quotation->quotation_number ??
                                                                            ''),
                                                                    );
                                                                    $displayNumber =
                                                                        $orderNumber !== ''
                                                                            ? $orderNumber
                                                                            : ($fallbackNumber !== ''
                                                                                ? $fallbackNumber
                                                                                : '#' . (int) $quotation->id);
                                                                @endphp
                                                                <li>
                                                                    <a href="{{ route('quotations.show', $quotation) }}"
                                                                        class="hover:underline">
                                                                        {{ $displayNumber }}
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                                                    0
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <span
                                                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
                                                {{ $totalCapacity }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="relative inline-block text-left itinerary-items-popover"
                                                data-popover-root>
                                                <button type="button" class="btn-outline-sm" data-popover-trigger
                                                    aria-expanded="false" aria-haspopup="true">
                                                    Desc
                                                </button>
                                                <div class="hidden w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                                    data-popover-panel role="dialog"
                                                    aria-label="{{ ui_phrase('Itinerary item list') }}"
                                                    style="position: fixed; z-index: 9999;">
                                                    <span
                                                        class="pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700"
                                                        data-popover-arrow aria-hidden="true"></span>
                                                    <p
                                                        class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                        {{ ui_phrase('Item List') }}</p>
                                                    @if ($transportItems->isNotEmpty())
                                                        <div
                                                            class="mb-2 space-y-1 border-b border-gray-200 pb-2 dark:border-gray-700">
                                                            @foreach ($transportItems as $transportLabel)
                                                                <div
                                                                    class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                                                    <i class="fa-solid fa-van-shuttle w-3 text-gray-500 dark:text-gray-400"
                                                                        aria-hidden="true"></i>
                                                                    <span>{{ $showTransportDayPrefix ? 'Day ' . ((int) ($transportLabel['day'] ?? 1)) . ' | ' : '' }}{{ $transportLabel['transport_name'] ?? '-' }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if ($flatItemNames->isNotEmpty())
                                                        <div
                                                            class="max-h-64 space-y-2 overflow-auto overscroll-contain pr-1 text-xs text-gray-700 dark:text-gray-200">
                                                            @if ($isMultiDayPopover)
                                                                @foreach ($itemsByDay as $day => $dayItemNames)
                                                                    <div>
                                                                        <p
                                                                            class="mb-1 font-semibold text-gray-500 dark:text-gray-400">
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
                                                                                    <i class="fa-solid fa-caret-right w-3 text-[10px] text-gray-500 dark:text-gray-400"
                                                                                        aria-hidden="true"></i>
                                                                                    <span>{{ $itemName }}</span>
                                                                                    @if ($mealLabel !== '')
                                                                                        <span
                                                                                            class="inline-flex items-center rounded border border-sky-300 bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 dark:border-sky-700 dark:bg-sky-900/40 dark:text-sky-200">{{ ui_phrase($mealLabel) }}</span>
                                                                                    @endif
                                                                                    @if ($itemKey !== '' && $itemKey === $highlightedItemKey)
                                                                                        <span
                                                                                            class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">{{ ui_phrase('Highlighted') }}</span>
                                                                                    @endif
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <ul class="space-y-1">
                                                                    @foreach ($flatItemNames as $itemRow)
                                                                        @php
                                                                            $itemName = (string) ($itemRow['label'] ?? '-');
                                                                            $mealLabel = trim((string) ($itemRow['meal_label'] ?? ''));
                                                                            $itemKey = trim((string) ($itemRow['item_key'] ?? ''));
                                                                        @endphp
                                                                        <li class="flex items-center gap-2">
                                                                            <i class="fa-solid fa-caret-right w-3 text-[10px] text-gray-500 dark:text-gray-400"
                                                                                aria-hidden="true"></i>
                                                                            <span>{{ $itemName }}</span>
                                                                            @if ($mealLabel !== '')
                                                                                <span
                                                                                    class="inline-flex items-center rounded border border-sky-300 bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 dark:border-sky-700 dark:bg-sky-900/40 dark:text-sky-200">{{ ui_phrase($mealLabel) }}</span>
                                                                            @endif
                                                                            @if ($itemKey !== '' && $itemKey === $highlightedItemKey)
                                                                                <span
                                                                                    class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">{{ ui_phrase('Highlighted') }}</span>
                                                                            @endif
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ ui_phrase('No items available.') }}</p>
                                                    @endif
                                                    @if ($canGenerateQuotationFromItinerary($itinerary))
                                                        <div
                                                            class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                                            <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}"
                                                                class="btn-primary-sm w-full justify-center">
                                                                {{ ui_phrase('Generate Quotation') }}
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                                    <x-slot:trigger>
                                                        <i class="fa-solid fa-ellipsis"></i>
                                                    </x-slot:trigger>
                                                    <a href="{{ route('itineraries.show', $itinerary) }}"
                                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"><i
                                                            class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i><span>{{ ui_phrase('View') }}</span></a>
                                                    @if (!$itinerary->trashed())
                                                        <x-ui.confirm-action :action="route('itineraries.duplicate', $itinerary)" method="POST"
                                                            :modal-name="'itinerary-index-duplicate-desktop-' .
                                                                $itinerary->id" :title="ui_phrase('Duplicate Itinerary')" :message="ui_phrase('confirm duplicate')"
                                                            :impact-title="__('confirm.what_will_happen')" :impact-items="[
                                                                __('confirm.duplicate_itinerary_info_1'),
                                                                __('confirm.duplicate_itinerary_info_2'),
                                                                __('confirm.duplicate_itinerary_info_3'),
                                                            ]" :notice-message="__('confirm.notification_after_action')"
                                                            notice-tone="info" :confirm-label="ui_phrase('Duplicate')" :trigger-label="ui_phrase('Duplicate')"
                                                            trigger-icon="fa-solid fa-copy w-4 text-gray-500 dark:text-gray-400"
                                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                                                            confirm-class="btn-primary-sm" />
                                                    @endif
                                                    @can('update', $itinerary)
                                                        <a href="{{ route('itineraries.edit', $itinerary) }}"
                                                            class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"><i
                                                                class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i><span>{{ ui_phrase('Edit') }}</span></a>
                                                    @endcan
                                                    @if (auth()->user()
                                                            ?->hasAnyRole(['Super Admin', 'Super User', 'Administrator']))
                                                        <div
                                                            class="my-1 border-t border-gray-200 dark:border-gray-700">
                                                        </div>
                                                        <x-ui.confirm-action :action="route('itineraries.destroy', $itinerary)" method="DELETE"
                                                            :modal-name="'itinerary-index-delete-desktop-' .
                                                                $itinerary->id" :title="ui_phrase('Delete Itinerary')" :message="ui_phrase(
                                                                'Are you sure you want to delete this itinerary?',
                                                            )"
                                                            :impact-title="__('confirm.important_warning')" :impact-items="[
                                                                __('confirm.delete_itinerary_info_1'),
                                                                __('confirm.delete_itinerary_info_2'),
                                                                __('confirm.delete_itinerary_info_3'),
                                                            ]" :notice-message="__('confirm.notification_after_action')"
                                                            notice-tone="danger" :confirm-label="ui_phrase('Delete')" :trigger-label="ui_phrase('Delete')"
                                                            trigger-icon="fa-solid fa-trash w-4"
                                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                                            confirm-class="btn-danger-sm" />
                                                    @endif
                                                </x-ui.table-action-dropdown>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Itineraries')]) }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="md:hidden space-y-3">
                    @forelse ($itineraries as $itinerary)
                        @php
                            $formatItemWithVendor = static function (
                                ?string $itemName,
                                ?string $vendorName,
                            ): ?string {
                                $name = trim((string) $itemName);
                                if ($name === '') {
                                    return null;
                                }
                                $vendor = trim((string) $vendorName);
                                return $vendor !== '' ? $name . ' | ' . $vendor : $name;
                            };

                            $dayItems = collect()
                                ->merge(
                                    $itinerary->touristAttractions->map(
                                        fn($item) => [
                                            'day' => max(1, (int) ($item->pivot->day_number ?? 1)),
                                            'start_time' => trim((string) ($item->pivot->start_time ?? '')),
                                            'sort_order' => (int) ($item->pivot->visit_order ?? 0),
                                            'label' => trim((string) ($item->name ?? '')),
                                            'item_name' => trim((string) ($item->name ?? '')),
                                            'vendor_name' => '',
                                        ],
                                    ),
                                )
                                ->merge(
                                    $itinerary->itineraryActivities->map(
                                        fn($item) => [
                                            'day' => max(1, (int) ($item->day_number ?? 1)),
                                            'start_time' => trim((string) ($item->start_time ?? '')),
                                            'sort_order' => (int) ($item->visit_order ?? 0),
                                            'label' => (string) $formatItemWithVendor(
                                                $item->activity?->name,
                                                $item->activity?->vendor?->name,
                                            ),
                                            'item_name' => trim((string) ($item->activity?->name ?? '')),
                                            'vendor_name' => trim((string) ($item->activity?->vendor?->name ?? '')),
                                        ],
                                    ),
                                )
                                ->merge(
                                    $itinerary->itineraryIslandTransfers->map(
                                        fn($item) => [
                                            'day' => max(1, (int) ($item->day_number ?? 1)),
                                            'start_time' => trim((string) ($item->start_time ?? '')),
                                            'sort_order' => (int) ($item->visit_order ?? 0),
                                            'label' => (string) $formatItemWithVendor(
                                                $item->islandTransfer?->name,
                                                $item->islandTransfer?->vendor?->name,
                                            ),
                                            'item_name' => trim((string) ($item->islandTransfer?->name ?? '')),
                                            'vendor_name' => trim(
                                                (string) ($item->islandTransfer?->vendor?->name ?? ''),
                                            ),
                                        ],
                                    ),
                                )
                                ->merge(
                                    $itinerary->itineraryFoodBeverages->map(
                                        fn($item) => [
                                            'day' => max(1, (int) ($item->day_number ?? 1)),
                                            'start_time' => trim((string) ($item->start_time ?? '')),
                                            'sort_order' => (int) ($item->visit_order ?? 0),
                                            'label' => (string) $formatItemWithVendor(
                                                $item->foodBeverage?->name,
                                                $item->foodBeverage?->vendor?->name,
                                            ),
                                            'item_name' => trim((string) ($item->foodBeverage?->name ?? '')),
                                            'vendor_name' => trim(
                                                (string) ($item->foodBeverage?->vendor?->name ?? ''),
                                            ),
                                            'meal_label' => $resolveMealLabel(
                                                $item->meal_type ?? null,
                                                $item->foodBeverage?->meal_period ?? null,
                                            ),
                                        ],
                                    ),
                                )
                                ->filter(fn($row) => filled($row['label'] ?? null))
                                ->sort(function ($left, $right) {
                                    $dayComparison = ((int) ($left['day'] ?? 0)) <=> ((int) ($right['day'] ?? 0));
                                    if ($dayComparison !== 0) {
                                        return $dayComparison;
                                    }

                                    $leftTime = (string) ($left['start_time'] ?? '');
                                    $rightTime = (string) ($right['start_time'] ?? '');
                                    if ($leftTime !== '' && $rightTime !== '' && $leftTime !== $rightTime) {
                                        return strcmp($leftTime, $rightTime);
                                    }
                                    if ($leftTime !== '' && $rightTime === '') {
                                        return -1;
                                    }
                                    if ($leftTime === '' && $rightTime !== '') {
                                        return 1;
                                    }

                                    return ((int) ($left['sort_order'] ?? 0)) <=>
                                        ((int) ($right['sort_order'] ?? 0));
                                })
                                ->map($normalizePopupItemRow)
                                ->values();

                            $itemsByDay = $dayItems
                                ->groupBy('day')
                                ->map(fn($items) => $items
                                    ->map(fn($row) => [
                                        'label' => trim((string) ($row['label'] ?? '')),
                                        'meal_label' => trim((string) ($row['meal_label'] ?? '')),
                                        'item_key' => trim((string) ($row['item_key'] ?? '')),
                                    ])
                                    ->filter(fn($row) => $row['label'] !== '')
                                    ->unique(fn($row) => (string) ($row['item_key'] ?? ''))
                                    ->values())
                                ->sortKeys();

                            $isMultiDayPopover = (int) ($itinerary->duration_days ?? 1) > 1;
                            $flatItemNames = $itemsByDay
                                ->flatten(1)
                                ->unique(fn($row) => (string) ($row['item_key'] ?? ''))
                                ->values();
                            $highlightedDayPoint = $itinerary->dayPoints
                                ->filter(function ($point) {
                                    return filled($point->main_experience_type) ||
                                        (int) ($point->main_tourist_attraction_id ?? 0) > 0 ||
                                        (int) ($point->main_activity_id ?? 0) > 0 ||
                                        (int) ($point->main_food_beverage_id ?? 0) > 0;
                                })
                                ->sortBy(fn($point) => (int) ($point->day_number ?? 0))
                                ->first();
                            $highlightedItemKey = $resolveHighlightedPopupItemKey(
                                $dayItems,
                                $highlightedDayPoint,
                            );
                            $resolveHotelRegion = static function ($hotel): string {
                                if (!$hotel) {
                                    return '-';
                                }
                                $region = trim((string) ($hotel->region ?? ''));
                                if ($region !== '') {
                                    return $region;
                                }
                                $region = trim((string) ($hotel->city ?? ''));
                                if ($region !== '') {
                                    return $region;
                                }
                                $region = trim((string) ($hotel->province ?? ''));
                                if ($region !== '') {
                                    return $region;
                                }
                                $region = trim(
                                    (string) ($hotel->destination?->province ?? ($hotel->destination?->name ?? '')),
                                );
                                return $region !== '' ? $region : '-';
                            };
                            $resolveStartLabelFromPoint = static function ($point) use (
                                $resolveHotelRegion,
                            ): string {
                                if (!$point) {
                                    return '';
                                }
                                $startType = strtolower(trim((string) ($point->start_point_type ?? '')));
                                if ($startType === 'airport') {
                                    return trim((string) ($point->startAirport?->name ?? ''));
                                }
                                if ($startType !== 'hotel' && $startType !== 'previous_day_end') {
                                    return '';
                                }
                                $isSelfBooked =
                                    strtolower(trim((string) ($point->start_hotel_booking_mode ?? ''))) === 'self';
                                if ($isSelfBooked) {
                                    return trim((string) ($point->start_hotel_area ?? ''));
                                }
                                return trim((string) ($point->startHotel?->name ?? ''));
                            };
                            $resolveEndLabelFromPoint = static function ($point) use ($resolveHotelRegion): string {
                                if (!$point) {
                                    return '';
                                }
                                $endType = strtolower(trim((string) ($point->end_point_type ?? '')));
                                if ($endType === 'airport') {
                                    return trim((string) ($point->endAirport?->name ?? ''));
                                }
                                if ($endType !== 'hotel') {
                                    return '';
                                }
                                $isSelfBooked =
                                    strtolower(trim((string) ($point->end_hotel_booking_mode ?? ''))) === 'self';
                                if ($isSelfBooked) {
                                    return trim((string) ($point->end_hotel_area ?? ''));
                                }
                                return trim((string) ($point->endHotel?->name ?? ''));
                            };
                            $sortedDayPoints = $itinerary->dayPoints
                                ->sortBy(fn($point) => (int) ($point->day_number ?? 0))
                                ->values();
                            $firstDayPoint = $sortedDayPoints->first();
                            $lastDayPoint = $sortedDayPoints->last();
                            $startPointLabel = $resolveStartLabelFromPoint($firstDayPoint);
                            $endPointLabel = $resolveEndLabelFromPoint($lastDayPoint);
                            if ($startPointLabel === '') {
                                $startPointLabel =
                                    (string) ($sortedDayPoints
                                        ->map(fn($point) => $resolveStartLabelFromPoint($point))
                                        ->first(fn($label) => trim((string) $label) !== '') ?? '');
                            }
                            if ($startPointLabel === '') {
                                $startPointLabel =
                                    (string) ($sortedDayPoints
                                        ->map(fn($point) => $resolveEndLabelFromPoint($point))
                                        ->first(fn($label) => trim((string) $label) !== '') ?? '');
                            }
                            if ($endPointLabel === '') {
                                $endPointLabel =
                                    (string) ($sortedDayPoints
                                        ->reverse()
                                        ->map(fn($point) => $resolveEndLabelFromPoint($point))
                                        ->first(fn($label) => trim((string) $label) !== '') ?? '');
                            }
                            if ($endPointLabel === '') {
                                $endPointLabel =
                                    (string) ($sortedDayPoints
                                        ->reverse()
                                        ->map(fn($point) => $resolveStartLabelFromPoint($point))
                                        ->first(fn($label) => trim((string) $label) !== '') ?? '');
                            }
                            $startPointLabel = $startPointLabel !== '' ? $startPointLabel : '-';
                            $endPointLabel = $endPointLabel !== '' ? $endPointLabel : '-';
                            $titleWithHighlight =
                                trim((string) ($itinerary->title ?? '')) .
                                ' | Start: ' .
                                $startPointLabel .
                                ' - End: ' .
                                $endPointLabel;
                            $capacityByDay = $itinerary->itineraryTransportUnits
                                ->groupBy(fn($row) => max(1, (int) ($row->day_number ?? 1)))
                                ->map(
                                    fn($rows) => (int) $rows->sum(
                                        fn($row) => max(0, (int) ($row->transportUnit?->seat_capacity ?? 0)),
                                    ),
                                );
                            $totalCapacity = (int) ($capacityByDay->max() ?? 0);
                            $showTransportDayPrefix = (int) ($itinerary->duration_days ?? 1) > 1;
                            $transportItems = $itinerary->itineraryTransportUnits
                                ->map(function ($row) {
                                    $dayNumber = max(1, (int) ($row->day_number ?? 1));
                                    $unitName = trim((string) ($row->transportUnit?->name ?? ''));
                                    $brandName = trim((string) ($row->transportUnit?->brand_model ?? ''));
                                    if ($unitName === '' && $brandName === '') {
                                        return null;
                                    }
                                    $transportName = trim($brandName . ' ' . $unitName);
                                    if ($transportName === '') {
                                        $transportName = '-';
                                    }
                                    return [
                                        'day' => $dayNumber,
                                        'transport_name' => $transportName,
                                    ];
                                })
                                ->filter(fn($row) => is_array($row) && filled($row['transport_name'] ?? null))
                                ->unique(
                                    fn($row) => strtolower((string) ($row['transport_name'] ?? '')) .
                                        '|' .
                                        (int) ($row['day'] ?? 0),
                                )
                                ->sortBy(
                                    fn($row) => [
                                        (int) ($row['day'] ?? 0),
                                        strtolower((string) ($row['transport_name'] ?? '')),
                                    ],
                                )
                                ->values();
                        @endphp
                        <div class="app-card p-4">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $titleWithHighlight }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('by :name', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), ui_phrase('system')) ?: '-']) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('day count', ['count' => $itinerary->duration_days]) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Quotation') }}:
                                {{ (int) ($itinerary->quotations_count ?? ($itinerary->quotations?->count() ?? 0)) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Capacity') }}:
                                {{ $totalCapacity }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $itinerary->destination?->name ?? ($itinerary->destination ?? '-') }}</p>
                            <div class="mt-3">
                                <div class="relative inline-block text-left itinerary-items-popover" data-popover-root>
                                    <button type="button" class="btn-outline-sm" data-popover-trigger
                                        aria-expanded="false" aria-haspopup="true">
                                        Desc
                                    </button>
                                    <div class="hidden w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                        data-popover-panel role="dialog"
                                        aria-label="{{ ui_phrase('Itinerary item list') }}"
                                        style="position: fixed; z-index: 9999;">
                                        <span
                                            class="pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700"
                                            data-popover-arrow aria-hidden="true"></span>
                                        <p
                                            class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ ui_phrase('Item List') }}</p>
                                        @if ($transportItems->isNotEmpty())
                                            <div
                                                class="mb-2 space-y-1 border-b border-gray-200 pb-2 dark:border-gray-700">
                                                @foreach ($transportItems as $transportLabel)
                                                    <div
                                                        class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                                        <i class="fa-solid fa-van-shuttle w-3 text-gray-500 dark:text-gray-400"
                                                            aria-hidden="true"></i>
                                                        <span>{{ $showTransportDayPrefix ? 'Day ' . ((int) ($transportLabel['day'] ?? 1)) . ' | ' : '' }}{{ $transportLabel['transport_name'] ?? '-' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if ($flatItemNames->isNotEmpty())
                                            <div
                                                class="max-h-64 space-y-2 overflow-auto overscroll-contain pr-1 text-xs text-gray-700 dark:text-gray-200">
                                                @if ($isMultiDayPopover)
                                                    @foreach ($itemsByDay as $day => $dayItemNames)
                                                        <div>
                                                            <p
                                                                class="mb-1 font-semibold text-gray-500 dark:text-gray-400">
                                                                {{ ui_phrase('Day') }} {{ $day }}</p>
                                                            <ul class="space-y-1">
                                                                @foreach ($dayItemNames as $itemRow)
                                                                    @php
                                                                        $itemName = (string) ($itemRow['label'] ?? '-');
                                                                        $mealLabel = trim((string) ($itemRow['meal_label'] ?? ''));
                                                                        $itemKey = trim((string) ($itemRow['item_key'] ?? ''));
                                                                    @endphp
                                                                    <li class="flex items-center gap-2">
                                                                        <i class="fa-solid fa-caret-right w-3 text-[10px] text-gray-500 dark:text-gray-400"
                                                                            aria-hidden="true"></i>
                                                                        <span>{{ $itemName }}</span>
                                                                        @if ($mealLabel !== '')
                                                                            <span
                                                                                class="inline-flex items-center rounded border border-sky-300 bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 dark:border-sky-700 dark:bg-sky-900/40 dark:text-sky-200">{{ ui_phrase($mealLabel) }}</span>
                                                                        @endif
                                                                        @if ($itemKey !== '' && $itemKey === $highlightedItemKey)
                                                                            <span
                                                                                class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">{{ ui_phrase('Highlighted') }}</span>
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <ul class="space-y-1">
                                                        @foreach ($flatItemNames as $itemRow)
                                                            @php
                                                                $itemName = (string) ($itemRow['label'] ?? '-');
                                                                $mealLabel = trim((string) ($itemRow['meal_label'] ?? ''));
                                                                $itemKey = trim((string) ($itemRow['item_key'] ?? ''));
                                                            @endphp
                                                            <li class="flex items-center gap-2">
                                                                <i class="fa-solid fa-caret-right w-3 text-[10px] text-gray-500 dark:text-gray-400"
                                                                    aria-hidden="true"></i>
                                                                <span>{{ $itemName }}</span>
                                                                @if ($mealLabel !== '')
                                                                    <span
                                                                        class="inline-flex items-center rounded border border-sky-300 bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 dark:border-sky-700 dark:bg-sky-900/40 dark:text-sky-200">{{ ui_phrase($mealLabel) }}</span>
                                                                @endif
                                                                @if ($itemKey !== '' && $itemKey === $highlightedItemKey)
                                                                    <span
                                                                        class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">{{ ui_phrase('Highlighted') }}</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ ui_phrase('No items available.') }}</p>
                                        @endif
                                        @if ($canGenerateQuotationFromItinerary($itinerary))
                                            <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                                <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}"
                                                    class="btn-primary-sm w-full justify-center">
                                                    {{ ui_phrase('Generate Quotation') }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <x-slot:trigger>
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </x-slot:trigger>
                                    <a href="{{ route('itineraries.show', $itinerary) }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"><i
                                            class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i><span>{{ ui_phrase('View') }}</span></a>
                                    @if (!$itinerary->trashed())
                                        <x-ui.confirm-action :action="route('itineraries.duplicate', $itinerary)" method="POST" :modal-name="'itinerary-index-duplicate-mobile-' . $itinerary->id"
                                            :title="ui_phrase('Duplicate Itinerary')" :message="ui_phrase('confirm duplicate')" :impact-title="__('confirm.what_will_happen')" :impact-items="[
                                                __('confirm.duplicate_itinerary_info_1'),
                                                __('confirm.duplicate_itinerary_info_2'),
                                                __('confirm.duplicate_itinerary_info_3'),
                                            ]"
                                            :notice-message="__('confirm.notification_after_action')" notice-tone="info" :confirm-label="ui_phrase('Duplicate')" :trigger-label="ui_phrase('Duplicate')"
                                            trigger-icon="fa-solid fa-copy w-4 text-gray-500 dark:text-gray-400"
                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                                            confirm-class="btn-primary-sm" />
                                    @endif
                                    @can('update', $itinerary)
                                        <a href="{{ route('itineraries.edit', $itinerary) }}"
                                            class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"><i
                                                class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i><span>{{ ui_phrase('Edit') }}</span></a>
                                    @endcan
                                    @if (auth()->user()
                                            ?->hasAnyRole(['Super Admin', 'Super User', 'Administrator']))
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action :action="route('itineraries.destroy', $itinerary)" method="DELETE" :modal-name="'itinerary-index-delete-mobile-' . $itinerary->id"
                                            :title="ui_phrase('Delete Itinerary')" :message="ui_phrase('Are you sure you want to delete this itinerary?')" :impact-title="__('confirm.important_warning')" :impact-items="[
                                                __('confirm.delete_itinerary_info_1'),
                                                __('confirm.delete_itinerary_info_2'),
                                                __('confirm.delete_itinerary_info_3'),
                                            ]"
                                            :notice-message="__('confirm.notification_after_action')" notice-tone="danger" :confirm-label="ui_phrase('Delete')"
                                            :trigger-label="ui_phrase('Delete')" trigger-icon="fa-solid fa-trash w-4"
                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                            confirm-class="btn-danger-sm" />
                                    @endif
                                </x-ui.table-action-dropdown>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Itineraries')]) }}</div>
                    @endforelse
                </div>
                <div class="mt-3">{{ $itineraries->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const state = window.__itineraryPopoverState || {
                popovers: [],
                observer: null,
                boundGlobals: false,
            };
            window.__itineraryPopoverState = state;

            const positionPanel = function(trigger, panel) {
                const rect = trigger.getBoundingClientRect();
                const arrow = panel.querySelector('[data-popover-arrow]');
                const gap = 8;
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                const preferredWidth = viewportWidth >= 768 ? 288 : Math.min(288, viewportWidth - (gap * 2));
                panel.style.width = preferredWidth + 'px';
                const panelWidth = panel.offsetWidth || preferredWidth;
                const panelHeight = panel.offsetHeight || 260;

                // Default placement: on the right side of the trigger button.
                let placement = 'right';
                let left = rect.right + gap;
                let top = rect.top + (rect.height / 2);

                // If right side overflows, fallback:
                // - desktop/tablet => left side of trigger
                // - mobile => below trigger
                if (left + panelWidth > viewportWidth - gap) {
                    if (viewportWidth >= 768) {
                        placement = 'left';
                        left = rect.left - panelWidth - gap;
                    } else {
                        placement = 'bottom';
                        left = rect.right - panelWidth;
                        top = rect.bottom + gap;
                    }
                }

                if (placement === 'right' || placement === 'left') {
                    const minTopCenter = gap + (panelHeight / 2);
                    const maxTopCenter = viewportHeight - gap - (panelHeight / 2);
                    top = Math.min(Math.max(top, minTopCenter), maxTopCenter);
                    panel.style.transform = 'translateY(-50%)';
                } else {
                    if (top + panelHeight > viewportHeight - gap) {
                        top = rect.top - panelHeight - gap;
                    }
                    panel.style.transform = 'none';
                }

                left = Math.max(gap, Math.min(left, viewportWidth - panelWidth - gap));
                top = Math.max(gap, Math.min(top, viewportHeight - gap));

                panel.style.left = left + 'px';
                panel.style.top = top + 'px';

                if (arrow) {
                    const triggerCenterY = rect.top + (rect.height / 2);
                    const panelTop = placement === 'right' || placement === 'left' ?
                        (top - (panelHeight / 2)) :
                        top;
                    const minArrowTop = 14;
                    const maxArrowTop = Math.max(minArrowTop, panelHeight - 14);
                    const alignedArrowTop = Math.min(
                        maxArrowTop,
                        Math.max(minArrowTop, (triggerCenterY - panelTop))
                    );

                    if (placement === 'right') {
                        arrow.className =
                            'pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700';
                        arrow.style.left = '-10px';
                        arrow.style.right = 'auto';
                        arrow.style.top = alignedArrowTop + 'px';
                        arrow.style.transform = 'translateY(-50%)';
                    } else if (placement === 'left') {
                        arrow.className =
                            'pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-l-[10px] border-l-gray-700 dark:border-l-gray-700';
                        arrow.style.left = 'auto';
                        arrow.style.right = '-10px';
                        arrow.style.top = alignedArrowTop + 'px';
                        arrow.style.transform = 'translateY(-50%)';
                    } else {
                        arrow.className =
                            'pointer-events-none absolute h-0 w-0 border-x-[8px] border-x-transparent border-b-[10px] border-b-gray-700 dark:border-b-gray-700';
                        const triggerCenterX = rect.left + (rect.width / 2);
                        const alignedArrowLeft = Math.min(
                            panelWidth - 14,
                            Math.max(14, triggerCenterX - left)
                        );
                        arrow.style.left = alignedArrowLeft + 'px';
                        arrow.style.right = 'auto';
                        arrow.style.top = '-12px';
                        arrow.style.transform = 'translateX(-50%)';
                    }
                }
            };

            const closeAll = function() {
                state.popovers.forEach(function(entry) {
                    const panel = entry.panel;
                    const trigger = entry.trigger;
                    if (panel) {
                        panel.classList.add('hidden');
                    }
                    if (trigger) {
                        trigger.setAttribute('aria-expanded', 'false');
                    }
                });
            };

            const openPanel = function(trigger, panel) {
                panel.style.visibility = 'hidden';
                panel.classList.remove('hidden');
                positionPanel(trigger, panel);
                panel.style.visibility = '';
            };

            const cleanupDetachedPopovers = function() {
                state.popovers = state.popovers.filter(function(entry) {
                    const isTriggerAlive = document.body.contains(entry.trigger);
                    const isRootAlive = document.body.contains(entry.root);
                    if (isTriggerAlive && isRootAlive) {
                        return true;
                    }
                    if (entry.panel && entry.panel.parentNode === document.body) {
                        entry.panel.remove();
                    }
                    return false;
                });
            };

            const bindRoots = function(scope) {
                const searchRoot = scope instanceof Element || scope instanceof Document ? scope : document;
                const roots = Array.from(searchRoot.querySelectorAll('[data-popover-root]'));
                roots.forEach(function(root) {
                    if (root.dataset.popoverBound === '1') {
                        return;
                    }
                    root.dataset.popoverBound = '1';

                    const trigger = root.querySelector('[data-popover-trigger]');
                    const panel = root.querySelector('[data-popover-panel]');
                    if (!trigger || !panel) {
                        root.dataset.popoverBound = '0';
                        return;
                    }
                    // Move panel to body to avoid fixed-position offset caused by transformed ancestors.
                    document.body.appendChild(panel);
                    state.popovers.push({
                        root: root,
                        trigger: trigger,
                        panel: panel
                    });

                    trigger.addEventListener('click', function(event) {
                        event.stopPropagation();
                        const isHidden = panel.classList.contains('hidden');
                        closeAll();
                        if (isHidden) {
                            openPanel(trigger, panel);
                            trigger.setAttribute('aria-expanded', 'true');
                        }
                    });
                });
            };

            if (!state.boundGlobals) {
                state.boundGlobals = true;

                document.addEventListener('click', function(event) {
                    cleanupDetachedPopovers();
                    const clickedInsidePopover = state.popovers.some(function(entry) {
                        return entry.root.contains(event.target) || entry.panel.contains(event
                            .target);
                    });
                    if (!clickedInsidePopover) {
                        closeAll();
                    }
                });

                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        closeAll();
                    }
                });

                window.addEventListener('resize', closeAll);
                window.addEventListener('scroll', function(event) {
                    const scrollTarget = event.target;
                    const isScrollingInsidePopover = scrollTarget instanceof Element && state.popovers.some(
                        function(entry) {
                            return entry.panel && entry.panel.contains(scrollTarget);
                        });
                    if (isScrollingInsidePopover) {
                        return;
                    }
                    closeAll();
                }, true);
            }

            bindRoots(document);
            cleanupDetachedPopovers();

            if (!state.observer) {
                state.observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (!(node instanceof Element)) {
                                return;
                            }
                            if (node.matches('[data-popover-root]')) {
                                bindRoots(node.parentElement || document);
                            } else if (node.querySelector('[data-popover-root]')) {
                                bindRoots(node);
                            }
                        });
                    });
                    cleanupDetachedPopovers();
                });
                state.observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            const filterForm = document.querySelector('[data-service-filter-form]');
            if (filterForm) {
                const minFilterLength = 3;
                const textFilterInputs = Array.from(filterForm.querySelectorAll(
                    'input[type="text"], input[type="search"]'));
                const titleInputVisible = filterForm.querySelector('[data-filter-title-visible]');
                const titleInputHidden = filterForm.querySelector('[data-filter-title-hidden]');
                let lastSubmittedTitleValue = String(titleInputHidden?.value || '').trim();

                const isTextFilterValueValid = function(value) {
                    const normalized = String(value || '').trim();
                    return normalized === '' || normalized.length >= minFilterLength;
                };

                const isAllTextFiltersValid = function() {
                    return textFilterInputs.every(function(input) {
                        return isTextFilterValueValid(input.value);
                    });
                };

                const syncInputValidityMessage = function(input) {
                    if (!input) return;
                    if (isTextFilterValueValid(input.value)) {
                        input.setCustomValidity('');
                        return;
                    }
                    input.setCustomValidity(
                        '{{ ui_phrase('Please enter at least :count characters before filtering.', ['count' => 3]) }}'
                        );
                };

                textFilterInputs.forEach(function(input) {
                    input.addEventListener('input', function() {
                        syncInputValidityMessage(input);
                        if (input !== titleInputVisible) {
                            return;
                        }
                        if (!titleInputHidden) {
                            return;
                        }
                        const currentValue = String(titleInputVisible.value || '').trim();
                        if (currentValue !== '' && currentValue.length < minFilterLength) {
                            return;
                        }
                        titleInputHidden.value = currentValue;
                        if (currentValue === lastSubmittedTitleValue) {
                            return;
                        }
                        lastSubmittedTitleValue = currentValue;
                        filterForm.requestSubmit();
                    });

                    input.addEventListener('blur', function() {
                        syncInputValidityMessage(input);
                        if (!isAllTextFiltersValid()) {
                            return;
                        }
                        if (input === titleInputVisible) {
                            const currentValue = String(titleInputVisible?.value || '').trim();
                            if (currentValue !== '' && currentValue.length < minFilterLength) {
                                return;
                            }
                            if (titleInputHidden) {
                                titleInputHidden.value = currentValue;
                            }
                            if (currentValue === lastSubmittedTitleValue) {
                                return;
                            }
                            lastSubmittedTitleValue = currentValue;
                        }
                        filterForm.requestSubmit();
                    });

                    input.addEventListener('keydown', function(event) {
                        if (event.key !== 'Enter' && event.key !== 'Tab') {
                            return;
                        }
                        syncInputValidityMessage(input);
                        if (!isAllTextFiltersValid()) {
                            event.preventDefault();
                            filterForm.reportValidity();
                            return;
                        }
                        if (input === titleInputVisible) {
                            const currentValue = String(titleInputVisible?.value || '').trim();
                            if (currentValue !== '' && currentValue.length < minFilterLength) {
                                event.preventDefault();
                                filterForm.reportValidity();
                                return;
                            }
                            if (titleInputHidden) {
                                titleInputHidden.value = currentValue;
                            }
                            if (currentValue === lastSubmittedTitleValue) {
                                return;
                            }
                            lastSubmittedTitleValue = currentValue;
                        }
                        filterForm.requestSubmit();
                    });
                });

                filterForm.addEventListener('submit', function(event) {
                    textFilterInputs.forEach(syncInputValidityMessage);
                    if (isAllTextFiltersValid()) {
                        if (titleInputVisible && titleInputHidden) {
                            const normalizedTitle = String(titleInputVisible.value || '').trim();
                            titleInputHidden.value = normalizedTitle;
                            lastSubmittedTitleValue = normalizedTitle;
                        }
                        return;
                    }
                    event.preventDefault();
                    filterForm.reportValidity();
                });

                // Block global service-filter auto-trigger on select/number change when title is non-empty but < min length.
                filterForm.addEventListener('change', function(event) {
                    if (!titleInputVisible || !titleInputHidden) {
                        return;
                    }
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement ||
                            target instanceof HTMLTextAreaElement)) {
                        return;
                    }
                    if (!target.matches('[data-service-filter-input]')) {
                        return;
                    }
                    const titleValue = String(titleInputVisible.value || '').trim();
                    if (titleValue !== '' && titleValue.length < minFilterLength) {
                        syncInputValidityMessage(titleInputVisible);
                        event.stopImmediatePropagation();
                        event.preventDefault();
                        filterForm.reportValidity();
                    }
                }, true);
            }
        });
    </script>
@endpush
