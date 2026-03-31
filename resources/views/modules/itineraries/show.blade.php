@extends('layouts.master')

@section('content')
    <div class="space-y-6 itinerary-show-page">
        <div class="app-card p-4 mb-6">
            @section('page_actions')@if (Route::has('quotations.create') && auth()->user()->can('module.quotations.access') && ! $itinerary->quotation)
                        <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}" class="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/20">Generate Quotation</a>
                    @endif
                    <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'stream']) }}" target="_blank" rel="noopener" class="rounded-lg border border-sky-300 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50 dark:border-sky-700 dark:text-sky-300 dark:hover:bg-sky-900/20">Preview PDF</a>
                    <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'download']) }}" target="_blank" rel="noopener" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">Download PDF</a>
                    @can('update', $itinerary)
                        @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') && ! $itinerary->isFinal())
                            <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary">Edit</a>
                        @endif
                    @endcan
                    <a href="{{ route('itineraries.index') }}"  class="btn-primary">Back</a>@endsection
            <div class="my-3 grid grid-cols-1 gap-2 text-sm md:grid-cols-2">
                <div><span class="text-gray-500 dark:text-gray-400">Title:</span> <span class="text-gray-800 dark:text-gray-100">{{ $itinerary->title }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Duration:</span> <span class="text-gray-800 dark:text-gray-100">{{ $itinerary->duration_days }}D{{ $itinerary->duration_nights > 0 ? "/".$itinerary->duration_nights."N": "" }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Destination:</span> <span class="text-gray-800 dark:text-gray-100">{{ $itinerary->destination ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Inquiry:</span>
                    <span class="text-gray-800 dark:text-gray-100">
                        @if ($itinerary->inquiry)
                            {{ $itinerary->inquiry?->inquiry_number ?? '-' }}{{ $itinerary->inquiry?->customer?->name ? ' | '.$itinerary->inquiry?->customer?->name : '' }}
                        @else
                            Independent
                        @endif
                    </span>
                </div>
            </div>
            <div>
                @if ($itinerary->description)
                    <x-rich-text :content="$itinerary->description" class="text-sm text-gray-700 dark:text-gray-200" />
                @endif
            </div>
        </div>

        

        @if ($itinerary->hotels->isNotEmpty())
            <div class="app-card p-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Hotels</h2>
                @php
                    $dayPointByDayForHotel = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
                @endphp
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($itinerary->hotels as $hotel)
                        @php
                            $stayDay = (int) ($hotel->pivot->day_number ?? 1);
                            $stayPoint = $dayPointByDayForHotel[$stayDay] ?? null;
                            if ($stayPoint && (int) ($stayPoint->end_hotel_id ?? 0) !== (int) $hotel->id) {
                                $stayPoint = null;
                            }
                            $roomName = (string) ($stayPoint?->endHotelRoom?->rooms ?? '');
                            $roomType = (string) ($stayPoint?->endHotelRoom?->view ?? '');
                        @endphp
                        <div class="rounded-lg mb-6 border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $hotel->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $hotel->category ?: '-' }}
                                @if(!is_null($hotel->star_rating))
                                    | {{ $hotel->star_rating }} star
                                @endif
                                | {{ $hotel->city ?: '-' }}
                            </p>
                            @if ($roomName !== '')
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                    Room: {{ $roomName }}{{ $roomType !== '' ? ' ('.$roomType.')' : '' }}
                                </p>
                            @endif
                            <p class="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-300">
                                Day {{ $hotel->pivot->day_number ?? 1 }} | {{ $hotel->pivot->night_count ?? 1 }} night(s)
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid min-w-0 grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="app-card min-w-0 p-4 lg:col-span-7">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Schedule by Day</h2>
                <div class="mt-3 space-y-4 text-sm text-gray-700 dark:text-gray-200">
                    @php
                        $dayPointByDay = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
                        $toMinutes = function ($time) {
                            $value = substr((string) $time, 0, 5);
                            if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
                                return null;
                            }
                            return ((int) substr($value, 0, 2) * 60) + (int) substr($value, 3, 2);
                        };
                        $fromMinutes = function ($minutes) {
                            if (!is_numeric($minutes)) {
                                return null;
                            }
                            $normalized = max(0, (int) $minutes);
                            $hours = (int) floor($normalized / 60);
                            $mins = $normalized % 60;
                            return str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT);
                        };
                        $resolvePointLabel = function ($dayPoint, string $scope, string $previousDayEndLabel = 'Not set') {
                            if (!$dayPoint) {
                                return 'Not set';
                            }
                            if ($scope === 'start') {
                                $type = (string) ($dayPoint->start_point_type ?? '');
                                if ($type === 'previous_day_end') {
                                    return $previousDayEndLabel ?: 'Not set';
                                }
                                if ($type === 'airport') {
                                    return $dayPoint->startAirport?->name ?: 'Not set';
                                }
                                if ($type === 'hotel') {
                                    $hotelName = (string) ($dayPoint->startHotel?->name ?? 'Not set');
                                    $roomName = (string) ($dayPoint->startHotelRoom?->rooms ?? '');
                                    if ($hotelName === 'Not set') {
                                        return 'Not set';
                                    }
                                    if ($roomName !== '') {
                                        return $hotelName . ' - ' . $roomName;
                                    }
                                    return $hotelName;
                                }
                                return 'Not set';
                            }
                            $type = (string) ($dayPoint->end_point_type ?? '');
                            if ($type === 'airport') {
                                return $dayPoint->endAirport?->name ?: 'Not set';
                            }
                            if ($type === 'hotel') {
                                $hotelName = (string) ($dayPoint->endHotel?->name ?? 'Not set');
                                $roomName = (string) ($dayPoint->endHotelRoom?->rooms ?? '');
                                if ($hotelName === 'Not set') {
                                    return 'Not set';
                                }
                                if ($roomName !== '') {
                                    return $hotelName . ' - ' . $roomName;
                                }
                                return $hotelName;
                            }
                            return 'Not set';
                        };
                        $resolvePointLocation = function ($dayPoint, string $scope, ?string $previousDayEndLocation = null) {
                            if (!$dayPoint) {
                                return null;
                            }
                            if ($scope === 'start') {
                                $type = (string) ($dayPoint->start_point_type ?? '');
                                if ($type === 'previous_day_end') {
                                    return $previousDayEndLocation;
                                }
                                if ($type === 'airport') {
                                    return $dayPoint->startAirport?->location;
                                }
                                if ($type === 'hotel') {
                                    return $dayPoint->startHotel?->address;
                                }
                                return null;
                            }
                            $type = (string) ($dayPoint->end_point_type ?? '');
                            if ($type === 'airport') {
                                return $dayPoint->endAirport?->location;
                            }
                            if ($type === 'hotel') {
                                return $dayPoint->endHotel?->address;
                            }
                            return null;
                        };
                    @endphp
                    @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                        @php
                            $attractions = collect();
                            foreach (($dayGroups[$day] ?? collect()) as $attraction) {
                                $attractions->push([
                                    'type' => 'attraction',
                                    'experience_id' => (int) $attraction->id,
                                    'name' => $attraction->name,
                                    'location' => $attraction->location,
                                    'description' => $attraction->description,
                                    'pax' => null,
                                    'start_time' => $attraction->pivot->start_time,
                                    'end_time' => $attraction->pivot->end_time,
                                    'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next,
                                    'visit_order' => $attraction->pivot->visit_order ?? 999999,
                                ]);
                            }
                            $activities = collect();
                            foreach (($activityDayGroups[$day] ?? collect()) as $activityItem) {
                                $activity = $activityItem->activity;
                                $activities->push([
                                    'type' => 'activity',
                                    'experience_id' => (int) ($activityItem->activity_id ?? 0),
                                    'name' => $activity->name ?? '-',
                                    'location' => $activity->vendor->location ?? null,
                                    'description' => $activity->notes ?? null,
                                    'includes' => $activity->includes ?? null,
                                    'excludes' => $activity->excludes ?? null,
                                    'benefits' => $activity->benefits ?? null,
                                    'pax' => $activityItem->pax,
                                    'pax_adult' => $activityItem->pax_adult ?? $activityItem->pax,
                                    'pax_child' => $activityItem->pax_child ?? 0,
                                    'start_time' => $activityItem->start_time,
                                    'end_time' => $activityItem->end_time,
                                    'travel_minutes_to_next' => $activityItem->travel_minutes_to_next,
                                    'visit_order' => $activityItem->visit_order ?? 999999,
                                ]);
                            }
                            $foodBeverages = collect();
                            foreach (($foodBeverageDayGroups[$day] ?? collect()) as $foodBeverageItem) {
                                $foodBeverage = $foodBeverageItem->foodBeverage;
                                $foodBeverages->push([
                                    'type' => 'fnb',
                                    'experience_id' => (int) ($foodBeverageItem->food_beverage_id ?? 0),
                                    'name' => $foodBeverage->name ?? '-',
                                    'location' => $foodBeverage->vendor->location ?? null,
                                    'description' => $foodBeverage->notes ?? $foodBeverage->menu_highlights ?? null,
                                    'meal_period' => $foodBeverage->meal_period ?? null,
                                    'service_type' => $foodBeverage->service_type ?? null,
                                    'publish_rate' => $foodBeverage->publish_rate ?? null,
                                    'currency' => 'IDR',
                                    'pax' => $foodBeverageItem->pax,
                                    'start_time' => $foodBeverageItem->start_time,
                                    'end_time' => $foodBeverageItem->end_time,
                                    'travel_minutes_to_next' => $foodBeverageItem->travel_minutes_to_next,
                                    'visit_order' => $foodBeverageItem->visit_order ?? 999999,
                                ]);
                            }
                            $dayItems = $attractions->merge($activities)->merge($foodBeverages)->sortBy('visit_order')->values();
                            $dayTransport = $transportUnitByDay[$day] ?? null;
                            $dayPoint = $dayPointByDay[$day] ?? null;
                            $previousDayPoint = $dayPointByDay[$day - 1] ?? null;
                            $previousEndLabel = $resolvePointLabel($previousDayPoint, 'end');
                            $previousEndLocation = $resolvePointLocation($previousDayPoint, 'end');
                            $startPointTypeRaw = (string) ($dayPoint->start_point_type ?? '');
                            $startPointType = $startPointTypeRaw === 'previous_day_end'
                                ? (string) ($previousDayPoint->end_point_type ?? 'hotel')
                                : $startPointTypeRaw;
                            if (!in_array($startPointType, ['airport', 'hotel'], true)) {
                                $startPointType = 'hotel';
                            }
                            $endPointType = (string) ($dayPoint->end_point_type ?? 'hotel');
                            if (!in_array($endPointType, ['airport', 'hotel'], true)) {
                                $endPointType = 'hotel';
                            }
                            $startPointLabel = $dayPoint
                                ? $resolvePointLabel($dayPoint, 'start', $previousEndLabel)
                                : ($day > 1 ? $previousEndLabel : 'Not set');
                            $endPointLabel = $resolvePointLabel($dayPoint, 'end');
                            $startPointLocation = $dayPoint
                                ? $resolvePointLocation($dayPoint, 'start', $previousEndLocation)
                                : ($day > 1 ? $previousEndLocation : null);
                            $endPointLocation = $resolvePointLocation($dayPoint, 'end');
                            $firstItem = $dayItems->first();
                            $lastItem = $dayItems->last();
                            $dayStartTime = $dayPoint && !empty($dayPoint->day_start_time)
                                ? substr((string) $dayPoint->day_start_time, 0, 5)
                                : (!empty($firstItem['start_time']) ? substr((string) $firstItem['start_time'], 0, 5) : null);
                            $dayStartTravelMinutes = $dayPoint && $dayPoint->day_start_travel_minutes !== null
                                ? max(0, (int) $dayPoint->day_start_travel_minutes)
                                : null;
                            $mainExperienceType = (string) ($dayPoint?->main_experience_type ?? '');
                            if (!in_array($mainExperienceType, ['attraction', 'activity', 'fnb'], true)) {
                                $mainExperienceType = '';
                            }
                            $mainExperienceId = $mainExperienceType === 'attraction'
                                ? (int) ($dayPoint?->main_tourist_attraction_id ?? 0)
                                : ($mainExperienceType === 'activity'
                                    ? (int) ($dayPoint?->main_activity_id ?? 0)
                                    : ($mainExperienceType === 'fnb'
                                        ? (int) ($dayPoint?->main_food_beverage_id ?? 0)
                                        : 0));
                            $mainExperienceName = null;
                            if ($mainExperienceType !== '' && $mainExperienceId > 0) {
                                $mainItem = $dayItems->first(
                                    fn ($item) => (string) ($item['type'] ?? '') === $mainExperienceType
                                        && (int) ($item['experience_id'] ?? 0) === $mainExperienceId
                                );
                                $mainExperienceName = $mainItem['name'] ?? null;
                            }
                            $lastEndBaseMinutes = $lastItem ? $toMinutes($lastItem['end_time'] ?? null) : null;
                            $lastTravelToEnd = $lastItem ? max(0, (int) ($lastItem['travel_minutes_to_next'] ?? 0)) : 0;
                            $dayEndTime = $lastEndBaseMinutes !== null ? $fromMinutes($lastEndBaseMinutes + $lastTravelToEnd) : null;
                        @endphp
                        <div>
                            <div class="app-day-header">
                                <p class="app-day-header-title">Day {{ $day }}</p>
                                <p class="app-day-header-meta">
                                    Start Tour: {{ $dayStartTime ?? '--:--' }} | End Tour: {{ $dayEndTime ?? '--:--' }}
                                </p>
                            </div>

                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Starts at: {{ $startPointLabel ?: 'Not set' }} | Ends at: {{ $endPointLabel ?: 'Not set' }}
                            </p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Transport Unit:
                                @if ($dayTransport && $dayTransport->transportUnit)
                                    {{ $dayTransport->transportUnit->name }}
                                    @if (!empty($dayTransport->transportUnit->transport?->name))
                                        ({{ $dayTransport->transportUnit->transport->name }})
                                    @endif
                                @else
                                    -
                                @endif
                            </p>
                            <ul class="itinerary-timeline-list relative mt-2 space-y-2">
                                <li class="flex items-start gap-0">
                                    <div class="timeline-node-col has-travel w-10 flex flex-col items-center">
                                        <span
                                             class="timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold text-white {{ $startPointType === 'airport' ? 'bg-sky-600' : 'bg-teal-600' }}">
                                            <i class="{{ $startPointType === 'airport' ? 'fa-solid fa-plane' : 'fa-solid fa-bed' }}"></i>
                                        </span>
                                        <span class="timeline-travel-spacer"></span>
                                        <span class="timeline-travel-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 fill-current" focusable="false">
                                                <path d="M5.5 11.5L7.3 6.9C7.6 6.1 8.3 5.5 9.2 5.5h5.6c.9 0 1.6.6 1.9 1.4l1.8 4.6c1 .2 1.8 1.1 1.8 2.2v2.3c0 .8-.7 1.5-1.5 1.5h-.5a2.3 2.3 0 01-4.6 0h-4.4a2.3 2.3 0 01-4.6 0h-.5c-.8 0-1.5-.7-1.5-1.5v-2.3c0-1.1.8-2 1.8-2.2zm3.1-4.2L7.2 11h9.6l-1.4-3.7a.8.8 0 00-.7-.5H9.3c-.3 0-.6.2-.7.5zM8.2 18.9c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9zm7.6 0c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9z"/>
                                            </svg>
                                        </span>
                                        <span class="timeline-travel-spacer"></span>
                                        <span class="timeline-travel-label">
                                            {{ $dayStartTravelMinutes !== null ? $dayStartTravelMinutes : '-' }} min
                                        </span>
                                        <span class="timeline-travel-spacer"></span>
                                    </div>
                                    <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                    <div class="ml-2 flex-1 rounded-lg border border-gray-200 px-2 py-1 dark:border-gray-700">
                                        <span class="font-medium">{{ $startPointLabel ?: 'Not set' }}</span>
                                        <span class="ml-1 text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">Start Point</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $startPointLocation ?: '-' }}
                                            | Start Tour: {{ $dayStartTime ?? '--:--' }}
                                        </div>
                                    </div>
                                </li>
                                @forelse ($dayItems as $index => $item)
                                    @php
                                        $isLast = $index === ($dayItems->count() - 1);
                                        $travelMinutes = $item['travel_minutes_to_next'];
                                        $isMainExperience = $mainExperienceType !== ''
                                            && (string) ($item['type'] ?? '') === $mainExperienceType
                                            && (int) ($item['experience_id'] ?? 0) === $mainExperienceId;
                                    @endphp
                                    <li class="flex items-start gap-0">
                                        <div class="timeline-node-col has-travel w-10 flex flex-col items-center">
                                            <span
                                                 class="timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold text-white {{ $item['type'] === 'activity' ? 'bg-emerald-600' : ($item['type'] === 'fnb' ? 'bg-amber-600' : 'bg-indigo-600') }}">
                                                <i class="{{ $item['type'] === 'activity' ? 'fa-solid fa-person-hiking' : ($item['type'] === 'fnb' ? 'fa-solid fa-utensils' : 'fa-solid fa-location-dot') }}"></i>
                                            </span>
                                            <span class="timeline-travel-spacer"></span>
                                            <span class="timeline-travel-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 fill-current" focusable="false">
                                                    <path d="M5.5 11.5L7.3 6.9C7.6 6.1 8.3 5.5 9.2 5.5h5.6c.9 0 1.6.6 1.9 1.4l1.8 4.6c1 .2 1.8 1.1 1.8 2.2v2.3c0 .8-.7 1.5-1.5 1.5h-.5a2.3 2.3 0 01-4.6 0h-4.4a2.3 2.3 0 01-4.6 0h-.5c-.8 0-1.5-.7-1.5-1.5v-2.3c0-1.1.8-2 1.8-2.2zm3.1-4.2L7.2 11h9.6l-1.4-3.7a.8.8 0 00-.7-.5H9.3c-.3 0-.6.2-.7.5zM8.2 18.9c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9zm7.6 0c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9z"/>
                                                </svg>
                                            </span>
                                            <span class="timeline-travel-spacer"></span>
                                            <span class="timeline-travel-label">
                                                {{ $travelMinutes !== null ? $travelMinutes . ' min' : '- min' }}
                                            </span>
                                            <span class="timeline-travel-spacer"></span>
                                        </div>
                                            <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                        <div class="ml-2 flex-1 rounded-lg border px-2 py-1 {{ $isMainExperience ? 'border-amber-400 bg-amber-50/70 dark:border-amber-500 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-700' }}">
                                            <span class="font-medium">{{ $item['name'] }}</span>
                                            <span class="ml-1 text-[11px] uppercase tracking-wide {{ $item['type'] === 'activity' ? 'text-emerald-600 dark:text-emerald-400' : ($item['type'] === 'fnb' ? 'text-amber-600 dark:text-amber-400' : 'text-indigo-600 dark:text-indigo-400') }}">{{ $item['type'] === 'fnb' ? 'F&B' : $item['type'] }}</span>
                                            @if ($isMainExperience)
                                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">Main Experience</span>
                                            @endif
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $item['location'] ?? '-' }}
                                                |
                                                {{ $item['start_time'] ? substr((string) $item['start_time'], 0, 5) : '--:--' }}
                                                -
                                                {{ $item['end_time'] ? substr((string) $item['end_time'], 0, 5) : '--:--' }}
                                            </div>
                                            <x-rich-text :content="$item['description'] ?? null" class="mt-1 text-xs text-gray-600 dark:text-gray-300" />
                                            @if ($item['type'] === 'activity')
                                                @php
                                                    $itemIncludeText = \App\Support\SafeRichText::plainText($item['includes'] ?? null);
                                                    $itemExcludeText = \App\Support\SafeRichText::plainText($item['excludes'] ?? null);
                                                @endphp
                                                <div class="mt-1 space-y-0.5 text-[11px] text-gray-600 dark:text-gray-300">
                                                    @if (filled($itemIncludeText))
                                                        <p><span class="font-semibold">Includes:</span></p>
                                                        <x-rich-text :content="$item['includes'] ?? null" class="text-[11px]" />
                                                    @endif
                                                    @if (filled($itemExcludeText))
                                                        <p><span class="font-semibold">Excludes:</span></p>
                                                        <x-rich-text :content="$item['excludes'] ?? null" class="text-[11px]" />
                                                    @endif
                                                    <p>
                                                        <span class="font-semibold">Benefits:</span>
                                                        {{ \App\Support\SafeRichText::plainText($item['benefits'] ?? null) ?: '-' }}
                                                    </p>
                                                </div>
                                            @endif
                                            @if ($item['type'] === 'fnb')
                                                <div class="mt-1 space-y-0.5 text-[11px] text-gray-600 dark:text-gray-300">
                                                    <p><span class="font-semibold">Meal:</span> {{ $item['meal_period'] ?: '-' }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </li>
                                @empty
                                @endforelse
                                <li class="flex items-start gap-0">
                                    <div class="timeline-node-col w-10 flex flex-col items-center">
                                        <span
                                             class="timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold text-white {{ $endPointType === 'airport' ? 'bg-sky-600' : 'bg-teal-600' }}">
                                            <i class="{{ $endPointType === 'airport' ? 'fa-solid fa-plane-arrival' : 'fa-solid fa-bed' }}"></i>
                                        </span>
                                    </div>
                                    <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                    <div class="ml-2 flex-1 rounded-lg border border-gray-200 px-2 py-1 dark:border-gray-700">
                                        <span class="font-medium">{{ $endPointLabel ?: 'Not set' }}</span>
                                        <span class="ml-1 text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">End Point</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $endPointLocation ?: '-' }}
                                            | End Tour: {{ $dayEndTime ?? '--:--' }}
                                        </div>
                                        @if ($dayItems->isEmpty())
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No schedule item.</p>
                                        @endif
                                    </div>
                                </li>
                            </ul>
                            @php
                                $dayIncludeText = \App\Support\SafeRichText::plainText($dayPoint?->day_include);
                                $dayExcludeText = \App\Support\SafeRichText::plainText($dayPoint?->day_exclude);
                            @endphp
                            @if (filled($dayIncludeText) || filled($dayExcludeText))
                                <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                                    @if (filled($dayIncludeText))
                                        <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50/60 px-2 py-1 dark:border-emerald-800 dark:bg-emerald-900/20">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Day Include</p>
                                            <x-rich-text :content="$dayPoint?->day_include" class="mt-0.5 text-xs text-emerald-900 dark:text-emerald-100" />
                                        </div>
                                    @endif
                                    @if (filled($dayExcludeText))
                                        <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50/60 px-2 py-1 dark:border-rose-800 dark:bg-rose-900/20">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Day Exclude</p>
                                            <x-rich-text :content="$dayPoint?->day_exclude" class="mt-0.5 text-xs text-rose-900 dark:text-rose-100" />
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
            <div class="app-card min-w-0 h-fit p-4 lg:col-span-5 lg:self-start">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Itinerary Map</h2>
                <div id="itinerary-show-map" class="mt-3 h-[520px] md:h-[640px] w-full rounded-lg border border-gray-300 dark:border-gray-700"></div>
                <div class="mt-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Display By Day</p>
                    <div id="itinerary-day-controls" class="mt-2 flex flex-wrap gap-2">
                        <button type="button" data-day="" class="itinerary-day-filter-btn btn-primary-sm">All Days</button>
                        @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                            <button type="button" data-day="{{ $day }}" class="itinerary-day-filter-btn btn-outline-sm">Day {{ $day }}</button>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $dayPointByDayForMap = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
        $resolveMapPoint = function ($dayPoint, string $scope, ?array $previousEnd = null) {
            if (!$dayPoint) {
                return null;
            }
            if ($scope === 'start') {
                $type = (string) ($dayPoint->start_point_type ?? '');
                if ($type === 'previous_day_end') {
                    return $previousEnd;
                }
                if ($type === 'airport' && $dayPoint->startAirport) {
                    return [
                        'type' => 'airport',
                        'name' => (string) ($dayPoint->startAirport->name ?? 'Start Point'),
                        'location' => (string) ($dayPoint->startAirport->location ?? '-'),
                        'lat' => $dayPoint->startAirport->latitude,
                        'lng' => $dayPoint->startAirport->longitude,
                    ];
                }
                if ($type === 'hotel' && $dayPoint->startHotel) {
                    return [
                        'type' => 'hotel',
                        'name' => (string) ($dayPoint->startHotel->name ?? 'Start Point'),
                        'location' => (string) ($dayPoint->startHotel->address ?? '-'),
                        'lat' => $dayPoint->startHotel->latitude,
                        'lng' => $dayPoint->startHotel->longitude,
                    ];
                }
                return null;
            }
            $type = (string) ($dayPoint->end_point_type ?? '');
            if ($type === 'airport' && $dayPoint->endAirport) {
                return [
                    'type' => 'airport',
                    'name' => (string) ($dayPoint->endAirport->name ?? 'End Point'),
                    'location' => (string) ($dayPoint->endAirport->location ?? '-'),
                    'lat' => $dayPoint->endAirport->latitude,
                    'lng' => $dayPoint->endAirport->longitude,
                ];
            }
            if ($type === 'hotel' && $dayPoint->endHotel) {
                return [
                    'type' => 'hotel',
                    'name' => (string) ($dayPoint->endHotel->name ?? 'End Point'),
                    'location' => (string) ($dayPoint->endHotel->address ?? '-'),
                    'lat' => $dayPoint->endHotel->latitude,
                    'lng' => $dayPoint->endHotel->longitude,
                ];
            }
            return null;
        };

        $mapPoints = collect();
        $previousEndCoordinates = null;
        for ($day = 1; $day <= (int) $itinerary->duration_days; $day++) {
            $dayPoint = $dayPointByDayForMap[$day] ?? null;
            $startData = $resolveMapPoint($dayPoint, 'start', $previousEndCoordinates);
            $endData = $resolveMapPoint($dayPoint, 'end');

            if ($startData && is_numeric($startData['lat'] ?? null) && is_numeric($startData['lng'] ?? null)) {
                $mapPoints->push([
                    'type' => $startData['type'],
                    'name' => $startData['name'],
                    'location' => $startData['location'],
                    'lat' => (float) $startData['lat'],
                    'lng' => (float) $startData['lng'],
                    'day_number' => $day,
                    'visit_order' => 0,
                    'travel_minutes_to_next' => $dayPoint && $dayPoint->day_start_travel_minutes !== null
                        ? max(0, (int) $dayPoint->day_start_travel_minutes)
                        : null,
                ]);
            }

            foreach (($dayGroups[$day] ?? collect()) as $attraction) {
                if (!is_numeric($attraction->latitude ?? null) || !is_numeric($attraction->longitude ?? null)) {
                    continue;
                }
                $mapPoints->push([
                    'type' => 'attraction',
                    'name' => (string) ($attraction->name ?? '-'),
                    'location' => (string) ($attraction->location ?? '-'),
                    'lat' => (float) $attraction->latitude,
                    'lng' => (float) $attraction->longitude,
                    'day_number' => $day,
                    'visit_order' => (int) ($attraction->pivot->visit_order ?? 999999),
                    'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next !== null
                        ? max(0, (int) $attraction->pivot->travel_minutes_to_next)
                        : null,
                ]);
            }
            foreach (($activityDayGroups[$day] ?? collect()) as $activityItem) {
                $lat = $activityItem->activity->vendor->latitude ?? null;
                $lng = $activityItem->activity->vendor->longitude ?? null;
                if (!is_numeric($lat) || !is_numeric($lng)) {
                    continue;
                }
                $mapPoints->push([
                    'type' => 'activity',
                    'name' => (string) ($activityItem->activity->name ?? '-'),
                    'location' => (string) ($activityItem->activity->vendor->location ?? '-'),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'day_number' => $day,
                    'visit_order' => (int) ($activityItem->visit_order ?? 999999),
                    'travel_minutes_to_next' => $activityItem->travel_minutes_to_next !== null
                        ? max(0, (int) $activityItem->travel_minutes_to_next)
                        : null,
                ]);
            }
            foreach (($foodBeverageDayGroups[$day] ?? collect()) as $foodBeverageItem) {
                $lat = $foodBeverageItem->foodBeverage->vendor->latitude ?? null;
                $lng = $foodBeverageItem->foodBeverage->vendor->longitude ?? null;
                if (!is_numeric($lat) || !is_numeric($lng)) {
                    continue;
                }
                $mapPoints->push([
                    'type' => 'fnb',
                    'name' => (string) ($foodBeverageItem->foodBeverage->name ?? '-'),
                    'location' => (string) ($foodBeverageItem->foodBeverage->vendor->location ?? '-'),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'day_number' => $day,
                    'visit_order' => (int) ($foodBeverageItem->visit_order ?? 999999),
                    'travel_minutes_to_next' => $foodBeverageItem->travel_minutes_to_next !== null
                        ? max(0, (int) $foodBeverageItem->travel_minutes_to_next)
                        : null,
                ]);
            }

            if ($endData && is_numeric($endData['lat'] ?? null) && is_numeric($endData['lng'] ?? null)) {
                $mapPoints->push([
                    'type' => $endData['type'],
                    'name' => $endData['name'],
                    'location' => $endData['location'],
                    'lat' => (float) $endData['lat'],
                    'lng' => (float) $endData['lng'],
                    'day_number' => $day,
                    'visit_order' => 999999,
                    'travel_minutes_to_next' => null,
                ]);
            }

            $previousEndCoordinates = $endData ?: $previousEndCoordinates;
        }
    @endphp
    <style>
        .itinerary-show-map-marker-icon.itinerary-show-map-marker-active {
            transform: scale(1.15);
            filter: drop-shadow(0 0 6px rgba(245, 158, 11, 0.95));
            z-index: 1200 !important;
        }
        .itinerary-show-map-travel-badge {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.9);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            line-height: 1;
            padding: 4px 8px;
            border-radius: 9999px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            pointer-events: auto;
            cursor: pointer;
            user-select: none;
        }
        .itinerary-show-map-travel-badge::before {
            display: none;
        }
    </style>
    <script>
        (function () {
            const points = @json($mapPoints->sortBy(fn ($point) => ((int) $point['day_number'] * 1000000) + (int) $point['visit_order'])->values());
            let isInitialized = false;
            let leafletFallbackRequested = false;

            const requestLeafletFallback = () => {
                if (leafletFallbackRequested || typeof window.L !== 'undefined') return;
                leafletFallbackRequested = true;
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.crossOrigin = '';
                script.async = true;
                document.head.appendChild(script);
            };

            const initializeMap = () => {
                if (isInitialized) return true;
                const L = window.L;
                if (typeof L === 'undefined') return false;
            const mapElement = document.getElementById('itinerary-show-map');
                if (!mapElement) return false;

            const normalizeType = (type) => {
                const value = String(type || '').toLowerCase().trim();
                return ['attraction', 'activity', 'fnb', 'hotel', 'airport'].includes(value) ? value : 'attraction';
            };
            const iconByType = (type) => {
                const normalized = normalizeType(type);
                if (normalized === 'activity') return 'fa-solid fa-person-hiking';
                if (normalized === 'fnb') return 'fa-solid fa-utensils';
                if (normalized === 'airport') return 'fa-solid fa-plane';
                if (normalized === 'hotel') return 'fa-solid fa-bed';
                return 'fa-solid fa-location-dot';
            };
            const normalizeLatLng = (lat, lng) => {
                const parsedLat = Number(lat);
                const parsedLng = Number(lng);
                if (!Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) return null;
                if (Math.abs(parsedLat) > 90 || Math.abs(parsedLng) > 180) return null;
                return { lat: parsedLat, lng: parsedLng };
            };
            const toLatLng = (lat, lng) => {
                const normalized = normalizeLatLng(lat, lng);
                if (!normalized) return null;
                try {
                    return L.latLng(normalized.lat, normalized.lng);
                } catch (_) {
                    return null;
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const markerBadge = (order, type) => {
                const markerType = normalizeType(type);
                const color = markerType === 'activity'
                    ? '#059669'
                    : (markerType === 'fnb'
                        ? '#d97706'
                        : (markerType === 'airport'
                            ? '#0284c7'
                            : (markerType === 'hotel' ? '#0f766e' : '#1d4ed8')));
                return L.divIcon({
                    className: 'itinerary-show-map-marker-icon',
                    html: `<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:${color};color:#fff;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.25);font-size:12px;position:relative"><i class="${iconByType(markerType)}"></i><span style="position:absolute;right:-5px;bottom:-5px;min-width:14px;height:14px;border-radius:9999px;background:#111827;color:#fff;border:1px solid #fff;font-size:9px;line-height:14px;text-align:center;padding:0 3px">${order}</span></span>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });
            };

            const validPoints = (Array.isArray(points) ? points : [])
                .map((point) => {
                    const normalized = normalizeLatLng(point?.lat, point?.lng);
                    if (!normalized) return null;
                    return {
                        ...point,
                        lat: normalized.lat,
                        lng: normalized.lng,
                        day_number: Number(point?.day_number || 0),
                        visit_order: Number(point?.visit_order || 0),
                        type: normalizeType(point?.type),
                        travel_minutes_to_next: Number(point?.travel_minutes_to_next ?? 0),
                    };
                })
                .filter((point) => point && point.day_number > 0)
                .sort((a, b) => (a.day_number - b.day_number) || (a.visit_order - b.visit_order));

            const map = L.map(mapElement, {
                zoomControl: true,
                preferCanvas: false,
                renderer: L.svg(),
            }).setView([-2.5, 118], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);
            const mapDataLayer = L.featureGroup().addTo(map);
            const routePalette = ['#2563eb', '#16a34a', '#ea580c', '#db2777', '#7c3aed', '#0891b2'];

            const dayButtons = Array.from(document.querySelectorAll('.itinerary-day-filter-btn'));
            const allDays = [...new Set(validPoints.map((point) => Number(point.day_number)))].sort((a, b) => a - b);
            let selectedDay = null;
            let routeRenderToken = 0;
            let activeRouteFetchController = null;
            let mapBusy = false;
            let renderPendingAfterMove = false;
            const highlightedSegmentState = {
                markerElements: [],
                haloLayers: [],
            };

            map.on('zoomstart movestart', () => {
                mapBusy = true;
            });
            map.on('zoomend moveend', () => {
                mapBusy = false;
                if (renderPendingAfterMove) {
                    renderPendingAfterMove = false;
                    requestSafeRender(selectedDay);
                }
            });

            const fetchRoadRouteGeometry = async (latLngPoints, signal) => {
                if (!Array.isArray(latLngPoints) || latLngPoints.length < 2) return null;
                const coordinateString = latLngPoints
                    .map((point) => `${point.lng},${point.lat}`)
                    .join(';');
                const endpoint = `https://router.project-osrm.org/route/v1/driving/${coordinateString}?overview=full&geometries=geojson`;
                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: { Accept: 'application/json' },
                    signal,
                });
                if (!response.ok) return null;
                const payload = await response.json();
                const coordinates = payload?.routes?.[0]?.geometry?.coordinates;
                if (!Array.isArray(coordinates) || coordinates.length < 2) return null;
                const routePoints = [];
                coordinates.forEach((coord) => {
                    if (!Array.isArray(coord) || coord.length < 2) return;
                    const latLng = toLatLng(coord[1], coord[0]);
                    if (latLng) routePoints.push(latLng);
                });
                return routePoints.length >= 2 ? routePoints : null;
            };
            const fetchRoadRouteForDay = async (dayLatLngPoints, signal) => {
                if (!Array.isArray(dayLatLngPoints) || dayLatLngPoints.length < 2) return null;
                // Build road-following route segment-by-segment to avoid URL length limits.
                const mergedRoute = [];
                const segmentRoutes = [];
                for (let index = 0; index < dayLatLngPoints.length - 1; index += 1) {
                    const from = dayLatLngPoints[index];
                    const to = dayLatLngPoints[index + 1];
                    const segment = await fetchRoadRouteGeometry([from, to], signal);
                    if (!Array.isArray(segment) || segment.length < 2) {
                        return null;
                    }
                    segmentRoutes.push(segment);
                    if (mergedRoute.length === 0) {
                        mergedRoute.push(...segment);
                    } else {
                        mergedRoute.push(...segment.slice(1));
                    }
                }
                return mergedRoute.length >= 2 ? { mergedRoute, segmentRoutes } : null;
            };
            const midpointOfRoute = (routeCoords) => {
                if (!Array.isArray(routeCoords) || routeCoords.length === 0) return null;
                const index = Math.floor((routeCoords.length - 1) / 2);
                const point = routeCoords[index];
                return point && Number.isFinite(point.lat) && Number.isFinite(point.lng) ? point : null;
            };
            const clearSegmentHighlight = () => {
                highlightedSegmentState.markerElements.forEach((element) => {
                    try {
                        element.classList.remove('itinerary-show-map-marker-active');
                    } catch (_) {}
                });
                highlightedSegmentState.markerElements = [];
                highlightedSegmentState.haloLayers.forEach((layer) => {
                    try {
                        mapDataLayer.removeLayer(layer);
                    } catch (_) {}
                });
                highlightedSegmentState.haloLayers = [];
            };
            const highlightMarkerPair = (fromMarker, toMarker) => {
                clearSegmentHighlight();
                [fromMarker, toMarker].forEach((marker) => {
                    if (!marker) return;
                    const markerElement = marker.getElement?.();
                    if (markerElement) {
                        markerElement.classList.add('itinerary-show-map-marker-active');
                        highlightedSegmentState.markerElements.push(markerElement);
                    }
                    const latLng = marker.getLatLng?.();
                    if (!latLng) return;
                    const halo = L.circleMarker(latLng, {
                        radius: 18,
                        color: '#f59e0b',
                        weight: 3,
                        fillColor: '#fde68a',
                        fillOpacity: 0.18,
                        interactive: false,
                        bubblingMouseEvents: false,
                    }).addTo(mapDataLayer);
                    highlightedSegmentState.haloLayers.push(halo);
                });
            };

            const renderMarkers = async (day = null) => {
                const currentToken = ++routeRenderToken;
                try {
                    activeRouteFetchController?.abort();
                } catch (_) {}
                activeRouteFetchController = typeof AbortController !== 'undefined' ? new AbortController() : null;
                const routeSignal = activeRouteFetchController?.signal;
                mapDataLayer.clearLayers();
                clearSegmentHighlight();
                const activePoints = day === null
                    ? validPoints
                    : validPoints.filter((point) => Number(point.day_number) === Number(day));
                if (!activePoints.length) {
                    map.setView([-2.5, 118], 4);
                    return;
                }

                const bounds = [];
                const dayCounter = {};
                const dayMarkers = {};
                activePoints.forEach((point) => {
                    dayCounter[point.day_number] = (dayCounter[point.day_number] || 0) + 1;
                    const index = dayCounter[point.day_number];
                    const latLng = toLatLng(point.lat, point.lng);
                    if (!latLng) return;
                    bounds.push([latLng.lat, latLng.lng]);
                    const marker = L.marker(latLng, { icon: markerBadge(index, point.type) }).addTo(mapDataLayer);
                    marker.bindPopup(`#${index} | Day ${point.day_number} | ${escapeHtml(point.name || '-')}<br>${escapeHtml(point.location || '-')}`);
                    const dayKey = String(point.day_number);
                    if (!dayMarkers[dayKey]) dayMarkers[dayKey] = [];
                    dayMarkers[dayKey].push(marker);
                });

                const pointsByDay = activePoints.reduce((carry, point) => {
                    const key = String(point.day_number);
                    if (!carry[key]) carry[key] = [];
                    carry[key].push(point);
                    return carry;
                }, {});
                const orderedDays = Object.keys(pointsByDay)
                    .map(Number)
                    .sort((a, b) => a - b);
                for (const dayNumber of orderedDays) {
                    const dayPointEntries = pointsByDay[String(dayNumber)]
                        .slice()
                        .sort((a, b) => (a.visit_order - b.visit_order));
                    const dayLatLngPoints = dayPointEntries
                        .map((point) => toLatLng(point.lat, point.lng))
                        .filter((point) => point && Number.isFinite(point.lat) && Number.isFinite(point.lng));
                    if (dayLatLngPoints.length < 2) continue;

                    const color = routePalette[(dayNumber - 1) % routePalette.length];
                    let roadRouteData = null;
                    try {
                        roadRouteData = await fetchRoadRouteForDay(dayLatLngPoints, routeSignal);
                        if (currentToken !== routeRenderToken) return;
                    } catch (_) {}
                    if (!roadRouteData || !Array.isArray(roadRouteData.mergedRoute) || roadRouteData.mergedRoute.length < 2) {
                        // Explicitly skip drawing if road route cannot be resolved.
                        continue;
                    }

                    L.polyline(roadRouteData.mergedRoute, {
                        color,
                        weight: 4,
                        opacity: 0.9,
                        lineJoin: 'round',
                        lineCap: 'round',
                        interactive: false,
                        bubblingMouseEvents: false,
                    }).addTo(mapDataLayer);

                    for (let index = 0; index < dayPointEntries.length - 1; index += 1) {
                        const minutes = Math.max(0, Math.round(Number(dayPointEntries[index]?.travel_minutes_to_next ?? 0)));
                        if (!Number.isFinite(minutes) || minutes <= 0) continue;
                        const segmentRoute = roadRouteData.segmentRoutes?.[index] ?? null;
                        const labelLatLng = midpointOfRoute(segmentRoute);
                        if (!labelLatLng) continue;
                        const dayMarkerList = dayMarkers[String(dayNumber)] || [];
                        const fromMarker = dayMarkerList[index] || null;
                        const toMarker = dayMarkerList[index + 1] || null;
                        const durationLabel = L.tooltip({
                            permanent: true,
                            direction: 'top',
                            offset: [0, -8],
                            className: 'itinerary-show-map-travel-badge',
                            interactive: true,
                        })
                            .setLatLng(labelLatLng)
                            .setContent(`${minutes}m`)
                            .addTo(mapDataLayer);
                        durationLabel.on('click', () => {
                            highlightMarkerPair(fromMarker, toMarker);
                        });
                    }
                }

                const safeBounds = bounds.filter((coord) =>
                    Array.isArray(coord) &&
                    coord.length === 2 &&
                    Number.isFinite(coord[0]) &&
                    Number.isFinite(coord[1])
                );
                if (safeBounds.length === 0) {
                    map.setView([-2.5, 118], 4);
                } else if (safeBounds.length === 1) {
                    map.setView(safeBounds[0], 13);
                } else {
                    map.fitBounds(safeBounds, { padding: [24, 24] });
                }
            };
            const canRenderMapNow = () => {
                if (!mapElement.isConnected) return false;
                const rect = mapElement.getBoundingClientRect();
                return Number.isFinite(rect.width) && Number.isFinite(rect.height) && rect.width > 8 && rect.height > 8;
            };
            const requestSafeRender = async (day = null, retry = 0) => {
                if (!canRenderMapNow()) {
                    if (retry >= 8) return;
                    window.setTimeout(() => {
                        requestSafeRender(day, retry + 1);
                    }, 120);
                    return;
                }
                if (mapBusy) {
                    renderPendingAfterMove = true;
                    return;
                }
                map.invalidateSize(false);
                await renderMarkers(day);
            };

            const setActiveButton = (day = null) => {
                dayButtons.forEach((button) => {
                    const raw = String(button.dataset.day || '').trim();
                    const current = raw === '' ? null : Number(raw);
                    const active = day === null ? current === null : current === day;
                    button.classList.toggle('btn-primary-sm', active);
                    button.classList.toggle('btn-outline-sm', !active);
                });
            };

            dayButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const raw = String(button.dataset.day || '').trim();
                    const parsed = Number(raw);
                    selectedDay = raw === '' || !Number.isFinite(parsed) || parsed < 1 ? null : parsed;
                    setActiveButton(selectedDay);
                    await requestSafeRender(selectedDay);
                });
            });

            if (!allDays.length) {
                map.invalidateSize(false);
                map.setView([-2.5, 118], 4);
                    isInitialized = true;
                    return true;
            }
            setActiveButton(selectedDay);
            window.setTimeout(() => {
                requestSafeRender(selectedDay);
            }, 0);
            map.whenReady(() => {
                requestSafeRender(selectedDay);
            });
            window.addEventListener('resize', () => {
                requestSafeRender(selectedDay);
            });
                isInitialized = true;
                return true;
            };

            const bootWhenReady = (attempt = 0) => {
                if (initializeMap()) return;
                if (attempt === 10) {
                    requestLeafletFallback();
                }
                if (attempt >= 60) {
                    console.error('Itinerary map failed to initialize: Leaflet is not ready.');
                    return;
                }
                window.setTimeout(() => {
                    bootWhenReady(attempt + 1);
                }, 100);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => bootWhenReady(), { once: true });
            } else {
                bootWhenReady();
            }
        })();
    </script>
@endpush

