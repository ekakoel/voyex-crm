@extends('layouts.master')
@section('page_title', ui_phrase('Itineraries'))
@section('page_subtitle', ui_phrase('Manage itinerary records.'))
@section('page_actions')
    <a href="{{ route('itineraries.create') }}" class="btn-primary">{{ ui_phrase('Create Itinerary') }}</a>
@endsection
@section('content')
    <div class="space-y-5 module-page module-page--itineraries" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-9-3 min-w-0">
            <aside class="module-grid-side min-w-0 space-y-3">
                @include('components.module-index-sidebar-info')
                <section class="app-card p-4">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Itinerary Logs') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Latest itinerary activity updates.') }}</p>
                    </div>

                    <div class="space-y-2">
                        @forelse (($itineraryLogs ?? collect()) as $log)
                            @php
                                $logUser = trim((string) ($log->user?->displayNameFor(auth()->user()) ?? $log->user?->name ?? ui_phrase('System')));
                                $logAction = trim((string) ($log->action ?? 'updated'));
                                $logActionLabel = \Illuminate\Support\Str::headline(str_replace('_', ' ', $logAction));
                                $logSubjectId = (int) ($log->subject_id ?? 0);
                                $logDateTime = optional($log->created_at)->format('d M Y H:i') ?? '-';
                            @endphp
                            <div class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                <span class="min-w-0 truncate">{{ $logUser }}, {{ $logActionLabel }} itinerary id {{ $logSubjectId > 0 ? $logSubjectId : '-' }}</span>
                                <span class="min-w-6 flex-1 border-b border-dotted border-gray-300 dark:border-gray-600"></span>
                                <span class="shrink-0 text-gray-500 dark:text-gray-400">({{ $logDateTime }})</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('No itinerary logs yet.') }}</p>
                        @endforelse
                    </div>

                </section>
            </aside>
            <div class="module-grid-main min-w-0">
                <div class="app-card p-5">
                    <div class="grid gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Filters') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('Refine your list quickly.') }}</p>
                        </div>
                        <form method="GET" action="{{ route('itineraries.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                            <input type="text" value="{{ request('title') }}" placeholder="{{ ui_phrase('Title') }}" class="app-input sm:col-span-2" data-filter-title-visible data-filter-min-text="3">
                            <input type="hidden" name="title" value="{{ request('title') }}" data-service-filter-input data-filter-title-hidden>
                            <select name="destination_id" class="app-input sm:col-span-2" data-service-filter-input>
                                <option value="">{{ ui_phrase('All destinations') }}</option>
                                @foreach ($destinations as $destination)
                                    <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>{{ $destination->name }}</option>
                                @endforeach
                            </select>
                            <input name="duration" type="number" min="1" value="{{ request('duration') }}" placeholder="{{ ui_phrase('Duration (days)') }}" class="app-input" data-service-filter-input>
                            <select name="per_page" class="app-input" data-service-filter-input>
                                @foreach ([10,25,50,100] as $size)
                                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                                @endforeach
                            </select>
                            <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                                <a href="{{ route('itineraries.index', ['reset' => 1]) }}" class="btn-ghost">{{ ui_phrase('Reset') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
        <div data-service-filter-results>
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Title') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Duration') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Quotation') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Capacity') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Item List') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($itineraries as $index => $itinerary)
                        @php
                            $formatItemWithVendor = static function (?string $itemName, ?string $vendorName): ?string {
                                $name = trim((string) $itemName);
                                if ($name === '') {
                                    return null;
                                }
                                $vendor = trim((string) $vendorName);
                                return $vendor !== '' ? ($name . ' | ' . $vendor) : $name;
                            };

                            $dayItems = collect()
                                ->merge($itinerary->touristAttractions->map(fn ($item) => [
                                    'day' => max(1, (int) ($item->pivot->day_number ?? 1)),
                                    'start_time' => trim((string) ($item->pivot->start_time ?? '')),
                                    'sort_order' => (int) ($item->pivot->visit_order ?? 0),
                                    'label' => trim((string) ($item->name ?? '')),
                                    'item_name' => trim((string) ($item->name ?? '')),
                                    'vendor_name' => '',
                                ]))
                                ->merge($itinerary->itineraryActivities->map(fn ($item) => [
                                    'day' => max(1, (int) ($item->day_number ?? 1)),
                                    'start_time' => trim((string) ($item->start_time ?? '')),
                                    'sort_order' => (int) ($item->visit_order ?? 0),
                                    'label' => (string) $formatItemWithVendor($item->activity?->name, $item->activity?->vendor?->name),
                                    'item_name' => trim((string) ($item->activity?->name ?? '')),
                                    'vendor_name' => trim((string) ($item->activity?->vendor?->name ?? '')),
                                ]))
                                ->merge($itinerary->itineraryIslandTransfers->map(fn ($item) => [
                                    'day' => max(1, (int) ($item->day_number ?? 1)),
                                    'start_time' => trim((string) ($item->start_time ?? '')),
                                    'sort_order' => (int) ($item->visit_order ?? 0),
                                    'label' => (string) $formatItemWithVendor($item->islandTransfer?->name, $item->islandTransfer?->vendor?->name),
                                    'item_name' => trim((string) ($item->islandTransfer?->name ?? '')),
                                    'vendor_name' => trim((string) ($item->islandTransfer?->vendor?->name ?? '')),
                                ]))
                                ->merge($itinerary->itineraryFoodBeverages->map(fn ($item) => [
                                    'day' => max(1, (int) ($item->day_number ?? 1)),
                                    'start_time' => trim((string) ($item->start_time ?? '')),
                                    'sort_order' => (int) ($item->visit_order ?? 0),
                                    'label' => (string) $formatItemWithVendor($item->foodBeverage?->name, $item->foodBeverage?->vendor?->name),
                                    'item_name' => trim((string) ($item->foodBeverage?->name ?? '')),
                                    'vendor_name' => trim((string) ($item->foodBeverage?->vendor?->name ?? '')),
                                ]))
                                ->filter(fn ($row) => filled($row['label'] ?? null))
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

                                    return ((int) ($left['sort_order'] ?? 0)) <=> ((int) ($right['sort_order'] ?? 0));
                                })
                                ->values();

                            $itemsByDay = $dayItems
                                ->groupBy('day')
                                ->map(fn ($items) => $items->pluck('label')->map(fn ($name) => trim((string) $name))->unique()->values())
                                ->sortKeys();

                            $isMultiDayPopover = (int) ($itinerary->duration_days ?? 1) > 1;
                            $flatItemNames = $itemsByDay->flatten(1)->unique()->values();
                            $highlightedDayPoint = $itinerary->dayPoints
                                ->filter(function ($point) {
                                    return filled($point->main_experience_type)
                                        || (int) ($point->main_tourist_attraction_id ?? 0) > 0
                                        || (int) ($point->main_activity_id ?? 0) > 0
                                        || (int) ($point->main_food_beverage_id ?? 0) > 0;
                                })
                                ->sortBy(fn ($point) => (int) ($point->day_number ?? 0))
                                ->first();
                            $highlightedItemName = '';
                            $highlightedItemVendorName = '';
                            if ($highlightedDayPoint) {
                                $mainType = strtolower(trim((string) ($highlightedDayPoint->main_experience_type ?? '')));
                                $mainAttraction = $highlightedDayPoint->mainTouristAttraction;
                                $mainActivity = $highlightedDayPoint->mainActivity;
                                $mainFoodBeverage = $highlightedDayPoint->mainFoodBeverage;

                                if (in_array($mainType, ['attraction', 'tourist_attraction'], true)) {
                                    $highlightedItemName = trim((string) ($mainAttraction?->name ?? ''));
                                } elseif (in_array($mainType, ['activity'], true)) {
                                    $highlightedItemName = trim((string) ($mainActivity?->name ?? ''));
                                    $highlightedItemVendorName = trim((string) ($mainActivity?->vendor?->name ?? ''));
                                } elseif (in_array($mainType, ['fnb', 'food_beverage'], true)) {
                                    $highlightedItemName = trim((string) ($mainFoodBeverage?->name ?? ''));
                                    $highlightedItemVendorName = trim((string) ($mainFoodBeverage?->vendor?->name ?? ''));
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
                                    }
                                }
                            }
                            if ($highlightedItemName === '') {
                                $fallbackHighlightedItem = $dayItems->first();
                                $highlightedItemName = trim((string) ($fallbackHighlightedItem['item_name'] ?? $flatItemNames->first() ?? ''));
                                $highlightedItemVendorName = trim((string) ($fallbackHighlightedItem['vendor_name'] ?? ''));
                            }
                            $highlightedItemLabel = $highlightedItemName;
                            if ($highlightedItemVendorName !== '') {
                                $highlightedItemLabel .= ' | ' . $highlightedItemVendorName;
                            }
                            $resolveHotelRegion = static function ($hotel): string {
                                if (! $hotel) {
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
                                $region = trim((string) ($hotel->destination?->province ?? $hotel->destination?->name ?? ''));
                                return $region !== '' ? $region : '-';
                            };
                            $resolveStartLabelFromPoint = static function ($point) use ($resolveHotelRegion): string {
                                if (! $point) {
                                    return '';
                                }
                                $startType = strtolower(trim((string) ($point->start_point_type ?? '')));
                                if ($startType === 'airport') {
                                    return trim((string) ($point->startAirport?->name ?? ''));
                                }
                                if ($startType !== 'hotel' && $startType !== 'previous_day_end') {
                                    return '';
                                }
                                $isSelfBooked = strtolower(trim((string) ($point->start_hotel_booking_mode ?? ''))) === 'self';
                                if ($isSelfBooked) {
                                    return trim((string) ($point->start_hotel_area ?? ''));
                                }
                                return trim((string) ($point->startHotel?->name ?? ''));
                            };
                            $resolveEndLabelFromPoint = static function ($point) use ($resolveHotelRegion): string {
                                if (! $point) {
                                    return '';
                                }
                                $endType = strtolower(trim((string) ($point->end_point_type ?? '')));
                                if ($endType === 'airport') {
                                    return trim((string) ($point->endAirport?->name ?? ''));
                                }
                                if ($endType !== 'hotel') {
                                    return '';
                                }
                                $isSelfBooked = strtolower(trim((string) ($point->end_hotel_booking_mode ?? ''))) === 'self';
                                if ($isSelfBooked) {
                                    return trim((string) ($point->end_hotel_area ?? ''));
                                }
                                return trim((string) ($point->endHotel?->name ?? ''));
                            };
                            $sortedDayPoints = $itinerary->dayPoints
                                ->sortBy(fn ($point) => (int) ($point->day_number ?? 0))
                                ->values();
                            $firstDayPoint = $sortedDayPoints->first();
                            $lastDayPoint = $sortedDayPoints->last();
                            $startPointLabel = $resolveStartLabelFromPoint($firstDayPoint);
                            $endPointLabel = $resolveEndLabelFromPoint($lastDayPoint);
                            if ($startPointLabel === '') {
                                $startPointLabel = (string) ($sortedDayPoints
                                    ->map(fn ($point) => $resolveStartLabelFromPoint($point))
                                    ->first(fn ($label) => trim((string) $label) !== '') ?? '');
                            }
                            if ($startPointLabel === '') {
                                $startPointLabel = (string) ($sortedDayPoints
                                    ->map(fn ($point) => $resolveEndLabelFromPoint($point))
                                    ->first(fn ($label) => trim((string) $label) !== '') ?? '');
                            }
                            if ($endPointLabel === '') {
                                $endPointLabel = (string) ($sortedDayPoints
                                    ->reverse()
                                    ->map(fn ($point) => $resolveEndLabelFromPoint($point))
                                    ->first(fn ($label) => trim((string) $label) !== '') ?? '');
                            }
                            if ($endPointLabel === '') {
                                $endPointLabel = (string) ($sortedDayPoints
                                    ->reverse()
                                    ->map(fn ($point) => $resolveStartLabelFromPoint($point))
                                    ->first(fn ($label) => trim((string) $label) !== '') ?? '');
                            }
                            $startPointLabel = $startPointLabel !== '' ? $startPointLabel : '-';
                            $endPointLabel = $endPointLabel !== '' ? $endPointLabel : '-';
                            $titleWithHighlight = trim((string) ($itinerary->title ?? ''))
                                . ' | Start: ' . $startPointLabel
                                . ' - End: ' . $endPointLabel;
                            $capacityByDay = $itinerary->itineraryTransportUnits
                                ->groupBy(fn ($row) => max(1, (int) ($row->day_number ?? 1)))
                                ->map(fn ($rows) => (int) $rows->sum(fn ($row) => max(0, (int) ($row->transportUnit?->seat_capacity ?? 0))));
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
                                ->filter(fn ($row) => is_array($row) && filled($row['transport_name'] ?? null))
                                ->unique(fn ($row) => strtolower((string) ($row['transport_name'] ?? '')) . '|' . (int) ($row['day'] ?? 0))
                                ->sortBy(fn ($row) => [(int) ($row['day'] ?? 0), strtolower((string) ($row['transport_name'] ?? ''))])
                                ->values();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                <div class="font-medium">{{ $titleWithHighlight }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('by :name', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), ui_phrase('system')) ?: '-']) }}</div>
                                {{-- <div class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('by -', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), ui_phrase('system')) ?: '-']) }}</div> --}}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $itinerary->duration_days }}D{{ $itinerary->duration_nights > 0 ? "/".$itinerary->duration_nights."N":""; }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $itinerary->destination?->name ?? $itinerary->destination ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @php
                                    $quotationCount = (int) ($itinerary->quotations?->count() ?? 0);
                                @endphp
                                @if ($quotationCount > 0)
                                    <div class="relative inline-block text-left itinerary-items-popover" data-popover-root>
                                        <button type="button" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200" data-popover-trigger aria-expanded="false" aria-haspopup="true">
                                            {{ $quotationCount }}
                                        </button>
                                        <div class="hidden w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900" data-popover-panel role="dialog" aria-label="{{ ui_phrase('Quotation list') }}" style="position: fixed; z-index: 9999;">
                                            <span class="pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700" data-popover-arrow aria-hidden="true"></span>
                                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Quotation') }}</p>
                                            <ul class="space-y-1 text-xs text-gray-700 dark:text-gray-200">
                                                @foreach ($itinerary->quotations as $quotation)
                                                    @php
                                                        $orderNumber = trim((string) ($quotation->order_number ?? ''));
                                                        $fallbackNumber = trim((string) ($quotation->quotation_number ?? ''));
                                                        $displayNumber = $orderNumber !== '' ? $orderNumber : ($fallbackNumber !== '' ? $fallbackNumber : ('#' . (int) $quotation->id));
                                                    @endphp
                                                    <li>
                                                        <a href="{{ route('quotations.show', $quotation) }}" class="hover:underline">
                                                            {{ $displayNumber }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                                        0
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
                                    {{ $totalCapacity }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div class="relative inline-block text-left itinerary-items-popover" data-popover-root>
                                    <button type="button" class="btn-outline-sm" data-popover-trigger aria-expanded="false" aria-haspopup="true">
                                        Desc
                                    </button>
                                    <div class="hidden w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900" data-popover-panel role="dialog" aria-label="{{ ui_phrase('Itinerary item list') }}" style="position: fixed; z-index: 9999;">
                                        <span class="pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700" data-popover-arrow aria-hidden="true"></span>
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Item List') }}</p>
                                        @if ($transportItems->isNotEmpty())
                                            <div class="mb-2 space-y-1 border-b border-gray-200 pb-2 dark:border-gray-700">
                                                @foreach ($transportItems as $transportLabel)
                                                    <div class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                                        <i class="fa-solid fa-van-shuttle w-3 text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                                                        <span>{{ $showTransportDayPrefix ? ('Day ' . ((int) ($transportLabel['day'] ?? 1)) . ' | ') : '' }}{{ $transportLabel['transport_name'] ?? '-' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if ($flatItemNames->isNotEmpty())
                                            <div class="max-h-64 space-y-2 overflow-auto overscroll-contain pr-1 text-xs text-gray-700 dark:text-gray-200">
                                                @if ($isMultiDayPopover)
                                                    @foreach ($itemsByDay as $day => $dayItemNames)
                                                        <div>
                                                            <p class="mb-1 font-semibold text-gray-500 dark:text-gray-400">{{ ui_phrase('Day') }} {{ $day }}</p>
                                                            <ul class="list-disc space-y-1 pl-5 marker:text-gray-500 dark:marker:text-gray-400">
                                                                @foreach ($dayItemNames as $itemName)
                                                                    <li class="flex items-center gap-2">
                                                                        <span>{{ $itemName }}</span>
                                                                        @if ((string) $itemName === (string) $highlightedItemLabel)
                                                                            <span class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">Highlighted</span>
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <ul class="list-disc space-y-1 pl-5 marker:text-gray-500 dark:marker:text-gray-400">
                                                        @foreach ($flatItemNames as $itemName)
                                                            <li class="flex items-center gap-2">
                                                                <span>{{ $itemName }}</span>
                                                                @if ((string) $itemName === (string) $highlightedItemLabel)
                                                                    <span class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">Highlighted</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('No items available.') }}</p>
                                        @endif
                                        @if (Route::has('quotations.create') && auth()->user()?->can('module.quotations.access'))
                                            <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                                <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}" class="btn-primary-sm w-full justify-center">
                                                    {{ ui_phrase('Generate Quotation') }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-outline-sm" title="{{ ui_phrase('View') }}" aria-label="{{ ui_phrase('View') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('View') }}</span></a>
                                @if (! $itinerary->trashed())
                                    <form action="{{ route('itineraries.duplicate', $itinerary) }}" method="POST" class="inline" onsubmit="if (!confirm('{{ ui_phrase('confirm duplicate') }}')) { return false; } const button = this.querySelector('button[type=submit]'); if (button) { button.disabled = true; button.classList.add('opacity-60', 'cursor-not-allowed'); } return true;">
                                        @csrf
                                        <button type="submit" class="btn-ghost-sm" title="{{ ui_phrase('Duplicate') }}" aria-label="{{ ui_phrase('Duplicate') }}">
                                            <i class="fa-solid fa-copy"></i><span class="sr-only">{{ ui_phrase('Duplicate') }}</span>
                                        </button>
                                    </form>
                                @endif
                                @can('update', $itinerary)
                                    <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                                @endcan
                                @if (auth()->user()?->hasAnyRole(['Super Admin', 'Super User', 'Administrator']))
                                    <form action="{{ route('itineraries.destroy', $itinerary) }}" method="POST" class="inline" onsubmit="return confirm('{{ ui_phrase('Are you sure you want to delete this itinerary?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost-sm text-rose-600 hover:text-rose-700 dark:text-rose-300 dark:hover:text-rose-200" title="{{ ui_phrase('Delete') }}" aria-label="{{ ui_phrase('Delete') }}">
                                            <i class="fa-solid fa-trash"></i><span class="sr-only">{{ ui_phrase('Delete') }}</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Itineraries')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($itineraries as $itinerary)
                @php
                    $formatItemWithVendor = static function (?string $itemName, ?string $vendorName): ?string {
                        $name = trim((string) $itemName);
                        if ($name === '') {
                            return null;
                        }
                        $vendor = trim((string) $vendorName);
                        return $vendor !== '' ? ($name . ' | ' . $vendor) : $name;
                    };

                    $dayItems = collect()
                        ->merge($itinerary->touristAttractions->map(fn ($item) => [
                            'day' => max(1, (int) ($item->pivot->day_number ?? 1)),
                            'start_time' => trim((string) ($item->pivot->start_time ?? '')),
                            'sort_order' => (int) ($item->pivot->visit_order ?? 0),
                            'label' => trim((string) ($item->name ?? '')),
                            'item_name' => trim((string) ($item->name ?? '')),
                            'vendor_name' => '',
                        ]))
                        ->merge($itinerary->itineraryActivities->map(fn ($item) => [
                            'day' => max(1, (int) ($item->day_number ?? 1)),
                            'start_time' => trim((string) ($item->start_time ?? '')),
                            'sort_order' => (int) ($item->visit_order ?? 0),
                            'label' => (string) $formatItemWithVendor($item->activity?->name, $item->activity?->vendor?->name),
                            'item_name' => trim((string) ($item->activity?->name ?? '')),
                            'vendor_name' => trim((string) ($item->activity?->vendor?->name ?? '')),
                        ]))
                        ->merge($itinerary->itineraryIslandTransfers->map(fn ($item) => [
                            'day' => max(1, (int) ($item->day_number ?? 1)),
                            'start_time' => trim((string) ($item->start_time ?? '')),
                            'sort_order' => (int) ($item->visit_order ?? 0),
                            'label' => (string) $formatItemWithVendor($item->islandTransfer?->name, $item->islandTransfer?->vendor?->name),
                            'item_name' => trim((string) ($item->islandTransfer?->name ?? '')),
                            'vendor_name' => trim((string) ($item->islandTransfer?->vendor?->name ?? '')),
                        ]))
                        ->merge($itinerary->itineraryFoodBeverages->map(fn ($item) => [
                            'day' => max(1, (int) ($item->day_number ?? 1)),
                            'start_time' => trim((string) ($item->start_time ?? '')),
                            'sort_order' => (int) ($item->visit_order ?? 0),
                            'label' => (string) $formatItemWithVendor($item->foodBeverage?->name, $item->foodBeverage?->vendor?->name),
                            'item_name' => trim((string) ($item->foodBeverage?->name ?? '')),
                            'vendor_name' => trim((string) ($item->foodBeverage?->vendor?->name ?? '')),
                        ]))
                        ->filter(fn ($row) => filled($row['label'] ?? null))
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

                            return ((int) ($left['sort_order'] ?? 0)) <=> ((int) ($right['sort_order'] ?? 0));
                        })
                        ->values();

                    $itemsByDay = $dayItems
                        ->groupBy('day')
                        ->map(fn ($items) => $items->pluck('label')->map(fn ($name) => trim((string) $name))->unique()->values())
                        ->sortKeys();

                    $isMultiDayPopover = (int) ($itinerary->duration_days ?? 1) > 1;
                    $flatItemNames = $itemsByDay->flatten(1)->unique()->values();
                    $highlightedDayPoint = $itinerary->dayPoints
                        ->filter(function ($point) {
                            return filled($point->main_experience_type)
                                || (int) ($point->main_tourist_attraction_id ?? 0) > 0
                                || (int) ($point->main_activity_id ?? 0) > 0
                                || (int) ($point->main_food_beverage_id ?? 0) > 0;
                        })
                        ->sortBy(fn ($point) => (int) ($point->day_number ?? 0))
                        ->first();
                    $highlightedItemName = '';
                    $highlightedItemVendorName = '';
                    if ($highlightedDayPoint) {
                        $mainType = strtolower(trim((string) ($highlightedDayPoint->main_experience_type ?? '')));
                        $mainAttraction = $highlightedDayPoint->mainTouristAttraction;
                        $mainActivity = $highlightedDayPoint->mainActivity;
                        $mainFoodBeverage = $highlightedDayPoint->mainFoodBeverage;

                        if (in_array($mainType, ['attraction', 'tourist_attraction'], true)) {
                            $highlightedItemName = trim((string) ($mainAttraction?->name ?? ''));
                        } elseif (in_array($mainType, ['activity'], true)) {
                            $highlightedItemName = trim((string) ($mainActivity?->name ?? ''));
                            $highlightedItemVendorName = trim((string) ($mainActivity?->vendor?->name ?? ''));
                        } elseif (in_array($mainType, ['fnb', 'food_beverage'], true)) {
                            $highlightedItemName = trim((string) ($mainFoodBeverage?->name ?? ''));
                            $highlightedItemVendorName = trim((string) ($mainFoodBeverage?->vendor?->name ?? ''));
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
                            }
                        }
                    }
                    if ($highlightedItemName === '') {
                        $fallbackHighlightedItem = $dayItems->first();
                        $highlightedItemName = trim((string) ($fallbackHighlightedItem['item_name'] ?? $flatItemNames->first() ?? ''));
                        $highlightedItemVendorName = trim((string) ($fallbackHighlightedItem['vendor_name'] ?? ''));
                    }
                    $highlightedItemLabel = $highlightedItemName;
                    if ($highlightedItemVendorName !== '') {
                        $highlightedItemLabel .= ' | ' . $highlightedItemVendorName;
                    }
                    $resolveHotelRegion = static function ($hotel): string {
                        if (! $hotel) {
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
                        $region = trim((string) ($hotel->destination?->province ?? $hotel->destination?->name ?? ''));
                        return $region !== '' ? $region : '-';
                    };
                    $resolveStartLabelFromPoint = static function ($point) use ($resolveHotelRegion): string {
                        if (! $point) {
                            return '';
                        }
                        $startType = strtolower(trim((string) ($point->start_point_type ?? '')));
                        if ($startType === 'airport') {
                            return trim((string) ($point->startAirport?->name ?? ''));
                        }
                        if ($startType !== 'hotel' && $startType !== 'previous_day_end') {
                            return '';
                        }
                        $isSelfBooked = strtolower(trim((string) ($point->start_hotel_booking_mode ?? ''))) === 'self';
                        if ($isSelfBooked) {
                            return trim((string) ($point->start_hotel_area ?? ''));
                        }
                        return trim((string) ($point->startHotel?->name ?? ''));
                    };
                    $resolveEndLabelFromPoint = static function ($point) use ($resolveHotelRegion): string {
                        if (! $point) {
                            return '';
                        }
                        $endType = strtolower(trim((string) ($point->end_point_type ?? '')));
                        if ($endType === 'airport') {
                            return trim((string) ($point->endAirport?->name ?? ''));
                        }
                        if ($endType !== 'hotel') {
                            return '';
                        }
                        $isSelfBooked = strtolower(trim((string) ($point->end_hotel_booking_mode ?? ''))) === 'self';
                        if ($isSelfBooked) {
                            return trim((string) ($point->end_hotel_area ?? ''));
                        }
                        return trim((string) ($point->endHotel?->name ?? ''));
                    };
                    $sortedDayPoints = $itinerary->dayPoints
                        ->sortBy(fn ($point) => (int) ($point->day_number ?? 0))
                        ->values();
                    $firstDayPoint = $sortedDayPoints->first();
                    $lastDayPoint = $sortedDayPoints->last();
                    $startPointLabel = $resolveStartLabelFromPoint($firstDayPoint);
                    $endPointLabel = $resolveEndLabelFromPoint($lastDayPoint);
                    if ($startPointLabel === '') {
                        $startPointLabel = (string) ($sortedDayPoints
                            ->map(fn ($point) => $resolveStartLabelFromPoint($point))
                            ->first(fn ($label) => trim((string) $label) !== '') ?? '');
                    }
                    if ($startPointLabel === '') {
                        $startPointLabel = (string) ($sortedDayPoints
                            ->map(fn ($point) => $resolveEndLabelFromPoint($point))
                            ->first(fn ($label) => trim((string) $label) !== '') ?? '');
                    }
                    if ($endPointLabel === '') {
                        $endPointLabel = (string) ($sortedDayPoints
                            ->reverse()
                            ->map(fn ($point) => $resolveEndLabelFromPoint($point))
                            ->first(fn ($label) => trim((string) $label) !== '') ?? '');
                    }
                    if ($endPointLabel === '') {
                        $endPointLabel = (string) ($sortedDayPoints
                            ->reverse()
                            ->map(fn ($point) => $resolveStartLabelFromPoint($point))
                            ->first(fn ($label) => trim((string) $label) !== '') ?? '');
                    }
                    $startPointLabel = $startPointLabel !== '' ? $startPointLabel : '-';
                    $endPointLabel = $endPointLabel !== '' ? $endPointLabel : '-';
                    $titleWithHighlight = trim((string) ($itinerary->title ?? ''))
                        . ' | Start: ' . $startPointLabel
                        . ' - End: ' . $endPointLabel;
                    $capacityByDay = $itinerary->itineraryTransportUnits
                        ->groupBy(fn ($row) => max(1, (int) ($row->day_number ?? 1)))
                        ->map(fn ($rows) => (int) $rows->sum(fn ($row) => max(0, (int) ($row->transportUnit?->seat_capacity ?? 0))));
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
                        ->filter(fn ($row) => is_array($row) && filled($row['transport_name'] ?? null))
                        ->unique(fn ($row) => strtolower((string) ($row['transport_name'] ?? '')) . '|' . (int) ($row['day'] ?? 0))
                        ->sortBy(fn ($row) => [(int) ($row['day'] ?? 0), strtolower((string) ($row['transport_name'] ?? ''))])
                        ->values();
                @endphp
                <div class="app-card p-4">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $titleWithHighlight }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('by :name', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), ui_phrase('system')) ?: '-']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('day count', ['count' => $itinerary->duration_days]) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Quotation') }}: {{ (int) ($itinerary->quotations?->count() ?? 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Capacity') }}: {{ $totalCapacity }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $itinerary->destination?->name ?? $itinerary->destination ?? '-' }}</p>
                    <div class="mt-3">
                        <div class="relative inline-block text-left itinerary-items-popover" data-popover-root>
                            <button type="button" class="btn-outline-sm" data-popover-trigger aria-expanded="false" aria-haspopup="true">
                                Desc
                            </button>
                            <div class="hidden w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900" data-popover-panel role="dialog" aria-label="{{ ui_phrase('Itinerary item list') }}" style="position: fixed; z-index: 9999;">
                                <span class="pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700" data-popover-arrow aria-hidden="true"></span>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Item List') }}</p>
                                @if ($transportItems->isNotEmpty())
                                    <div class="mb-2 space-y-1 border-b border-gray-200 pb-2 dark:border-gray-700">
                                        @foreach ($transportItems as $transportLabel)
                                            <div class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                                <i class="fa-solid fa-van-shuttle w-3 text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                                                <span>{{ $showTransportDayPrefix ? ('Day ' . ((int) ($transportLabel['day'] ?? 1)) . ' | ') : '' }}{{ $transportLabel['transport_name'] ?? '-' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($flatItemNames->isNotEmpty())
                                    <div class="max-h-64 space-y-2 overflow-auto overscroll-contain pr-1 text-xs text-gray-700 dark:text-gray-200">
                                        @if ($isMultiDayPopover)
                                            @foreach ($itemsByDay as $day => $dayItemNames)
                                                <div>
                                                    <p class="mb-1 font-semibold text-gray-500 dark:text-gray-400">{{ ui_phrase('Day') }} {{ $day }}</p>
                                                    <ul class="list-disc space-y-1 pl-5 marker:text-gray-500 dark:marker:text-gray-400">
                                                        @foreach ($dayItemNames as $itemName)
                                                            <li class="flex items-center gap-2">
                                                                <span>{{ $itemName }}</span>
                                                                @if ((string) $itemName === (string) $highlightedItemLabel)
                                                                    <span class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">Highlighted</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endforeach
                                        @else
                                            <ul class="list-disc space-y-1 pl-5 marker:text-gray-500 dark:marker:text-gray-400">
                                                @foreach ($flatItemNames as $itemName)
                                                    <li class="flex items-center gap-2">
                                                        <span>{{ $itemName }}</span>
                                                        @if ((string) $itemName === (string) $highlightedItemLabel)
                                                            <span class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200">Highlighted</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('No items available.') }}</p>
                                @endif
                                @if (Route::has('quotations.create') && auth()->user()?->can('module.quotations.access'))
                                    <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                        <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}" class="btn-primary-sm w-full justify-center">
                                            {{ ui_phrase('Generate Quotation') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-outline-sm" title="{{ ui_phrase('View') }}" aria-label="{{ ui_phrase('View') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('View') }}</span></a>
                        @if (! $itinerary->trashed())
                            <form action="{{ route('itineraries.duplicate', $itinerary) }}" method="POST" class="inline" onsubmit="if (!confirm('{{ ui_phrase('confirm duplicate') }}')) { return false; } const button = this.querySelector('button[type=submit]'); if (button) { button.disabled = true; button.classList.add('opacity-60', 'cursor-not-allowed'); } return true;">
                                @csrf
                                <button type="submit" class="btn-ghost-sm" title="{{ ui_phrase('Duplicate') }}" aria-label="{{ ui_phrase('Duplicate') }}">
                                    <i class="fa-solid fa-copy"></i><span class="sr-only">{{ ui_phrase('Duplicate') }}</span>
                                </button>
                            </form>
                        @endif
                        @can('update', $itinerary)
                            <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                        @endcan
                        @if (auth()->user()?->hasAnyRole(['Super Admin', 'Super User', 'Administrator']))
                            <form action="{{ route('itineraries.destroy', $itinerary) }}" method="POST" class="inline" onsubmit="return confirm('{{ ui_phrase('Are you sure you want to delete this itinerary?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost-sm text-rose-600 hover:text-rose-700 dark:text-rose-300 dark:hover:text-rose-200" title="{{ ui_phrase('Delete') }}" aria-label="{{ ui_phrase('Delete') }}">
                                    <i class="fa-solid fa-trash"></i><span class="sr-only">{{ ui_phrase('Delete') }}</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Itineraries')]) }}</div>
            @endforelse
        </div>
        <div>{{ $itineraries->links() }}</div>
        </div>
            </div>
        </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const state = window.__itineraryPopoverState || {
        popovers: [],
        observer: null,
        boundGlobals: false,
    };
    window.__itineraryPopoverState = state;

    const positionPanel = function (trigger, panel) {
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
            const panelTop = placement === 'right' || placement === 'left'
                ? (top - (panelHeight / 2))
                : top;
            const minArrowTop = 14;
            const maxArrowTop = Math.max(minArrowTop, panelHeight - 14);
            const alignedArrowTop = Math.min(
                maxArrowTop,
                Math.max(minArrowTop, (triggerCenterY - panelTop))
            );

            if (placement === 'right') {
                arrow.className = 'pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700';
                arrow.style.left = '-10px';
                arrow.style.right = 'auto';
                arrow.style.top = alignedArrowTop + 'px';
                arrow.style.transform = 'translateY(-50%)';
            } else if (placement === 'left') {
                arrow.className = 'pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-l-[10px] border-l-gray-700 dark:border-l-gray-700';
                arrow.style.left = 'auto';
                arrow.style.right = '-10px';
                arrow.style.top = alignedArrowTop + 'px';
                arrow.style.transform = 'translateY(-50%)';
            } else {
                arrow.className = 'pointer-events-none absolute h-0 w-0 border-x-[8px] border-x-transparent border-b-[10px] border-b-gray-700 dark:border-b-gray-700';
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

    const closeAll = function () {
        state.popovers.forEach(function (entry) {
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

    const openPanel = function (trigger, panel) {
        panel.style.visibility = 'hidden';
        panel.classList.remove('hidden');
        positionPanel(trigger, panel);
        panel.style.visibility = '';
    };

    const cleanupDetachedPopovers = function () {
        state.popovers = state.popovers.filter(function (entry) {
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

    const bindRoots = function (scope) {
        const searchRoot = scope instanceof Element || scope instanceof Document ? scope : document;
        const roots = Array.from(searchRoot.querySelectorAll('[data-popover-root]'));
        roots.forEach(function (root) {
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
        state.popovers.push({ root: root, trigger: trigger, panel: panel });

        trigger.addEventListener('click', function (event) {
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

        document.addEventListener('click', function (event) {
            cleanupDetachedPopovers();
            const clickedInsidePopover = state.popovers.some(function (entry) {
                return entry.root.contains(event.target) || entry.panel.contains(event.target);
            });
            if (!clickedInsidePopover) {
                closeAll();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAll();
            }
        });

        window.addEventListener('resize', closeAll);
        window.addEventListener('scroll', function (event) {
            const scrollTarget = event.target;
            const isScrollingInsidePopover = scrollTarget instanceof Element && state.popovers.some(function (entry) {
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
        state.observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
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
        state.observer.observe(document.body, { childList: true, subtree: true });
    }

    const filterForm = document.querySelector('[data-service-filter-form]');
    if (filterForm) {
        const minFilterLength = 3;
        const textFilterInputs = Array.from(filterForm.querySelectorAll('input[type="text"], input[type="search"]'));
        const titleInputVisible = filterForm.querySelector('[data-filter-title-visible]');
        const titleInputHidden = filterForm.querySelector('[data-filter-title-hidden]');
        let lastSubmittedTitleValue = String(titleInputHidden?.value || '').trim();

        const isTextFilterValueValid = function (value) {
            const normalized = String(value || '').trim();
            return normalized === '' || normalized.length >= minFilterLength;
        };

        const isAllTextFiltersValid = function () {
            return textFilterInputs.every(function (input) {
                return isTextFilterValueValid(input.value);
            });
        };

        const syncInputValidityMessage = function (input) {
            if (!input) return;
            if (isTextFilterValueValid(input.value)) {
                input.setCustomValidity('');
                return;
            }
            input.setCustomValidity('{{ ui_phrase('Please enter at least :count characters before filtering.', ['count' => 3]) }}');
        };

        textFilterInputs.forEach(function (input) {
            input.addEventListener('input', function () {
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

            input.addEventListener('blur', function () {
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

            input.addEventListener('keydown', function (event) {
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

        filterForm.addEventListener('submit', function (event) {
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
        filterForm.addEventListener('change', function (event) {
            if (!titleInputVisible || !titleInputHidden) {
                return;
            }
            const target = event.target;
            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) {
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


