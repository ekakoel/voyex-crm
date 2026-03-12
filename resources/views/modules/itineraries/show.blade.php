@extends('layouts.master')

@section('content')
    <div class="space-y-6 itinerary-show-page">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @section('page_actions')@if (Route::has('quotations.create') && auth()->user()->can('module.quotations.access') && ! $itinerary->quotation)
                        <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}" class="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/20">Generate Quotation</a>
                    @endif
                    <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'stream']) }}" target="_blank" rel="noopener" class="rounded-lg border border-sky-300 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50 dark:border-sky-700 dark:text-sky-300 dark:hover:bg-sky-900/20">Preview PDF</a>
                    <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'download']) }}" target="_blank" rel="noopener" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">Download PDF</a>
                    @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved'))
                        <a href="{{ route('itineraries.edit', $itinerary) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Edit</a>
                    @endif
                    <a href="{{ route('itineraries.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Back</a>@endsection
            <div class="mt-3 grid grid-cols-1 gap-2 text-sm md:grid-cols-2">
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
        </div>

        @if ($itinerary->description)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <x-rich-text :content="$itinerary->description" class="text-sm text-gray-700 dark:text-gray-200" />
            </div>
        @endif

        @if ($itinerary->accommodations->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Accommodations</h2>
                @php
                    $dayPointByDayForAccommodation = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
                @endphp
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($itinerary->accommodations as $accommodation)
                        @php
                            $stayDay = (int) ($accommodation->pivot->day_number ?? 1);
                            $stayPoint = $dayPointByDayForAccommodation[$stayDay] ?? null;
                            if ($stayPoint && (int) ($stayPoint->end_accommodation_id ?? 0) !== (int) $accommodation->id) {
                                $stayPoint = null;
                            }
                            $roomName = (string) ($stayPoint?->endAccommodationRoom?->name ?? '');
                            $roomType = (string) ($stayPoint?->endAccommodationRoom?->room_type ?? '');
                        @endphp
                        <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $accommodation->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $accommodation->category ?: '-' }}
                                @if(!is_null($accommodation->star_rating))
                                    | {{ $accommodation->star_rating }} star
                                @endif
                                | {{ $accommodation->city ?: '-' }}
                            </p>
                            @if ($roomName !== '')
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                    Room: {{ $roomName }}{{ $roomType !== '' ? ' ('.$roomType.')' : '' }}
                                </p>
                            @endif
                            <p class="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-300">
                                Day {{ $accommodation->pivot->day_number ?? 1 }} | {{ $accommodation->pivot->night_count ?? 1 }} night(s)
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-7">
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
                                if ($type === 'accommodation') {
                                    $accommodationName = (string) ($dayPoint->startAccommodation?->name ?? 'Not set');
                                    $roomName = (string) ($dayPoint->startAccommodationRoom?->name ?? '');
                                    if ($accommodationName === 'Not set') {
                                        return 'Not set';
                                    }
                                    if ($roomName !== '') {
                                        return $accommodationName . ' - ' . $roomName;
                                    }
                                    return $accommodationName;
                                }
                                return 'Not set';
                            }
                            $type = (string) ($dayPoint->end_point_type ?? '');
                            if ($type === 'airport') {
                                return $dayPoint->endAirport?->name ?: 'Not set';
                            }
                            if ($type === 'accommodation') {
                                $accommodationName = (string) ($dayPoint->endAccommodation?->name ?? 'Not set');
                                $roomName = (string) ($dayPoint->endAccommodationRoom?->name ?? '');
                                if ($accommodationName === 'Not set') {
                                    return 'Not set';
                                }
                                if ($roomName !== '') {
                                    return $accommodationName . ' - ' . $roomName;
                                }
                                return $accommodationName;
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
                                if ($type === 'accommodation') {
                                    return $dayPoint->startAccommodation?->location;
                                }
                                return null;
                            }
                            $type = (string) ($dayPoint->end_point_type ?? '');
                            if ($type === 'airport') {
                                return $dayPoint->endAirport?->location;
                            }
                            if ($type === 'accommodation') {
                                return $dayPoint->endAccommodation?->location;
                            }
                            return null;
                        };
                    @endphp
                    @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                        @php
                            $attractions = ($dayGroups[$day] ?? collect())->map(function ($attraction) {
                                return [
                                    'type' => 'attraction',
                                    'experience_id' => (int) $attraction->id,
                                    'marker_key' => 'attraction-'.$attraction->id.'-'.((int) ($attraction->pivot->day_number ?? 1)).'-'.((int) ($attraction->pivot->visit_order ?? 999999)),
                                    'name' => $attraction->name,
                                    'location' => $attraction->location,
                                    'description' => $attraction->description,
                                    'pax' => null,
                                    'start_time' => $attraction->pivot->start_time,
                                    'end_time' => $attraction->pivot->end_time,
                                    'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next,
                                    'visit_order' => $attraction->pivot->visit_order ?? 999999,
                                ];
                            });
                            $activities = ($activityDayGroups[$day] ?? collect())->map(function ($activityItem) {
                                $activity = $activityItem->activity;
                                return [
                                    'type' => 'activity',
                                    'experience_id' => (int) ($activityItem->activity_id ?? 0),
                                    'marker_key' => 'activity-'.$activityItem->id.'-'.((int) ($activityItem->day_number ?? 1)).'-'.((int) ($activityItem->visit_order ?? 999999)),
                                    'name' => $activity->name ?? '-',
                                    'location' => $activity->vendor->location ?? null,
                                    'description' => $activity->notes ?? null,
                                    'includes' => $activity->includes ?? null,
                                    'excludes' => $activity->excludes ?? null,
                                    'benefits' => $activity->benefits ?? null,
                                    'pax' => $activityItem->pax,
                                    'start_time' => $activityItem->start_time,
                                    'end_time' => $activityItem->end_time,
                                    'travel_minutes_to_next' => $activityItem->travel_minutes_to_next,
                                    'visit_order' => $activityItem->visit_order ?? 999999,
                                ];
                            });
                            $foodBeverages = ($foodBeverageDayGroups[$day] ?? collect())->map(function ($foodBeverageItem) {
                                $foodBeverage = $foodBeverageItem->foodBeverage;
                                return [
                                    'type' => 'fnb',
                                    'experience_id' => (int) ($foodBeverageItem->food_beverage_id ?? 0),
                                    'marker_key' => 'fnb-'.$foodBeverageItem->id.'-'.((int) ($foodBeverageItem->day_number ?? 1)).'-'.((int) ($foodBeverageItem->visit_order ?? 999999)),
                                    'name' => $foodBeverage->name ?? '-',
                                    'location' => $foodBeverage->vendor->location ?? null,
                                    'description' => $foodBeverage->notes ?? $foodBeverage->menu_highlights ?? null,
                                    'meal_period' => $foodBeverage->meal_period ?? null,
                                    'service_type' => $foodBeverage->service_type ?? null,
                                    'agent_price' => $foodBeverage->agent_price ?? null,
                                    'currency' => $foodBeverage->currency ?? 'IDR',
                                    'pax' => $foodBeverageItem->pax,
                                    'start_time' => $foodBeverageItem->start_time,
                                    'end_time' => $foodBeverageItem->end_time,
                                    'travel_minutes_to_next' => $foodBeverageItem->travel_minutes_to_next,
                                    'visit_order' => $foodBeverageItem->visit_order ?? 999999,
                                ];
                            });
                            $dayItems = $attractions->merge($activities)->merge($foodBeverages)->sortBy('visit_order')->values();
                            $dayTransport = $transportUnitByDay[$day] ?? null;
                            $dayPoint = $dayPointByDay[$day] ?? null;
                            $previousDayPoint = $dayPointByDay[$day - 1] ?? null;
                            $previousEndLabel = $resolvePointLabel($previousDayPoint, 'end');
                            $previousEndLocation = $resolvePointLocation($previousDayPoint, 'end');
                            $startPointTypeRaw = (string) ($dayPoint->start_point_type ?? '');
                            $startPointType = $startPointTypeRaw === 'previous_day_end'
                                ? (string) ($previousDayPoint->end_point_type ?? 'accommodation')
                                : $startPointTypeRaw;
                            if (!in_array($startPointType, ['airport', 'accommodation'], true)) {
                                $startPointType = 'accommodation';
                            }
                            $endPointType = (string) ($dayPoint->end_point_type ?? 'accommodation');
                            if (!in_array($endPointType, ['airport', 'accommodation'], true)) {
                                $endPointType = 'accommodation';
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
                                        <button
                                            type="button"
                                            class="schedule-item-index-btn timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold text-white {{ $startPointType === 'airport' ? 'bg-sky-600' : 'bg-teal-600' }}"
                                            data-day="{{ $day }}"
                                            data-marker-key="start-point-day-{{ $day }}"
                                            title="Lihat Start Point di map">
                                            <i class="{{ $startPointType === 'airport' ? 'fa-solid fa-plane' : 'fa-solid fa-bed' }}"></i>
                                        </button>
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
                                            <button
                                                type="button"
                                                class="schedule-item-index-btn timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold text-white {{ $item['type'] === 'activity' ? 'bg-emerald-600' : ($item['type'] === 'fnb' ? 'bg-amber-600' : 'bg-indigo-600') }}"
                                                data-day="{{ $day }}"
                                                data-marker-key="{{ $item['marker_key'] ?? '' }}"
                                                title="Lihat di map">
                                                <i class="{{ $item['type'] === 'activity' ? 'fa-solid fa-person-hiking' : ($item['type'] === 'fnb' ? 'fa-solid fa-utensils' : 'fa-solid fa-location-dot') }}"></i>
                                            </button>
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
                                        <button
                                            type="button"
                                            class="schedule-item-index-btn timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold text-white {{ $endPointType === 'airport' ? 'bg-sky-600' : 'bg-teal-600' }}"
                                            data-day="{{ $day }}"
                                            data-marker-key="end-point-day-{{ $day }}"
                                            title="Lihat End Point di map">
                                            <i class="{{ $endPointType === 'airport' ? 'fa-solid fa-plane-arrival' : 'fa-solid fa-bed' }}"></i>
                                        </button>
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
                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 px-2 py-1 dark:border-emerald-800 dark:bg-emerald-900/20">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Day Include</p>
                                            <x-rich-text :content="$dayPoint?->day_include" class="mt-0.5 text-xs text-emerald-900 dark:text-emerald-100" />
                                        </div>
                                    @endif
                                    @if (filled($dayExcludeText))
                                        <div class="rounded-lg border border-rose-200 bg-rose-50/60 px-2 py-1 dark:border-rose-800 dark:bg-rose-900/20">
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
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Itinerary Map</h2>
                <div id="itinerary-show-map" class="mt-3 h-[520px] md:h-[640px] w-full rounded-lg border border-gray-300"></div>
                <div class="mt-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Display By Day</p>
                    <div id="itinerary-day-controls" class="mt-2 flex flex-wrap gap-2">
                        <button type="button" data-day="" class="day-filter-btn rounded-lg border border-indigo-500 bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 hover:text-white">All Days</button>
                        @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                            <button type="button" data-day="{{ $day }}" class="day-filter-btn rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-white">Day {{ $day }}</button>
                        @endfor
                    </div>
                </div>
                <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50/70 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Visible Items</p>
                    <ul id="itinerary-map-item-list" class="mt-2 space-y-1.5 text-xs text-gray-700 dark:text-gray-200"></ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @php
        $mapPoints = collect()
            ->merge($itinerary->touristAttractions->map(function ($attraction) {
                return [
                    'type' => 'attraction',
                    'marker_key' => 'attraction-'.$attraction->id.'-'.((int) ($attraction->pivot->day_number ?? 1)).'-'.((int) ($attraction->pivot->visit_order ?? 1)),
                    'name' => $attraction->name,
                    'location' => $attraction->location,
                    'lat' => $attraction->latitude,
                    'lng' => $attraction->longitude,
                    'day_number' => $attraction->pivot->day_number ?? 1,
                    'start_time' => $attraction->pivot->start_time,
                    'end_time' => $attraction->pivot->end_time,
                    'visit_order' => $attraction->pivot->visit_order ?? 1,
                ];
            })->values())
            ->merge($itinerary->itineraryActivities->map(function ($activityItem) {
                return [
                    'type' => 'activity',
                    'marker_key' => 'activity-'.$activityItem->id.'-'.((int) ($activityItem->day_number ?? 1)).'-'.((int) ($activityItem->visit_order ?? 1)),
                    'name' => $activityItem->activity->name ?? '-',
                    'location' => $activityItem->activity->vendor->location ?? null,
                    'lat' => $activityItem->activity->vendor->latitude ?? null,
                    'lng' => $activityItem->activity->vendor->longitude ?? null,
                    'day_number' => $activityItem->day_number ?? 1,
                    'start_time' => $activityItem->start_time,
                    'end_time' => $activityItem->end_time,
                    'visit_order' => $activityItem->visit_order ?? 1,
                ];
            })->values())
            ->merge($itinerary->itineraryFoodBeverages->map(function ($foodBeverageItem) {
                return [
                    'type' => 'fnb',
                    'marker_key' => 'fnb-'.$foodBeverageItem->id.'-'.((int) ($foodBeverageItem->day_number ?? 1)).'-'.((int) ($foodBeverageItem->visit_order ?? 1)),
                    'name' => $foodBeverageItem->foodBeverage->name ?? '-',
                    'location' => $foodBeverageItem->foodBeverage->vendor->location ?? null,
                    'lat' => $foodBeverageItem->foodBeverage->vendor->latitude ?? null,
                    'lng' => $foodBeverageItem->foodBeverage->vendor->longitude ?? null,
                    'day_number' => $foodBeverageItem->day_number ?? 1,
                    'start_time' => $foodBeverageItem->start_time,
                    'end_time' => $foodBeverageItem->end_time,
                    'visit_order' => $foodBeverageItem->visit_order ?? 1,
                ];
            })->values());

        $dayPointByDayForMap = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
        $resolvePointCoordinates = function ($dayPoint, string $scope, ?array $previousEnd = null) {
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
                if ($type === 'accommodation' && $dayPoint->startAccommodation) {
                    $accommodationName = (string) ($dayPoint->startAccommodation->name ?? 'Start Point');
                    $roomName = (string) ($dayPoint->startAccommodationRoom?->name ?? '');
                    $pointName = $roomName !== ''
                        ? ($accommodationName . ' - ' . $roomName)
                        : $accommodationName;
                    return [
                        'type' => 'accommodation',
                        'name' => $pointName,
                        'location' => (string) ($dayPoint->startAccommodation->location ?? '-'),
                        'lat' => $dayPoint->startAccommodation->latitude,
                        'lng' => $dayPoint->startAccommodation->longitude,
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
            if ($type === 'accommodation' && $dayPoint->endAccommodation) {
                $accommodationName = (string) ($dayPoint->endAccommodation->name ?? 'End Point');
                $roomName = (string) ($dayPoint->endAccommodationRoom?->name ?? '');
                $pointName = $roomName !== ''
                    ? ($accommodationName . ' - ' . $roomName)
                    : $accommodationName;
                return [
                    'type' => 'accommodation',
                    'name' => $pointName,
                    'location' => (string) ($dayPoint->endAccommodation->location ?? '-'),
                    'lat' => $dayPoint->endAccommodation->latitude,
                    'lng' => $dayPoint->endAccommodation->longitude,
                ];
            }
            return null;
        };

        $dayPointMarkers = collect();
        $toMinutesForMap = function (?string $time): ?int {
            $value = substr((string) $time, 0, 5);
            if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
                return null;
            }
            return ((int) substr($value, 0, 2) * 60) + (int) substr($value, 3, 2);
        };
        $fromMinutesForMap = function (?int $minutes): ?string {
            if (!is_int($minutes)) {
                return null;
            }
            $normalized = max(0, $minutes);
            return str_pad((string) ((int) floor($normalized / 60)), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) ($normalized % 60), 2, '0', STR_PAD_LEFT);
        };
        $previousEndCoordinates = null;
        for ($day = 1; $day <= (int) $itinerary->duration_days; $day++) {
            $dayPoint = $dayPointByDayForMap[$day] ?? null;
            $startData = $resolvePointCoordinates($dayPoint, 'start', $previousEndCoordinates);
            $endData = $resolvePointCoordinates($dayPoint, 'end');
            $dayAttractions = $itinerary->touristAttractions
                ->filter(fn ($attraction) => (int) ($attraction->pivot->day_number ?? 0) === $day)
                ->map(fn ($attraction) => [
                    'end_time' => $attraction->pivot->end_time ? substr((string) $attraction->pivot->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next,
                    'visit_order' => (int) ($attraction->pivot->visit_order ?? 999999),
                ]);
            $dayActivities = $itinerary->itineraryActivities
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(fn ($item) => [
                    'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => $item->travel_minutes_to_next,
                    'visit_order' => (int) ($item->visit_order ?? 999999),
                ]);
            $dayFoodBeverages = $itinerary->itineraryFoodBeverages
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(fn ($item) => [
                    'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => $item->travel_minutes_to_next,
                    'visit_order' => (int) ($item->visit_order ?? 999999),
                ]);
            $dayScheduleItems = collect($dayAttractions)
                ->merge(collect($dayActivities))
                ->merge(collect($dayFoodBeverages))
                ->sortBy('visit_order')
                ->values();
            $lastDayItem = $dayScheduleItems->last();
            $lastEndBaseMinutes = $lastDayItem ? $toMinutesForMap($lastDayItem['end_time'] ?? null) : null;
            $lastTravelToEnd = $lastDayItem ? max(0, (int) ($lastDayItem['travel_minutes_to_next'] ?? 0)) : 0;
            $dayEndTourTime = $lastEndBaseMinutes !== null ? $fromMinutesForMap($lastEndBaseMinutes + $lastTravelToEnd) : null;

            if ($startData && is_numeric($startData['lat'] ?? null) && is_numeric($startData['lng'] ?? null)) {
                $dayPointMarkers->push([
                    'type' => $startData['type'],
                    'point_role' => 'start',
                    'marker_key' => 'start-point-day-'.$day,
                    'name' => $startData['name'],
                    'location' => $startData['location'],
                    'lat' => (float) $startData['lat'],
                    'lng' => (float) $startData['lng'],
                    'day_number' => $day,
                    'start_time' => $dayPoint && $dayPoint->day_start_time ? substr((string) $dayPoint->day_start_time, 0, 5) : null,
                    'end_time' => $dayPoint && $dayPoint->day_start_time ? substr((string) $dayPoint->day_start_time, 0, 5) : null,
                    'visit_order' => 0,
                ]);
            }
            if ($endData && is_numeric($endData['lat'] ?? null) && is_numeric($endData['lng'] ?? null)) {
                $dayPointMarkers->push([
                    'type' => $endData['type'],
                    'point_role' => 'end',
                    'marker_key' => 'end-point-day-'.$day,
                    'name' => $endData['name'],
                    'location' => $endData['location'],
                    'lat' => (float) $endData['lat'],
                    'lng' => (float) $endData['lng'],
                    'day_number' => $day,
                    'start_time' => $dayEndTourTime,
                    'end_time' => $dayEndTourTime,
                    'visit_order' => 999999,
                ]);
            }
            $previousEndCoordinates = $endData ?: $previousEndCoordinates;
        }

        $mapPoints = $mapPoints->merge($dayPointMarkers)->values();
    @endphp
    <script>
        (function () {
            const points = @json($mapPoints);

            const validPoints = points.filter((point) =>
                typeof point.lat === 'number' && Number.isFinite(point.lat) &&
                typeof point.lng === 'number' && Number.isFinite(point.lng)
            ).sort((a, b) => (a.day_number - b.day_number) || (a.visit_order - b.visit_order));

            const mapElement = document.getElementById('itinerary-show-map');
            if (!mapElement || typeof L === 'undefined') return;

            const map = L.map(mapElement).setView([-6.2, 106.816666], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (!validPoints.length) return;

            const markerLayer = L.layerGroup().addTo(map);
            const routeLayers = [];
            const dayControls = document.getElementById('itinerary-day-controls');
            const dayButtons = dayControls ? Array.from(dayControls.querySelectorAll('.day-filter-btn')) : [];
            const scheduleIndexButtons = Array.from(document.querySelectorAll('.schedule-item-index-btn'));
                const mapItemList = document.getElementById('itinerary-map-item-list');
            const dayValues = [...new Set(validPoints.map((point) => Number(point.day_number)))].sort((a, b) => a - b);
            const markerLookup = new Map();
            let highlightedMarker = null;
            let highlightedMarkerOrder = null;
            let highlightedMarkerType = null;

            const markerTypeClass = (type) => {
                if (type === 'activity') return 'activity';
                if (type === 'fnb') return 'fnb';
                if (type === 'airport') return 'airport';
                if (type === 'accommodation') return 'accommodation';
                return 'attraction';
            };

            const markerTypeIcon = (type) => {
                if (type === 'activity') return 'fa-solid fa-person-hiking';
                if (type === 'fnb') return 'fa-solid fa-utensils';
                if (type === 'airport') return 'fa-solid fa-plane';
                if (type === 'accommodation') return 'fa-solid fa-bed';
                return 'fa-solid fa-location-dot';
            };

            const createBadgeIcon = (order, type, highlighted = false) => L.divIcon({
                className: '',
                html: `<div class="itinerary-marker-badge ${markerTypeClass(type)} ${highlighted ? 'is-highlighted' : ''}"><i class="${markerTypeIcon(type)}"></i><span class="itinerary-marker-number">${order}</span></div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            const groupedByDay = validPoints.reduce((carry, point) => {
                const key = String(point.day_number);
                carry[key] = carry[key] || [];
                carry[key].push(point);
                return carry;
            }, {});

            const dayColors = ['#2563eb', '#16a34a', '#ea580c', '#db2777', '#7c3aed', '#0891b2'];
            const clearRouteLayers = () => {
                routeLayers.forEach((layer) => map.removeLayer(layer));
                routeLayers.length = 0;
            };

            const setActiveButton = (selectedDay) => {
                dayButtons.forEach((button) => {
                    const isActive = (button.dataset.day || '') === (selectedDay === null ? '' : String(selectedDay));
                    button.classList.toggle('bg-indigo-600', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('border-indigo-500', isActive);
                    button.classList.toggle('hover:bg-indigo-700', isActive);
                    button.classList.toggle('hover:text-white', isActive);
                    button.classList.toggle('bg-white', !isActive);
                    button.classList.toggle('text-gray-700', !isActive);
                    button.classList.toggle('border-gray-300', !isActive);
                    button.classList.toggle('hover:bg-gray-100', !isActive);
                    button.classList.toggle('hover:text-gray-900', !isActive);
                    button.classList.toggle('dark:bg-gray-900', !isActive);
                    button.classList.toggle('dark:text-gray-200', !isActive);
                    button.classList.toggle('dark:border-gray-600', !isActive);
                    button.classList.toggle('dark:hover:bg-gray-700', !isActive);
                    button.classList.toggle('dark:hover:text-white', !isActive);
                });
            };

            const renderMapItemList = (items) => {
                if (!mapItemList) return;
                if (!items.length) {
                    mapItemList.innerHTML = '<li class="text-gray-500 dark:text-gray-400">No items available.</li>';
                    return;
                }
                mapItemList.innerHTML = items.map((item) => {
                    const start = item.start_time ? String(item.start_time).slice(0, 5) : '--:--';
                    const end = item.end_time ? String(item.end_time).slice(0, 5) : '--:--';
                    const pointRole = String(item.point_role || '');
                    const typeLabelMap = {
                        attraction: 'Attraction',
                        activity: 'Activity',
                        fnb: 'F&B',
                        airport: 'Airport',
                        accommodation: 'Accommodation',
                    };
                    const type = typeLabelMap[item.type] || 'Point';
                    const dayLabel = `Day ${item.day_number}`;
                    const iconClass = item.type === 'activity'
                        ? 'fa-solid fa-person-hiking'
                        : (item.type === 'fnb'
                            ? 'fa-solid fa-utensils'
                            : (item.type === 'airport' ? 'fa-solid fa-plane' : (item.type === 'accommodation' ? 'fa-solid fa-bed' : 'fa-solid fa-location-dot')));
                    const iconTypeClass = item.type === 'activity'
                        ? 'activity'
                        : (item.type === 'fnb' ? 'fnb' : (item.type === 'airport' || item.type === 'accommodation' ? 'point' : 'attraction'));
                    const timeLabel = pointRole === 'start'
                        ? `Start Tour: ${start}`
                        : (pointRole === 'end' ? `End Tour: ${end}` : `${start} - ${end}`);
                    return `<li class="visible-item rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/50">
                        <div class="visible-item-icon-box ${iconTypeClass}">
                            <i class="${iconClass} text-xl"></i>
                        </div>
                        <div class="visible-item-content">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex min-w-0 items-center">
                                <span class="truncate font-medium">#${item.badge_no} ${item.name}</span>
                                </span>
                                <span class="text-[10px] uppercase text-gray-500 dark:text-gray-400">${dayLabel} | ${type}</span>
                            </div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">${timeLabel}${item.location ? ` | ${item.location}` : ''}</div>
                        </div>
                    </li>`;
                }).join('');
            };

            const renderPoints = async (selectedDay = null) => {
                markerLayer.clearLayers();
                clearRouteLayers();
                markerLookup.clear();
                highlightedMarker = null;
                highlightedMarkerOrder = null;

                const activeDays = selectedDay === null ? dayValues : [selectedDay];
                const activePoints = validPoints.filter((point) => activeDays.includes(Number(point.day_number)));
                if (!activePoints.length) return;

                const badgeCounterByDay = {};
                const latLngs = [];
                const visibleListItems = [];
                activePoints.forEach((point) => {
                    const dayKey = String(point.day_number);
                    badgeCounterByDay[dayKey] = (badgeCounterByDay[dayKey] || 0) + 1;
                    const badgeNo = badgeCounterByDay[dayKey];
                    const latLng = [point.lat, point.lng];
                    latLngs.push(latLng);
                    const start = point.start_time ? String(point.start_time).slice(0, 5) : '--:--';
                    const end = point.end_time ? String(point.end_time).slice(0, 5) : '--:--';
                    const pointRole = String(point.point_role || '');
                    const typeLabelMap = {
                        attraction: 'Attraction',
                        activity: 'Activity',
                        fnb: 'F&B',
                        airport: 'Airport',
                        accommodation: 'Accommodation',
                    };
                    const pointTypeLabel = typeLabelMap[point.type] || 'Point';
                    const popupTimeLabel = pointRole === 'start'
                        ? `Start Tour: ${start}`
                        : (pointRole === 'end' ? `End Tour: ${end}` : `${start} - ${end}`);
                    const marker = L.marker(latLng, { icon: createBadgeIcon(badgeNo, point.type) })
                        .bindPopup(`#${badgeNo} | Day ${point.day_number} | ${pointTypeLabel}: ${point.name}${point.location ? ' - ' + point.location : ''} (${popupTimeLabel})`)
                        .addTo(markerLayer);
                    markerLookup.set(String(point.marker_key || `${point.day_number}-${badgeNo}`), { marker, badgeNo, type: point.type });
                    visibleListItems.push({
                        badge_no: badgeNo,
                        day_number: point.day_number,
                        type: point.type,
                        name: point.name,
                        location: point.location,
                        start_time: point.start_time,
                        end_time: point.end_time,
                        point_role: point.point_role || null,
                    });
                });
                renderMapItemList(visibleListItems);

                if (latLngs.length === 1) {
                    map.setView(latLngs[0], 14);
                } else {
                    map.fitBounds(latLngs, { padding: [20, 20] });
                }

                for (const day of activeDays) {
                    const dayPoints = (groupedByDay[String(day)] || []).slice().sort((a, b) => (a.visit_order - b.visit_order));
                    if (dayPoints.length < 2) continue;
                    const coordinates = dayPoints.map((point) => `${point.lng},${point.lat}`).join(';');
                    try {
                        const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson`);
                        const data = await response.json();
                        const geometry = data?.routes?.[0]?.geometry;
                        if (!geometry) continue;
                        const dayIndex = Math.max(0, dayValues.indexOf(day));
                        const layer = L.geoJSON(geometry, {
                            style: { color: dayColors[dayIndex % dayColors.length], weight: 4, opacity: 0.9 }
                        }).addTo(map);
                        routeLayers.push(layer);
                    } catch (_) {}
                }
            };

            let activeDay = null;
            setActiveButton(activeDay);
            renderPoints(activeDay);

            const focusSchedulePoint = async (day, markerKey) => {
                if (!Number.isFinite(day) || !markerKey) return;
                if (activeDay !== day) {
                    activeDay = day;
                    setActiveButton(activeDay);
                    await renderPoints(activeDay);
                }

                const markerData = markerLookup.get(String(markerKey));
                if (!markerData?.marker) return;
                const marker = markerData.marker;
                const badgeNo = markerData.badgeNo;

                if (highlightedMarker && highlightedMarkerOrder !== null) {
                    highlightedMarker.setIcon(createBadgeIcon(highlightedMarkerOrder, highlightedMarkerType, false));
                }

                marker.setIcon(createBadgeIcon(badgeNo, markerData.type, true));
                highlightedMarker = marker;
                highlightedMarkerOrder = badgeNo;
                highlightedMarkerType = markerData.type;
                map.panTo(marker.getLatLng(), { animate: true, duration: 0.35 });
                marker.openPopup();
            };

            dayButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const value = button.dataset.day || '';
                    activeDay = value === '' ? null : Number(value);
                    setActiveButton(activeDay);
                    await renderPoints(activeDay);
                });
            });

            scheduleIndexButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const day = Number(button.dataset.day || '');
                    const markerKey = String(button.dataset.markerKey || '');
                    await focusSchedulePoint(day, markerKey);
                });
            });
        })();
    </script>
@endpush


