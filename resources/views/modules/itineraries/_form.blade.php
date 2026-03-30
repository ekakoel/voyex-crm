@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $itinerary = $itinerary ?? null;
    $inquiries = $inquiries ?? collect();
    $airports = $airports ?? collect();
    $hotels = $hotels ?? collect();
    $transportUnits = $transportUnits ?? collect();
    $destinations = $destinations ?? collect();
    $destinationNameById = $destinations->pluck('name', 'id')->toArray();
    $prefillInquiryId = $prefillInquiryId ?? null;
    $normalizePointType = static fn ($value, string $default = ''): string => trim((string) $value) !== ''
        ? trim((string) $value)
        : $default;
    $selectedInquiryId = old('inquiry_id', $itinerary->inquiry_id ?? $prefillInquiryId);
    $durationNights = max(
        0,
        (int) old('duration_nights', $itinerary->duration_nights ?? max(0, ($itinerary->duration_days ?? 1) - 1)),
    );

    $rawAttractions = old('itinerary_items');
    if (!is_array($rawAttractions)) {
        $rawAttractions = [];
        if (isset($itinerary)) {
            foreach ($itinerary->touristAttractions as $a) {
                $rawAttractions[] = [
                    'tourist_attraction_id' => $a->id,
                    'day_number' => $a->pivot->day_number ?? 1,
                    'start_time' => $a->pivot->start_time ? substr((string) $a->pivot->start_time, 0, 5) : '',
                    'end_time' => $a->pivot->end_time ? substr((string) $a->pivot->end_time, 0, 5) : '',
                    'travel_minutes_to_next' => $a->pivot->travel_minutes_to_next ?? null,
                    'visit_order' => $a->pivot->visit_order ?? null,
                ];
            }
        }
    }

    $rawActivities = old('itinerary_activity_items');
    if (!is_array($rawActivities)) {
        $rawActivities = [];
        if (isset($itinerary)) {
            foreach ($itinerary->itineraryActivities as $a) {
                $rawActivities[] = [
                    'activity_id' => $a->activity_id,
                    'pax' => $a->pax ?? 1,
                    'day_number' => $a->day_number ?? 1,
                    'start_time' => $a->start_time ? substr((string) $a->start_time, 0, 5) : '',
                    'end_time' => $a->end_time ? substr((string) $a->end_time, 0, 5) : '',
                    'travel_minutes_to_next' => $a->travel_minutes_to_next ?? null,
                    'visit_order' => $a->visit_order ?? null,
                ];
            }
        }
    }
    $rawFoodBeverages = old('itinerary_food_beverage_items');
    if (!is_array($rawFoodBeverages)) {
        $rawFoodBeverages = [];
        if (isset($itinerary)) {
            foreach ($itinerary->itineraryFoodBeverages as $f) {
                $rawFoodBeverages[] = [
                    'food_beverage_id' => $f->food_beverage_id,
                    'pax' => $f->pax ?? 1,
                    'day_number' => $f->day_number ?? 1,
                    'start_time' => $f->start_time ? substr((string) $f->start_time, 0, 5) : '',
                    'end_time' => $f->end_time ? substr((string) $f->end_time, 0, 5) : '',
                    'travel_minutes_to_next' => $f->travel_minutes_to_next ?? null,
                    'visit_order' => $f->visit_order ?? null,
                ];
            }
        }
    }

    $durationDays = max(1, (int) old('duration_days', $itinerary->duration_days ?? 1));
    $touristAttractionsSorted = collect($touristAttractions ?? [])
        ->sortBy(function ($item) {
            $city = strtolower(trim((string) ($item->city ?? '')));
            $name = strtolower(trim((string) ($item->name ?? '')));
            return $city . '|' . $name;
        })
        ->values();
    $activitiesSorted = collect($activities ?? [])
        ->sortBy(function ($item) {
            $city = strtolower(trim((string) ($item->vendor?->city ?? '')));
            $name = strtolower(trim((string) ($item->name ?? '')));
            $vendor = strtolower(trim((string) ($item->vendor?->name ?? '')));
            return $city . '|' . $name . '|' . $vendor;
        })
        ->values();
    $foodBeveragesSorted = collect($foodBeverages ?? [])
        ->sortBy(function ($item) {
            $city = strtolower(trim((string) ($item->vendor?->city ?? '')));
            $name = strtolower(trim((string) ($item->name ?? '')));
            $vendor = strtolower(trim((string) ($item->vendor?->name ?? '')));
            return $city . '|' . $name . '|' . $vendor;
        })
        ->values();
    $hotelsSorted = collect($hotels ?? [])
        ->sortBy(function ($item) {
            $city = strtolower(trim((string) ($item->city ?? '')));
            $name = strtolower(trim((string) ($item->name ?? '')));
            return $city . '|' . $name;
        })
        ->values();
    $dailyEndPointTypes = old('daily_end_point_types');
    $dailyEndPointItems = old('daily_end_point_items');
    $dailyStartPointRoomIds = old('daily_start_point_room_ids');
    $dailyEndPointRoomIds = old('daily_end_point_room_ids');
    $dailyStartPointRoomCounts = old('daily_start_point_room_counts');
    $dailyEndPointRoomCounts = old('daily_end_point_room_counts');
    if (!is_array($dailyEndPointTypes) || !is_array($dailyEndPointItems)) {
        $dailyEndPointTypes = [];
        $dailyEndPointItems = [];
        if (isset($itinerary) && $itinerary->dayPoints->isNotEmpty()) {
            foreach ($itinerary->dayPoints as $dayPoint) {
                $day = (int) ($dayPoint->day_number ?? 0);
                if ($day <= 0) {
                    continue;
                }
                $dailyEndPointTypes[$day] = $normalizePointType($dayPoint->end_point_type ?? '', 'hotel');
                $dailyEndPointItems[$day] =
                    (string) ($dailyEndPointTypes[$day] === 'airport'
                        ? $dayPoint->end_airport_id ?? ''
                        : $dayPoint->end_hotel_id ?? '');
                $dailyEndPointRoomIds[$day] = (string) ($dayPoint->end_hotel_room_id ?? '');
            }
        } elseif (isset($itinerary)) {
            foreach ($itinerary->hotels as $hotel) {
                $startDay = (int) ($hotel->pivot->day_number ?? 1);
                $nightCount = max(1, (int) ($hotel->pivot->night_count ?? 1));
                $roomCount = max(1, (int) ($hotel->pivot->room_count ?? 1));
                for ($day = $startDay; $day < $startDay + $nightCount; $day++) {
                    $dailyEndPointTypes[$day] = 'hotel';
                    $dailyEndPointItems[$day] = (string) $hotel->id;
                    $dailyEndPointRoomCounts[$day] = $roomCount;
                    $dailyEndPointRoomIds[$day] = '';
                }
            }
        }
    }
    if (!is_array($dailyStartPointRoomIds)) {
        $dailyStartPointRoomIds = [];
    }
    if (!is_array($dailyEndPointRoomIds)) {
        $dailyEndPointRoomIds = [];
    }
    if (!is_array($dailyStartPointRoomCounts)) {
        $dailyStartPointRoomCounts = [];
    }
    if (!is_array($dailyEndPointRoomCounts)) {
        $dailyEndPointRoomCounts = [];
    }
    $dailyStartPointTypes = old('daily_start_point_types');
    $dailyStartPointItems = old('daily_start_point_items');
    $dailyTransportUnitItems = old('daily_transport_units');
    if (!is_array($dailyTransportUnitItems)) {
        $dailyTransportUnitItems = [];
        if (isset($itinerary)) {
            foreach ($itinerary->itineraryTransportUnits as $transportItem) {
                $day = (int) ($transportItem->day_number ?? 0);
                if ($day <= 0) {
                    continue;
                }
                $dailyTransportUnitItems[$day] = [
                    'day_number' => $day,
                    'transport_unit_id' => (string) ($transportItem->transport_unit_id ?? ''),
                ];
            }
        }
    }
    if (!is_array($dailyStartPointTypes) || !is_array($dailyStartPointItems)) {
        $dailyStartPointTypes = [];
        $dailyStartPointItems = [];
        if (isset($itinerary) && $itinerary->dayPoints->isNotEmpty()) {
            foreach ($itinerary->dayPoints as $dayPoint) {
                $day = (int) ($dayPoint->day_number ?? 0);
                if ($day <= 0) {
                    continue;
                }
                $dailyStartPointTypes[$day] = $normalizePointType(
                    $dayPoint->start_point_type ?? '',
                    $day === 1 ? 'airport' : 'previous_day_end',
                );
                $dailyStartPointItems[$day] =
                    (string) ($dailyStartPointTypes[$day] === 'airport'
                        ? $dayPoint->start_airport_id ?? ''
                        : $dayPoint->start_hotel_id ?? '');
                $dailyStartPointRoomIds[$day] = (string) ($dayPoint->start_hotel_room_id ?? '');
            }
        }
        for ($day = 1; $day <= $durationDays; $day++) {
            if (!isset($dailyStartPointTypes[$day])) {
                $dailyStartPointTypes[$day] = $day === 1 ? 'airport' : 'previous_day_end';
            }
            if (!isset($dailyStartPointItems[$day])) {
                $dailyStartPointItems[$day] = '';
            }
        }
    }
    $dailyMainExperienceTypes = old('daily_main_experience_types');
    $dailyMainExperienceItems = old('daily_main_experience_items');
    if (!is_array($dailyMainExperienceTypes) || !is_array($dailyMainExperienceItems)) {
        $dailyMainExperienceTypes = [];
        $dailyMainExperienceItems = [];
        if (isset($itinerary) && $itinerary->dayPoints->isNotEmpty()) {
            foreach ($itinerary->dayPoints as $dayPoint) {
                $day = (int) ($dayPoint->day_number ?? 0);
                if ($day <= 0) {
                    continue;
                }
                $type = (string) ($dayPoint->main_experience_type ?? '');
                if (!in_array($type, ['attraction', 'activity', 'fnb'], true)) {
                    $type = '';
                }
                $dailyMainExperienceTypes[$day] = $type;
                $dailyMainExperienceItems[$day] =
                    (string) ($type === 'attraction'
                        ? $dayPoint->main_tourist_attraction_id ?? ''
                        : ($type === 'activity'
                            ? $dayPoint->main_activity_id ?? ''
                            : ($type === 'fnb'
                                ? $dayPoint->main_food_beverage_id ?? ''
                                : '')));
            }
        }
    }
    $dayIncludes = old('day_includes');
    $dayExcludes = old('day_excludes');
    if (!is_array($dayIncludes)) {
        $dayIncludes = [];
        if (isset($itinerary) && $itinerary->dayPoints->isNotEmpty()) {
            foreach ($itinerary->dayPoints as $dayPoint) {
                $day = (int) ($dayPoint->day_number ?? 0);
                if ($day <= 0) {
                    continue;
                }
                $dayIncludes[$day] = (string) ($dayPoint->day_include ?? '');
            }
        }
    }
    if (!is_array($dayExcludes)) {
        $dayExcludes = [];
        if (isset($itinerary) && $itinerary->dayPoints->isNotEmpty()) {
            foreach ($itinerary->dayPoints as $dayPoint) {
                $day = (int) ($dayPoint->day_number ?? 0);
                if ($day <= 0) {
                    continue;
                }
                $dayExcludes[$day] = (string) ($dayPoint->day_exclude ?? '');
            }
        }
    }

    $rows = collect();
    foreach ($rawAttractions as $i => $item) {
        $rows->push([
            'item_type' => 'attraction',
            'tourist_attraction_id' => $item['tourist_attraction_id'] ?? '',
            'activity_id' => '',
            'pax' => 1,
            'day_number' => (int) ($item['day_number'] ?? 1),
            'start_time' => $item['start_time'] ?? '',
            'end_time' => $item['end_time'] ?? '',
            'travel_minutes_to_next' => $item['travel_minutes_to_next'] ?? '',
            'visit_order' => $item['visit_order'] ?? null,
            '_sort' => $i,
        ]);
    }
    foreach ($rawActivities as $i => $item) {
        $rows->push([
            'item_type' => 'activity',
            'tourist_attraction_id' => '',
            'activity_id' => $item['activity_id'] ?? '',
            'food_beverage_id' => '',
            'pax' => max(1, (int) ($item['pax'] ?? 1)),
            'day_number' => (int) ($item['day_number'] ?? 1),
            'start_time' => $item['start_time'] ?? '',
            'end_time' => $item['end_time'] ?? '',
            'travel_minutes_to_next' => $item['travel_minutes_to_next'] ?? '',
            'visit_order' => $item['visit_order'] ?? null,
            '_sort' => 100000 + $i,
        ]);
    }
    foreach ($rawFoodBeverages as $i => $item) {
        $rows->push([
            'item_type' => 'fnb',
            'tourist_attraction_id' => '',
            'activity_id' => '',
            'food_beverage_id' => $item['food_beverage_id'] ?? '',
            'pax' => max(1, (int) ($item['pax'] ?? 1)),
            'day_number' => (int) ($item['day_number'] ?? 1),
            'start_time' => $item['start_time'] ?? '',
            'end_time' => $item['end_time'] ?? '',
            'travel_minutes_to_next' => $item['travel_minutes_to_next'] ?? '',
            'visit_order' => $item['visit_order'] ?? null,
            '_sort' => 200000 + $i,
        ]);
    }
    $rowsByDay = $rows
        ->sort(function ($a, $b) {
            if ($a['day_number'] !== $b['day_number']) {
                return $a['day_number'] <=> $b['day_number'];
            }
            if (($a['visit_order'] ?? 999999) !== ($b['visit_order'] ?? 999999)) {
                return ($a['visit_order'] ?? 999999) <=> ($b['visit_order'] ?? 999999);
            }
            return $a['_sort'] <=> $b['_sort'];
        })
        ->groupBy('day_number');

    $inquiryPreviewData = [];
    foreach ($inquiries as $inquiry) {
        $latestFollowUp = $inquiry->followUps->first();
        $inquiryPreviewData[(string) $inquiry->id] = [
            'inquiry_number' => (string) ($inquiry->inquiry_number ?? '-'),
            'customer' => trim(
                (string) (($inquiry->customer?->code ? '(' . $inquiry->customer->code . ') ' : '') .
                    ($inquiry->customer?->name ?? '-')),
            ),
            'status' => (string) ($inquiry->status ?? '-'),
            'priority' => (string) ($inquiry->priority ?? '-'),
            'source' => (string) ($inquiry->source ?? '-'),
            'assigned_to' => (string) ($inquiry->assignedUser?->name ?? '-'),
            'itinerary_count' => (int) ($inquiry->itineraries_count ?? 0),
            'deadline' => $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-',
            'created_at' => $inquiry->created_at ? $inquiry->created_at->format('Y-m-d H:i') : '-',
            'notes' => \App\Support\SafeRichText::sanitize($inquiry->notes ?? null) ?: '-',
            'reminder_note' => \App\Support\SafeRichText::sanitize($latestFollowUp?->note ?? null) ?: '-',
            'reminder_reason' => \App\Support\SafeRichText::sanitize($latestFollowUp?->done_reason ?? null) ?: '-',
        ];
    }
@endphp

<div class="space-y-4 itinerary-form-page">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Inquiry (Optional)</label>
        <select id="inquiry-select" name="inquiry_id"
            class="mt-1 dark:border-gray-600 app-input">
            <option value="">Independent itinerary (no inquiry)</option>
            @foreach ($inquiries as $inquiry)
                <option value="{{ $inquiry->id }}" @selected((string) $selectedInquiryId === (string) $inquiry->id)>
                    {{ date('y-m-d',strtotime($inquiry->deadline)) }}
                    @if (!empty($inquiry->customer?->name))
                        | {{ $inquiry->customer->name }}
                    @endif
                    | Itineraries: {{ (int) ($inquiry->itineraries_count ?? 0) }}
                </option>
            @endforeach
        </select>
        @error('inquiry_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
        <input name="title" value="{{ old('title', $itinerary->title ?? '') }}"
            class="mt-1 dark:border-gray-600 app-input"
            required>
    </div>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
        <div class="md:col-span-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Destination</label>
            <div class="relative mt-1">
                <input id="itinerary-destination" name="destination"
                    value="{{ old('destination', $itinerary->destination ?? '') }}"
                    data-endpoint="{{ route('itineraries.destination-suggestions') }}" autocomplete="off"
                    placeholder="Example: Bali, Lombok, Jakarta"
                    class="dark:border-gray-600 app-input"
                    required>
                <div id="itinerary-destination-dropdown"
                    class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-gray-600 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                </div>
            </div>
            @error('destination')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="md:col-span-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Duration (Days)</label>
            <input id="duration-days" name="duration_days" type="number" min="1"
                value="{{ old('duration_days', $itinerary->duration_days ?? 1) }}"
                class="mt-1 dark:border-gray-600 app-input"
                required>
        </div>
        <div class="md:col-span-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Duration (Nights)</label>
            <input id="duration-nights" name="duration_nights" type="number" min="0"
                value="{{ $durationNights }}"
                class="mt-1 dark:border-gray-600 app-input"
                required>
            @error('duration_nights')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
        <textarea name="description" rows="4"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $itinerary->description ?? '') }}</textarea>
    </div>

    <input type="hidden" id="hotel-stays-hidden-enabled" value="1" class="app-input">
    <div id="hotel-stays-hidden"></div>
    @error('hotel_stays')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error('hotel_stays.*')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error('hotel_stays.*.room_count')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error('daily_start_point_room_ids.*')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error('daily_end_point_room_ids.*')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error('daily_transport_units')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error('daily_transport_units.*.transport_unit_id')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror

    <div class="space-y-2">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Schedule Items (Attraction + Activity)</p>
        <div id="day-sections" class="space-y-3">
            @for ($day = 1; $day <= $durationDays; $day++)
                @php
                    $dayRows = collect($rowsByDay->get($day, collect()));
                    $existingDayPoint = isset($itinerary)
                        ? $itinerary->dayPoints->firstWhere('day_number', $day)
                        : null;
                    $dayStart = old(
                        "day_start_times.$day",
                        $existingDayPoint?->day_start_time
                            ? substr((string) $existingDayPoint->day_start_time, 0, 5)
                            : '',
                    );
                    if ($dayStart === '') {
                        foreach ($dayRows as $r) {
                            if (!empty($r['start_time'])) {
                                $dayStart = substr((string) $r['start_time'], 0, 5);
                                break;
                            }
                        }
                    }
                @endphp
                <div class="day-section rounded-xl border border-gray-400 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    data-day="{{ $day }}">
                    @php
                        $startType = $normalizePointType(
                            $dailyStartPointTypes[$day] ?? '',
                            $day === 1 ? 'airport' : 'previous_day_end',
                        );
                        $startItem = (string) ($dailyStartPointItems[$day] ?? '');
                        $startRoomId = (string) ($dailyStartPointRoomIds[$day] ?? '');
                        $endType = $normalizePointType($dailyEndPointTypes[$day] ?? '', 'hotel');
                        $endItem = (string) ($dailyEndPointItems[$day] ?? '');
                        $endRoomId = (string) ($dailyEndPointRoomIds[$day] ?? '');
                        $dayStartTravelMinutes = old(
                            "day_start_travel_minutes.$day",
                            isset($existingDayPoint)
                                ? (string) ($existingDayPoint->day_start_travel_minutes ?? '')
                                : '',
                        );
                        $dayInclude = old(
                            "day_includes.$day",
                            isset($existingDayPoint)
                                ? (string) ($existingDayPoint->day_include ?? ($dayIncludes[$day] ?? ''))
                                : (string) ($dayIncludes[$day] ?? ''),
                        );
                        $dayExclude = old(
                            "day_excludes.$day",
                            isset($existingDayPoint)
                                ? (string) ($existingDayPoint->day_exclude ?? ($dayExcludes[$day] ?? ''))
                                : (string) ($dayExcludes[$day] ?? ''),
                        );
                        $mainExperienceType = (string) ($dailyMainExperienceTypes[$day] ?? '');
                        $mainExperienceItem = (string) ($dailyMainExperienceItems[$day] ?? '');
                    @endphp
                    <div class="day-card-header mb-3">
                        <div class="app-day-header day-card-header-pill">
                            <p class="day-title-label app-day-header-title">Day {{ $day }}</p>
                        </div>
                        <div class="app-day-header day-card-header-pill min-w-[280px] flex-1">
                            <p class="day-endpoint-badge app-day-header-meta">
                                Starts at: <span class="day-starts-at-label">Not set</span>
                                <span class="mx-1">|</span>
                                Ends at: <span class="day-ends-at-label">Not set</span>
                            </p>
                        </div>
                    </div>
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-500">Start Tour</label>
                            <input type="time" value="{{ $dayStart }}"
                                name="day_start_times[{{ $day }}]"
                                class="day-start-time dark:border-gray-600 app-input">
                            <label class="text-xs text-gray-500">End Tour</label>
                            <input type="time" value=""
                                class="day-end-time text-gray-700 dark:border-gray-600 app-input"
                                readonly>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                 class="add-attraction rounded-lg border px-3 py-1 text-xs font-medium text-white-700">Add
                                Attraction</button>
                            <button type="button"
                                 class="add-activity rounded-lg border px-3 py-1 text-xs font-medium text-white-700">Add
                                Activity</button>
                            <button type="button"
                                 class="add-fnb rounded-lg border px-3 py-1 text-xs font-medium text-white-700">Add
                                F&B</button>
                        </div>
                    </div>
                    <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                Day {{ $day }} Transport Unit
                            </label>
                            <select
                                class="day-transport-unit dark:border-gray-600 app-input">
                                <option value="">Select transport unit</option>
                                @foreach ($transportUnits ?? collect() as $unit)
                                    <option value="{{ $unit->id }}"
                                        data-city="{{ $unit->transport?->vendor?->city ?? '' }}"
                                        data-province="{{ $unit->transport?->vendor?->province ?? '' }}"
                                        data-location="{{ $unit->transport?->vendor?->location ?? '' }}"
                                        data-destination="{{ $unit->transport?->vendor?->destination?->name ?? '' }}"
                                        @selected((string) ($dailyTransportUnitItems[$day]['transport_unit_id'] ?? '') === (string) $unit->id)>
                                        {{ $unit->name }}{{ !empty($unit->transport?->name) ? ' - ' . $unit->transport->name : '' }}{{ !empty($unit->seat_capacity) ? ' (' . $unit->seat_capacity . ' seats)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" class="day-transport-day app-input" value="{{ $day }}">
                        </div>
                    </div>
                    <div
                        class="mb-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3 day-start-point-card dark:border-slate-600 dark:bg-slate-900/25">
                        <div class="space-y-2">
                            <label
                                class="day-start-point-label mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                Day {{ $day }} Start Point
                            </label>
                            <div class="mb-3 grid grid-cols-1 gap-2 md:grid-cols-12">
                                <div class="md:col-span-4">
                                    <select name="daily_start_point_types[{{ $day }}]"
                                        class="day-start-point-type dark:border-gray-600 app-input">
                                        @if ($day !== 1)
                                            <option value="previous_day_end" @selected($startType === 'previous_day_end')>Previous Day
                                                Endpoint (Auto)</option>
                                        @endif
                                        <option value="hotel" @selected($startType === 'hotel')>Hotel</option>
                                        <option value="airport" @selected($startType === 'airport')>Airport</option>
                                    </select>
                                </div>
                                <div class="md:col-span-8">
                                    <select name="daily_start_point_items[{{ $day }}]"
                                        class="day-start-point-item dark:border-gray-600 app-input">
                                        <option value="">Select start point item</option>
                                        @foreach ($hotelsSorted as $hotel)
                                            <option value="{{ $hotel->id }}" data-point-type="hotel"
                                                data-location="{{ $hotel->address ?? '' }}"
                                                data-city="{{ $hotel->city ?? '' }}"
                                                data-province="{{ $hotel->province ?? '' }}"
                                                data-destination="{{ $destinationNameById[$hotel->destination_id] ?? '' }}"
                                                data-latitude="{{ $hotel->latitude ?? '' }}"
                                                data-longitude="{{ $hotel->longitude ?? '' }}"
                                                @selected($startType === 'hotel' && $startItem === (string) $hotel->id)>
                                                {{ !empty($hotel->city) ? $hotel->city : '-' }} - {{ $hotel->name }}
                                            </option>
                                        @endforeach
                                        @foreach ($airports ?? collect() as $airport)
                                            <option value="{{ $airport->id }}" data-point-type="airport"
                                                data-location="{{ $airport->location ?? '' }}"
                                                data-city="{{ $airport->city ?? '' }}"
                                                data-province="{{ $airport->province ?? '' }}"
                                                data-destination="{{ $destinationNameById[$airport->destination_id] ?? '' }}"
                                                data-latitude="{{ $airport->latitude ?? '' }}"
                                                data-longitude="{{ $airport->longitude ?? '' }}"
                                                @selected($startType === 'airport' && $startItem === (string) $airport->id)>
                                                {{ $airport->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div
                                class="day-start-room-wrap {{ $startType === 'hotel' ? '' : 'hidden' }} md:max-w-4xl">
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
                                    <div class="md:col-span-8">
                                        <label
                                            class="mb-1 mt-2 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Room</label>
                                        <select name="daily_start_point_room_ids[{{ $day }}]"
                                            class="day-start-room-select dark:border-gray-600 app-input"
                                            {{ $startType === 'hotel' ? '' : 'disabled' }}>
                                            <option value="">Select room</option>
                                            @foreach ($hotels as $hotel)
                                                @foreach ($hotel->rooms ?? collect() as $room)
                                                    <option value="{{ $room->id }}"
                                                        data-hotel-id="{{ $hotel->id }}"
                                                        @selected($startType === 'hotel' && $startRoomId === (string) $room->id)>
                                                        {{ $hotel->name }} -
                                                        {{ $room->rooms }}{{ !empty($room->view) ? ' (' . $room->view . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="travel-connector mb-3 flex min-h-[74px] items-stretch overflow-hidden rounded-lg border border-sky-200 bg-sky-50 dark:border-sky-700/60 dark:bg-sky-900/25">
                        <div class="flex w-12 shrink-0 items-center justify-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true" focusable="false">
                                <path d="M5.5 11.5L7.3 6.9C7.6 6.1 8.3 5.5 9.2 5.5h5.6c.9 0 1.6.6 1.9 1.4l1.8 4.6c1 .2 1.8 1.1 1.8 2.2v2.3c0 .8-.7 1.5-1.5 1.5h-.5a2.3 2.3 0 01-4.6 0h-4.4a2.3 2.3 0 01-4.6 0h-.5c-.8 0-1.5-.7-1.5-1.5v-2.3c0-1.1.8-2 1.8-2.2zm3.1-4.2L7.2 11h9.6l-1.4-3.7a.8.8 0 00-.7-.5H9.3c-.3 0-.6.2-.7.5zM8.2 18.9c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9zm7.6 0c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9z"/>
                            </svg>
                        </div>
                        <div class="flex-1 p-3">
                            <label class="block text-xs text-gray-500 dark:text-gray-400">
                                Travel to next item (minutes)
                            </label>
                            <input type="number" min="0" step="5"
                                name="day_start_travel_minutes[{{ $day }}]"
                                value="{{ $dayStartTravelMinutes }}"
                                class="day-start-travel mt-2 dark:border-gray-600 app-input"
                                placeholder="Example: 30">
                        </div>
                    </div>

                    <div class="day-items space-y-2">
                        @forelse ($dayRows as $r)
                            @php
                                $rowItemId =
                                    $r['item_type'] === 'attraction'
                                        ? (string) ($r['tourist_attraction_id'] ?? '')
                                        : ($r['item_type'] === 'activity'
                                            ? (string) ($r['activity_id'] ?? '')
                                            : (string) ($r['food_beverage_id'] ?? ''));
                                $isRowMainExperience =
                                    $mainExperienceType !== '' &&
                                    $mainExperienceType === (string) ($r['item_type'] ?? '') &&
                                    $mainExperienceItem !== '' &&
                                    $mainExperienceItem === $rowItemId;
                            @endphp
                            <div class="schedule-row grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50/70 p-3 dark:border-slate-600 dark:bg-slate-900/30 lg:grid-cols-12"
                                data-item-type="{{ $r['item_type'] }}">
                                <div class="flex items-center gap-2 lg:col-span-2">
                                    <button type="button"
                                         class="drag-handle inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-base leading-none text-gray-600 dark:border-gray-600 dark:text-gray-300"
                                        title="Drag to reorder" aria-label="Drag to reorder">::</button>
                                    <span
                                        class="item-seq-badge inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Item
                                        Type</label>
                                    <select
                                        class="item-type dark:border-gray-600 app-input">
                                        <option value="attraction" @selected($r['item_type'] === 'attraction')>Attraction</option>
                                        <option value="activity" @selected($r['item_type'] === 'activity')>Activity</option>
                                        <option value="fnb" @selected($r['item_type'] === 'fnb')>F&B</option>
                                    </select>
                                </div>
                                <div class="min-w-0 lg:col-span-8">
                                    <label
                                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Attraction
                                        / Activity / F&B</label>
                                    <select
                                        class="item-attraction {{ $r['item_type'] !== 'attraction' ? 'hidden' : '' }} dark:border-gray-600 app-input">
                                        <option value="">Select attraction</option>
                                        @foreach ($touristAttractionsSorted as $a)
                                            <option value="{{ $a->id }}"
                                                data-duration="{{ $a->ideal_visit_minutes ?? 120 }}"
                                                data-city="{{ $a->city ?? '' }}"
                                                data-province="{{ $a->province ?? '' }}"
                                                data-latitude="{{ $a->latitude }}"
                                                data-longitude="{{ $a->longitude }}" @selected((string) ($r['tourist_attraction_id'] ?? '') === (string) $a->id)>
                                                {{ !empty($a->city) ? $a->city : '-' }} - {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <select
                                            class="item-activity {{ $r['item_type'] !== 'activity' ? 'hidden' : '' }} dark:border-gray-600 app-input">
                                            <option value="">Select activity</option>
                                            @foreach ($activitiesSorted as $a)
                                                <option value="{{ $a->id }}"
                                                    data-duration="{{ $a->duration_minutes ?? 60 }}"
                                                    data-city="{{ $a->vendor?->city ?? '' }}"
                                                    data-province="{{ $a->vendor?->province ?? '' }}"
                                                    data-latitude="{{ $a->vendor?->latitude ?? '' }}"
                                                    data-longitude="{{ $a->vendor?->longitude ?? '' }}"
                                                    @selected((string) ($r['activity_id'] ?? '') === (string) $a->id)>{{ !empty($a->vendor?->city) ? $a->vendor->city : '-' }} - {{ $a->name }} - {{ !empty($a->vendor?->name) ? $a->vendor->name : '-' }}</option>
                                            @endforeach
                                        </select>
                                        <select
                                            class="item-fnb {{ $r['item_type'] !== 'fnb' ? 'hidden' : '' }} dark:border-gray-600 app-input">
                                            <option value="">Select F&B</option>
                                            @foreach ($foodBeveragesSorted as $f)
                                                <option value="{{ $f->id }}"
                                                    data-duration="{{ $f->duration_minutes ?? 60 }}"
                                                    data-city="{{ $f->vendor?->city ?? '' }}"
                                                    data-province="{{ $f->vendor?->province ?? '' }}"
                                                    data-latitude="{{ $f->vendor?->latitude ?? '' }}"
                                                    data-longitude="{{ $f->vendor?->longitude ?? '' }}"
                                                    @selected((string) ($r['food_beverage_id'] ?? '') === (string) $f->id)>{{ !empty($f->vendor?->city) ? $f->vendor->city : '-' }} - {{ $f->name }} - {{ !empty($f->vendor?->name) ? $f->vendor->name : '-' }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" value="{{ $r['pax'] ?? 1 }}" class="item-pax app-input">
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <label
                                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Start
                                        Time</label>
                                    <input type="time" value="{{ $r['start_time'] ?? '' }}"
                                        class="item-start text-gray-700 dark:border-gray-600 app-input"
                                        readonly>
                                </div>
                                <div class="lg:col-span-3">
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">End
                                        Time</label>
                                    <input type="time" value="{{ $r['end_time'] ?? '' }}"
                                        class="item-end text-gray-700 dark:border-gray-600 app-input"
                                        readonly>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Main
                                        Experience</label>
                                    <label
                                        class="inline-flex items-center gap-2 text-xs text-amber-700 dark:text-amber-300">
                                        <input type="checkbox"
                                            class="item-main-experience rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                                            @checked($isRowMainExperience)>
                                        Highlight
                                    </label>
                                </div>
                                <div class="lg:col-span-2">
                                    <label
                                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Action</label>
                                    <button type="button"
                                         class="remove-row w-full rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700">Remove</button>
                                </div>

                                <input type="hidden" class="item-travel app-input"
                                    value="{{ $r['travel_minutes_to_next'] }}">
                                <input type="hidden" class="item-day app-input" value="{{ $day }}">
                                <input type="hidden" class="item-order app-input" value="{{ $r['visit_order'] ?? '' }}">
                            </div>
                        @empty
                            <div class="schedule-row grid grid-cols-1 gap-3 rounded-lg border border-blue-200 bg-blue-50/60 p-3 dark:border-blue-700/60 dark:bg-blue-900/25 lg:grid-cols-12"
                                data-item-type="attraction">
                                <div class="flex items-center gap-2 lg:col-span-2"><button type="button"
                                         class="drag-handle inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-base leading-none text-gray-600 dark:border-gray-600 dark:text-gray-300"
                                        title="Drag to reorder" aria-label="Drag to reorder">::</button><span
                                        class="item-seq-badge inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Item
                                        Type</label>
                                    <select
                                        class="item-type dark:border-gray-600 app-input">
                                        <option value="attraction">Attraction</option>
                                        <option value="activity">Activity</option>
                                        <option value="fnb">F&B</option>
                                    </select>
                                </div>
                                <div class="min-w-0 lg:col-span-8">
                                    <label
                                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Attraction
                                        / Activity / F&B</label>
                                    <select
                                        class="item-attraction dark:border-gray-600 app-input">
                                        <option value="">Select attraction</option>
                                        @foreach ($touristAttractionsSorted as $a)
                                            <option value="{{ $a->id }}"
                                                data-duration="{{ $a->ideal_visit_minutes ?? 120 }}"
                                                data-city="{{ $a->city ?? '' }}"
                                                data-province="{{ $a->province ?? '' }}"
                                                data-latitude="{{ $a->latitude }}"
                                                data-longitude="{{ $a->longitude }}">{{ !empty($a->city) ? $a->city : '-' }} - {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex flex-col gap-2 sm:flex-row"><select
                                            class="item-activity hidden dark:border-gray-600 app-input">
                                            <option value="">Select activity</option>
                                            @foreach ($activitiesSorted as $a)
                                                <option value="{{ $a->id }}"
                                                    data-duration="{{ $a->duration_minutes ?? 60 }}"
                                                    data-city="{{ $a->vendor?->city ?? '' }}"
                                                    data-province="{{ $a->vendor?->province ?? '' }}"
                                                    data-latitude="{{ $a->vendor?->latitude ?? '' }}"
                                                    data-longitude="{{ $a->vendor?->longitude ?? '' }}">
                                                    {{ !empty($a->vendor?->city) ? $a->vendor->city : '-' }} - {{ $a->name }} - {{ !empty($a->vendor?->name) ? $a->vendor->name : '-' }}</option>
                                            @endforeach
                                        </select><select
                                            class="item-fnb hidden dark:border-gray-600 app-input">
                                            <option value="">Select F&B</option>
                                            @foreach ($foodBeveragesSorted as $f)
                                                <option value="{{ $f->id }}"
                                                    data-duration="{{ $f->duration_minutes ?? 60 }}"
                                                    data-city="{{ $f->vendor?->city ?? '' }}"
                                                    data-province="{{ $f->vendor?->province ?? '' }}"
                                                    data-latitude="{{ $f->vendor?->latitude ?? '' }}"
                                                    data-longitude="{{ $f->vendor?->longitude ?? '' }}">
                                                    {{ !empty($f->vendor?->city) ? $f->vendor->city : '-' }} - {{ $f->name }} - {{ !empty($f->vendor?->name) ? $f->vendor->name : '-' }}</option>
                                            @endforeach
                                        </select><input type="hidden" value="1" class="item-pax app-input">
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <label
                                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Start
                                        Time</label>
                                    <input type="time"
                                        class="item-start text-gray-700 dark:border-gray-600 app-input"
                                        readonly>
                                </div>
                                <div class="lg:col-span-3">
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">End
                                        Time</label>
                                    <input type="time"
                                        class="item-end text-gray-700 dark:border-gray-600 app-input"
                                        readonly>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Main
                                        Experience</label>
                                    <label
                                        class="inline-flex items-center gap-2 text-xs text-amber-700 dark:text-amber-300">
                                        <input type="checkbox"
                                            class="item-main-experience rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                                        Highlight
                                    </label>
                                </div>
                                <div class="lg:col-span-2">
                                    <label
                                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Action</label>
                                    <button type="button"
                                         class="remove-row w-full rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700">Remove</button>
                                </div>

                                <input type="hidden" class="item-travel app-input" value="">
                                <input type="hidden" class="item-day app-input" value="{{ $day }}"><input
                                    type="hidden" class="item-order app-input" value="">
                            </div>
                        @endforelse
                    </div>

                    <div
                        class="mt-3 mb-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3 day-end-point-card dark:border-slate-600 dark:bg-slate-900/25">
                        <div class="space-y-2">
                            <label
                                class="day-end-point-label mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                Day {{ $day }} End Point
                            </label>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
                                <div class="md:col-span-4">
                                    <select name="daily_end_point_types[{{ $day }}]"
                                        class="day-end-point-type dark:border-gray-600 app-input">
                                        <option value="hotel" @selected($endType === 'hotel')>Hotel</option>
                                        @if ($day === $durationDays)
                                            <option value="airport" @selected($endType === 'airport')>Airport</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="md:col-span-8">
                                    <select name="daily_end_point_items[{{ $day }}]"
                                        class="day-end-point-item day-end-point-select dark:border-gray-600 app-input">
                                        <option value="">Select end point item</option>
                                        @foreach ($hotelsSorted as $hotel)
                                            <option value="{{ $hotel->id }}" data-point-type="hotel"
                                                data-location="{{ $hotel->address ?? '' }}"
                                                data-city="{{ $hotel->city ?? '' }}"
                                                data-province="{{ $hotel->province ?? '' }}"
                                                data-destination="{{ $destinationNameById[$hotel->destination_id] ?? '' }}"
                                                data-latitude="{{ $hotel->latitude ?? '' }}"
                                                data-longitude="{{ $hotel->longitude ?? '' }}"
                                                @selected($endType === 'hotel' && $endItem === (string) $hotel->id)>
                                                {{ !empty($hotel->city) ? $hotel->city : '-' }} - {{ $hotel->name }}
                                            </option>
                                        @endforeach
                                        @foreach ($airports ?? collect() as $airport)
                                            <option value="{{ $airport->id }}" data-point-type="airport"
                                                data-location="{{ $airport->location ?? '' }}"
                                                data-city="{{ $airport->city ?? '' }}"
                                                data-province="{{ $airport->province ?? '' }}"
                                                data-destination="{{ $destinationNameById[$airport->destination_id] ?? '' }}"
                                                data-latitude="{{ $airport->latitude ?? '' }}"
                                                data-longitude="{{ $airport->longitude ?? '' }}"
                                                @selected($endType === 'airport' && $endItem === (string) $airport->id)>
                                                {{ $airport->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="day-end-room-wrap {{ $endType === 'hotel' ? '' : 'hidden' }} md:max-w-4xl">
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
                                    <div class="md:col-span-8">
                                        <label
                                            class="mb-1 mt-2 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Room</label>
                                        <select name="daily_end_point_room_ids[{{ $day }}]"
                                            class="day-end-room-select dark:border-gray-600 app-input"
                                            {{ $endType === 'hotel' ? '' : 'disabled' }}>
                                            <option value="">Select room</option>
                                            @foreach ($hotels as $hotel)
                                                @foreach ($hotel->rooms ?? collect() as $room)
                                                    <option value="{{ $room->id }}"
                                                        data-hotel-id="{{ $hotel->id }}"
                                                        @selected($endType === 'hotel' && $endRoomId === (string) $room->id)>
                                                        {{ $hotel->name }} -
                                                        {{ $room->rooms }}{{ !empty($room->view) ? ' (' . $room->view . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="daily_main_experience_types[{{ $day }}]"
                                class="day-main-experience-type app-input" value="{{ $mainExperienceType }}">
                            <input type="hidden" name="daily_main_experience_items[{{ $day }}]"
                                class="day-main-experience-item app-input" value="{{ $mainExperienceItem }}">
                        </div>
                    </div>
                    <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label
                                class="day-include-label mb-1 block text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                Day {{ $day }} Includes
                            </label>
                            <textarea name="day_includes[{{ $day }}]"
                                class="day-include w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                rows="4" placeholder="Tuliskan yang termasuk di hari ini...">{{ $dayInclude }}</textarea>
                        </div>
                        <div>
                            <label
                                class="day-exclude-label mb-1 block text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">
                                Day {{ $day }} Excludes
                            </label>
                            <textarea name="day_excludes[{{ $day }}]"
                                class="day-exclude w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                rows="4" placeholder="Tuliskan yang tidak termasuk di hari ini...">{{ $dayExclude }}</textarea>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
        @error('itinerary_items')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('itinerary_activity_items')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('itinerary_food_beverage_items')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('daily_main_experience_items.*')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('day_includes.*')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('day_excludes.*')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-2">
        <button
             class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('itineraries.index') }}"
             class="btn-secondary">Cancel</a>
    </div>
</div>

@once
    @push('styles')
        <style>
            .schedule-row.item-theme-attraction {
                border-color: #bfdbfe !important;
                background-color: rgba(59, 130, 246, 0.12) !important;
            }
            .schedule-row.item-theme-activity {
                border-color: #a7f3d0 !important;
                background-color: rgba(16, 185, 129, 0.12) !important;
            }
            .schedule-row.item-theme-fnb {
                border-color: #fde68a !important;
                background-color: rgba(245, 158, 11, 0.12) !important;
            }
            .dark .schedule-row.item-theme-attraction {
                border-color: rgba(59, 130, 246, 0.45) !important;
                background-color: rgba(30, 64, 175, 0.25) !important;
            }
            .dark .schedule-row.item-theme-activity {
                border-color: rgba(16, 185, 129, 0.5) !important;
                background-color: rgba(6, 78, 59, 0.35) !important;
            }
            .dark .schedule-row.item-theme-fnb {
                border-color: rgba(245, 158, 11, 0.5) !important;
                background-color: rgba(120, 53, 15, 0.35) !important;
            }
            .day-start-point-card.theme-airport,
            .day-end-point-card.theme-airport {
                border-color: #a5b4fc !important;
                background-color: rgba(129, 140, 248, 0.16) !important;
            }
            .day-start-point-card.theme-hotel,
            .day-end-point-card.theme-hotel {
                border-color: #93c5fd !important;
                background-color: rgba(59, 130, 246, 0.12) !important;
            }
            .dark .day-start-point-card.theme-airport,
            .dark .day-end-point-card.theme-airport {
                border-color: rgba(129, 140, 248, 0.55) !important;
                background-color: rgba(67, 56, 202, 0.3) !important;
            }
            .dark .day-start-point-card.theme-hotel,
            .dark .day-end-point-card.theme-hotel {
                border-color: rgba(59, 130, 246, 0.5) !important;
                background-color: rgba(30, 64, 175, 0.3) !important;
            }
            .itinerary-map-marker {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 30px;
                height: 30px;
                border-radius: 9999px;
                font-size: 13px;
                font-weight: 700;
                color: #fff;
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow: 0 6px 14px rgba(15, 23, 42, 0.28);
                position: relative;
            }
            .itinerary-map-marker--attraction {
                background: #0ea5e9;
            }
            .itinerary-map-marker--activity {
                background: #10b981;
            }
            .itinerary-map-marker--fnb {
                background: #f59e0b;
            }
            .itinerary-map-marker--hotel {
                background: #2563eb;
            }
            .itinerary-map-marker--airport {
                background: #6366f1;
            }
            .itinerary-map-marker-number {
                position: absolute;
                top: -6px;
                right: -6px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 16px;
                height: 16px;
                padding: 0 4px;
                border-radius: 9999px;
                font-size: 9px;
                font-weight: 700;
                color: #fff;
                background: #0f172a;
                border: 1px solid rgba(255, 255, 255, 0.95);
            }
            .itinerary-map-travel-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 20px;
                padding: 0 8px;
                border-radius: 9999px;
                font-size: 10px;
                font-weight: 700;
                color: #fff;
                background: rgba(15, 23, 42, 0.86);
                border: 1px solid rgba(255, 255, 255, 0.8);
            }
        </style>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (() => {
                const inquiryPreviewData = @json($inquiryPreviewData);
                const inquirySelect = document.getElementById('inquiry-select');
                const detailEmpty = document.getElementById('inquiry-detail-empty');
                const detailContent = document.getElementById('inquiry-detail-content');
                const detailField = (id) => document.getElementById(id);
                const setDetail = () => {
                    if (!inquirySelect || !detailEmpty || !detailContent) return;
                    const key = String(inquirySelect.value || '');
                    const detail = inquiryPreviewData[key] || null;
                    if (!detail) {
                        detailEmpty.classList.remove('hidden');
                        detailContent.classList.add('hidden');
                        return;
                    }
                    detailEmpty.classList.add('hidden');
                    detailContent.classList.remove('hidden');
                    detailField('inq-detail-number').textContent = detail.inquiry_number || '-';
                    detailField('inq-detail-customer').textContent = detail.customer || '-';
                    detailField('inq-detail-status').textContent = detail.status || '-';
                    detailField('inq-detail-priority').textContent = detail.priority || '-';
                    detailField('inq-detail-source').textContent = detail.source || '-';
                    detailField('inq-detail-assigned').textContent = detail.assigned_to || '-';
                    detailField('inq-detail-deadline').textContent = detail.deadline || '-';
                    detailField('inq-detail-created').textContent = detail.created_at || '-';
                    detailField('inq-detail-notes').innerHTML = detail.notes || '-';
                    detailField('inq-detail-reminder-note').innerHTML = detail.reminder_note || '-';
                    detailField('inq-detail-reminder-reason').innerHTML = detail.reminder_reason || '-';
                };
                inquirySelect?.addEventListener('change', setDetail);
                setDetail();

                const daySections = document.getElementById('day-sections');
                const durationInput = document.getElementById('duration-days');
                const durationNightsInput = document.getElementById('duration-nights');
                const hotelStaysHidden = document.getElementById('hotel-stays-hidden');
                const mapEl = document.getElementById('itinerary-map');
                const mapDayTabsEl = document.getElementById('itinerary-map-day-tabs');
                const mapLegendEl = document.getElementById('itinerary-map-legend');
                const form = daySections?.closest('form');
                if (!daySections || !durationInput) return;
                let itineraryMap = null;
                let itineraryMarkerLayer = null;
                let itineraryRouteLayers = [];
                const routePalette = ['#2563eb', '#16a34a', '#ea580c', '#7c3aed', '#db2777', '#0891b2'];
                const toMin = (t) => /^\d{2}:\d{2}$/.test(t || '') ? (parseInt(t.slice(0, 2), 10) * 60) + parseInt(t.slice(
                    3, 5), 10) : null;
                const fromMin = (m) => {
                    const n = Math.max(0, Math.min(1439, m));
                    return `${String(Math.floor(n/60)).padStart(2,'0')}:${String(n%60).padStart(2,'0')}`;
                };
                const normalizePointType = (value, fallback = '') => {
                    const type = String(value || '').trim();
                    return type !== '' ? type : fallback;
                };
                const isHotelPointType = (value) => normalizePointType(value) === 'hotel';
                const getRowSelection = (row) => {
                    const typeFieldValue = String(row.querySelector('.item-type')?.value || '').trim();
                    const datasetTypeValue = String(row.dataset.itemType || '').trim();
                    const candidates = [{
                            type: 'attraction',
                            select: row.querySelector('.item-attraction')
                        },
                        {
                            type: 'activity',
                            select: row.querySelector('.item-activity')
                        },
                        {
                            type: 'fnb',
                            select: row.querySelector('.item-fnb')
                        },
                    ];
                    const byType = {};
                    candidates.forEach((candidate) => {
                        byType[candidate.type] = candidate.select;
                    });
                    const fallbackType = typeFieldValue === 'activity' || typeFieldValue === 'fnb' ?
                        typeFieldValue :
                        (datasetTypeValue === 'activity' || datasetTypeValue === 'fnb' ? datasetTypeValue :
                            'attraction');
                    const preferredTypes = [];
                    [typeFieldValue, datasetTypeValue].forEach((type) => {
                        if ((type === 'attraction' || type === 'activity' || type === 'fnb') &&
                            !preferredTypes.includes(type)) {
                            preferredTypes.push(type);
                        }
                    });
                    candidates.forEach((candidate) => {
                        if (!preferredTypes.includes(candidate.type)) {
                            preferredTypes.push(candidate.type);
                        }
                    });

                    const resolveOption = (select) => {
                        if (!select) return null;
                        const option = select.selectedOptions?.[0] || null;
                        const value = String(select.value || '').trim();
                        if (!option || value === '') return null;
                        return option;
                    };

                    for (const type of preferredTypes) {
                        const select = byType[type] || null;
                        const option = resolveOption(select);
                        if (option) {
                            return {
                                type,
                                select,
                                option,
                                source: type === typeFieldValue ? 'type-field' : (type === datasetTypeValue ?
                                    'dataset' : 'fallback')
                            };
                        }
                    }

                    return {
                        type: fallbackType,
                        select: byType[fallbackType] || byType.attraction || null,
                        option: null,
                        source: 'empty'
                    };
                };
                const rowType = (r) => getRowSelection(r).type;
                const activeSelect = (r) => getRowSelection(r).select;
                const selected = (r) => getRowSelection(r).option !== null;
                const toggleType = (r, t, reset = true) => {
                    const type = t === 'activity' || t === 'fnb' ? t : 'attraction';
                    r.dataset.itemType = type;
                    r.querySelector('.item-type').value = type;
                    const a = r.querySelector('.item-attraction');
                    const b = r.querySelector('.item-activity');
                    const f = r.querySelector('.item-fnb');
                    if (type === 'activity') {
                        a.classList.add('hidden');
                        b.classList.remove('hidden');
                        f.classList.add('hidden');
                        if (reset) {
                            a.value = '';
                            f.value = '';
                        }
                    } else if (type === 'fnb') {
                        a.classList.add('hidden');
                        b.classList.add('hidden');
                        f.classList.remove('hidden');
                        if (reset) {
                            a.value = '';
                            b.value = '';
                        }
                    } else {
                        a.classList.remove('hidden');
                        b.classList.add('hidden');
                        f.classList.add('hidden');
                        if (reset) {
                            b.value = '';
                            f.value = '';
                        }
                    }
                };
                let itineraryDataLayer = null;
                let mapBusy = false;
                let renderPendingAfterMove = false;
                let hardResetInProgress = false;
                let mapRenderSeq = 0;
                let activeRouteFetchController = null;
                let mapSelectedDay = null;
                const initItineraryMap = () => {
                    if (!mapEl || typeof L === 'undefined') return null;
                    if (itineraryMap) return itineraryMap;
                    itineraryMap = L.map(mapEl, {
                        zoomControl: true,
                        preferCanvas: false,
                        renderer: L.svg(),
                    }).setView([-2.5, 118], 4);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19,
                    }).addTo(itineraryMap);
                    itineraryDataLayer = L.featureGroup().addTo(itineraryMap);
                    itineraryMarkerLayer = itineraryDataLayer;
                    itineraryMap.on('zoomstart movestart', () => {
                        mapBusy = true;
                    });
                    itineraryMap.on('zoomend moveend', () => {
                        mapBusy = false;
                        if (renderPendingAfterMove) {
                            renderPendingAfterMove = false;
                            requestRenderItineraryMap();
                        }
                    });
                    setTimeout(() => {
                        if (!itineraryMap) return;
                        itineraryMap.invalidateSize();
                    }, 0);
                    return itineraryMap;
                };
                const hardResetItineraryMap = () => {
                    if (hardResetInProgress) return;
                    hardResetInProgress = true;
                    try {
                        activeRouteFetchController?.abort();
                    } catch (_) {}
                    activeRouteFetchController = null;
                    try {
                        if (itineraryMap) {
                            itineraryMap.off();
                            itineraryMap.remove();
                        }
                    } catch (_) {}
                    itineraryMap = null;
                    itineraryDataLayer = null;
                    itineraryMarkerLayer = null;
                    itineraryRouteLayers = [];
                    mapBusy = false;
                    renderPendingAfterMove = false;
                    setTimeout(() => {
                        hardResetInProgress = false;
                        requestRenderItineraryMap();
                    }, 0);
                };
                const clearItineraryMapLayers = () => {
                    if (!itineraryMap) return;
                    if (itineraryDataLayer) {
                        try {
                            itineraryDataLayer.clearLayers();
                        } catch (_) {}
                    }
                    itineraryRouteLayers = [];
                };
                const canRenderMapNow = (mapInstance) => {
                    if (!mapEl || !mapInstance) return false;
                    if (!mapEl.isConnected) return false;
                    const rect = mapEl.getBoundingClientRect();
                    if (!Number.isFinite(rect.width) || !Number.isFinite(rect.height) || rect.width < 8 || rect.height < 8) {
                        return false;
                    }
                    return true;
                };
                const isLatitudeInRange = (value) => Number.isFinite(value) && value >= -90 && value <= 90;
                const isLongitudeInRange = (value) => Number.isFinite(value) && value >= -180 && value <= 180;
                const normalizeLatLngPair = (rawLat, rawLng) => {
                    let lat = Number(rawLat);
                    let lng = Number(rawLng);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
                    if (isLatitudeInRange(lat) && isLongitudeInRange(lng)) {
                        return { lat, lng };
                    }
                    // Guard for swapped coordinates (common data-entry issue).
                    if (isLatitudeInRange(lng) && isLongitudeInRange(lat)) {
                        return { lat: lng, lng: lat };
                    }
                    return null;
                };
                const isFiniteLatLng = (point) => {
                    if (!point || typeof point !== 'object') return false;
                    const normalized = normalizeLatLngPair(point.lat, point.lng);
                    return normalized !== null;
                };
                const normalizeMapPointType = (rawType) => {
                    const type = String(rawType || '').trim().toLowerCase();
                    if (type === 'activity' || type === 'fnb' || type === 'hotel' || type === 'airport' || type === 'attraction') {
                        return type;
                    }
                    return 'attraction';
                };
                const markerTypeClass = (type) => {
                    const normalized = normalizeMapPointType(type);
                    if (normalized === 'activity') return 'itinerary-map-marker--activity';
                    if (normalized === 'fnb') return 'itinerary-map-marker--fnb';
                    if (normalized === 'hotel') return 'itinerary-map-marker--hotel';
                    if (normalized === 'airport') return 'itinerary-map-marker--airport';
                    return 'itinerary-map-marker--attraction';
                };
                const markerTypeIcon = (type) => {
                    const normalized = normalizeMapPointType(type);
                    if (normalized === 'activity') return 'fa-solid fa-person-hiking';
                    if (normalized === 'fnb') return 'fa-solid fa-utensils';
                    if (normalized === 'hotel') return 'fa-solid fa-bed';
                    if (normalized === 'airport') return 'fa-solid fa-plane';
                    return 'fa-solid fa-location-dot';
                };
                const markerByTypeWithOrder = (type, order) => L.divIcon({
                    className: '',
                    html: `<span class="itinerary-map-marker ${markerTypeClass(type)}"><i class="${markerTypeIcon(type)}"></i><span class="itinerary-map-marker-number">${order}</span></span>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15],
                });
                const parseOptionPoint = (option, typeHint = null) => {
                    if (!option) return null;
                    const normalized = normalizeLatLngPair(
                        parseFloat(option.dataset.latitude || ''),
                        parseFloat(option.dataset.longitude || ''),
                    );
                    if (!normalized) return null;
                    return {
                        lat: normalized.lat,
                        lng: normalized.lng,
                        name: String(option.textContent || '').trim() || '-',
                        type: normalizeMapPointType(typeHint || option.dataset.pointType || ''),
                    };
                };
                const toLeafletLatLng = (rawLat, rawLng) => {
                    const normalized = normalizeLatLngPair(rawLat, rawLng);
                    if (!normalized) return null;
                    try {
                        const latLng = L.latLng(normalized.lat, normalized.lng);
                        if (!Number.isFinite(latLng.lat) || !Number.isFinite(latLng.lng)) return null;
                        return latLng;
                    } catch (_) {
                        return null;
                    }
                };
                const closestPointOnRoute = (routeCoords, targetLatLng) => {
                    if (!Array.isArray(routeCoords) || routeCoords.length === 0 || !targetLatLng) return null;
                    let best = null;
                    let bestDistance = Number.POSITIVE_INFINITY;
                    routeCoords.forEach((coord) => {
                        if (!coord || !Number.isFinite(coord.lat) || !Number.isFinite(coord.lng)) return;
                        const dLat = coord.lat - targetLatLng.lat;
                        const dLng = coord.lng - targetLatLng.lng;
                        const distanceSq = (dLat * dLat) + (dLng * dLng);
                        if (distanceSq < bestDistance) {
                            bestDistance = distanceSq;
                            best = coord;
                        }
                    });
                    return best;
                };
                const fetchRoadRouteGeometry = async (latLngPoints, signal) => {
                    if (!Array.isArray(latLngPoints) || latLngPoints.length < 2) return null;
                    const coordinateString = latLngPoints
                        .map((point) => `${point.lng},${point.lat}`)
                        .join(';');
                    const endpoint =
                        `https://router.project-osrm.org/route/v1/driving/${coordinateString}?overview=full&geometries=geojson`;
                    const response = await fetch(endpoint, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                        },
                        signal,
                    });
                    if (!response.ok) return null;
                    const payload = await response.json();
                    const coordinates = payload?.routes?.[0]?.geometry?.coordinates;
                    if (!Array.isArray(coordinates) || coordinates.length < 2) return null;
                    const routePoints = [];
                    coordinates.forEach((coord) => {
                        if (!Array.isArray(coord) || coord.length < 2) return;
                        const latLng = toLeafletLatLng(coord[1], coord[0]);
                        if (latLng) routePoints.push(latLng);
                    });
                    return routePoints.length >= 2 ? routePoints : null;
                };
                const getDayEndPoint = (day) => {
                    const section = daySections.querySelector(`.day-section[data-day="${day}"]`);
                    if (!section) return null;
                    const endType = normalizePointType(section.querySelector('.day-end-point-type')?.value || '');
                    if (endType !== 'airport' && endType !== 'hotel') return null;
                    return parseOptionPoint(section.querySelector('.day-end-point-item')?.selectedOptions?.[0] || null, endType);
                };
                const collectDayRoutePoints = (day) => {
                    const section = daySections.querySelector(`.day-section[data-day="${day}"]`);
                    if (!section) return [];
                    const points = [];
                    const startType = normalizePointType(section.querySelector('.day-start-point-type')?.value || '');
                    let startPoint = null;
                    if (startType === 'previous_day_end' && day > 1) {
                        startPoint = getDayEndPoint(day - 1);
                    } else {
                        const startOpt = section.querySelector('.day-start-point-item')?.selectedOptions?.[0] || null;
                        startPoint = parseOptionPoint(startOpt, startType);
                    }
                    if ((startType === 'airport' || startType === 'hotel') && startPoint) {
                        points.push({
                            day,
                            role: 'start',
                            type: startPoint.type || normalizeMapPointType(startType),
                            lat: startPoint.lat,
                            lng: startPoint.lng,
                            name: startPoint.name,
                            travelMinutes: Math.max(0, parseInt(section.querySelector('.day-start-travel')?.value || '0', 10) || 0),
                        });
                    } else if (startType === 'previous_day_end' && startPoint) {
                        points.push({
                            day,
                            role: 'start',
                            type: startPoint.type || 'hotel',
                            lat: startPoint.lat,
                            lng: startPoint.lng,
                            name: startPoint.name,
                            travelMinutes: Math.max(0, parseInt(section.querySelector('.day-start-travel')?.value || '0', 10) || 0),
                        });
                    }
                    const rows = [...section.querySelectorAll('.schedule-row')];
                    rows.forEach((row) => {
                        if (!selected(row)) return;
                        const selection = getRowSelection(row);
                        const option = selection.option;
                        const point = parseOptionPoint(option, selection.type);
                        if (!point) return;
                        const order = Number(row.querySelector('.item-order')?.value || '0');
                        points.push({
                            day,
                            role: 'schedule',
                            order: Number.isFinite(order) && order > 0 ? order : 9999,
                            type: selection.type,
                            lat: point.lat,
                            lng: point.lng,
                            name: point.name,
                            travelMinutes: Math.max(0, parseInt(row.querySelector('.item-travel')?.value || '0', 10) || 0),
                        });
                    });
                    points.sort((a, b) => {
                        const aRank = a.role === 'start' ? 0 : (a.role === 'schedule' ? 1 : 2);
                        const bRank = b.role === 'start' ? 0 : (b.role === 'schedule' ? 1 : 2);
                        if (aRank !== bRank) return aRank - bRank;
                        return (a.order || 0) - (b.order || 0);
                    });
                    const endType = normalizePointType(section.querySelector('.day-end-point-type')?.value || '');
                    const endOpt = section.querySelector('.day-end-point-item')?.selectedOptions?.[0] || null;
                    const endPoint = parseOptionPoint(endOpt, endType);
                    if ((endType === 'airport' || endType === 'hotel') && endPoint) {
                        points.push({
                            day,
                            role: 'end',
                            type: endPoint.type || normalizeMapPointType(endType),
                            lat: endPoint.lat,
                            lng: endPoint.lng,
                            name: endPoint.name,
                            travelMinutes: 0,
                        });
                    }
                    return points;
                };
                const refreshMapDayOptions = () => {
                    if (!mapDayTabsEl) return;
                    const totalDays = Math.max(1, parseInt(durationInput.value || '1', 10));
                    if (mapSelectedDay !== null && (mapSelectedDay < 1 || mapSelectedDay > totalDays)) {
                        mapSelectedDay = null;
                    }
                    let html = `
                        <button type="button" data-map-day=""
                            class="itinerary-map-day-tab inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold transition ${mapSelectedDay === null ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200' : 'border-gray-300 bg-white text-gray-700 hover:border-blue-300 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-blue-600 dark:hover:text-blue-300'}"
                            aria-pressed="${mapSelectedDay === null ? 'true' : 'false'}">All Days</button>
                    `;
                    for (let day = 1; day <= totalDays; day++) {
                        const active = mapSelectedDay === day;
                        html += `
                            <button type="button" data-map-day="${day}"
                                class="itinerary-map-day-tab inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold transition ${active ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200' : 'border-gray-300 bg-white text-gray-700 hover:border-blue-300 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-blue-600 dark:hover:text-blue-300'}"
                                aria-pressed="${active ? 'true' : 'false'}">Day ${day}</button>
                        `;
                    }
                    mapDayTabsEl.innerHTML = html;
                };
                const renderMapLegend = (dayList) => {
                    if (!mapLegendEl) return;
                    if (!Array.isArray(dayList) || dayList.length === 0) {
                        mapLegendEl.innerHTML = '<span class="text-[11px] text-gray-500 dark:text-gray-400">No day route selected.</span>';
                        return;
                    }
                    let html = '';
                    dayList.forEach((day) => {
                        const color = routePalette[(Number(day) - 1) % routePalette.length];
                        html += `
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white/90 px-2 py-0.5 dark:border-gray-700 dark:bg-gray-800/80">
                                <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:${color}"></span>
                                <span>Day ${day}</span>
                            </span>
                        `;
                    });
                    mapLegendEl.innerHTML = html;
                };
                const renderItineraryMap = async () => {
                    const map = initItineraryMap();
                    if (!map || !itineraryDataLayer) return;
                    if (!canRenderMapNow(map)) {
                        renderPendingAfterMove = true;
                        return;
                    }
                    if (mapBusy) {
                        renderPendingAfterMove = true;
                        return;
                    }
                    try {
                        const renderSeq = ++mapRenderSeq;
                        try {
                            activeRouteFetchController?.abort();
                        } catch (_) {}
                        activeRouteFetchController = typeof AbortController !== 'undefined' ? new AbortController() :
                            null;
                        const routeSignal = activeRouteFetchController?.signal;
                        map.invalidateSize(false);
                        refreshMapDayOptions();
                        clearItineraryMapLayers();
                        const totalDays = Math.max(1, parseInt(durationInput.value || '1', 10));
                        const dayFilter = mapSelectedDay !== null && mapSelectedDay >= 1 && mapSelectedDay <= totalDays ?
                            mapSelectedDay : null;
                        const dayList = dayFilter ? [dayFilter] : Array.from({ length: totalDays }, (_, idx) => idx + 1);
                        renderMapLegend(dayList);
                        const groupedRoutes = dayList.map((day) => ({
                            day,
                            points: collectDayRoutePoints(day).filter(isFiniteLatLng),
                        })).filter((entry) => entry.points.length > 0);
                        if (!groupedRoutes.length) {
                            map.setView([-2.5, 118], 4);
                            return;
                        }
                        const bounds = [];
                        let markerIndex = 1;
                        for (const {
                                day,
                                points
                            }
                            of groupedRoutes) {
                            if (renderSeq !== mapRenderSeq) return;
                            const safePoints = points.filter(isFiniteLatLng);
                            if (!safePoints.length) continue;
                            const polylineCoords = [];
                            safePoints.forEach((point) => {
                                const latLng = toLeafletLatLng(point.lat, point.lng);
                                if (!latLng) return;
                                bounds.push([latLng.lat, latLng.lng]);
                                polylineCoords.push(latLng);
                                const markerType = normalizeMapPointType(point.type || (point.role === 'end' || point.role === 'start' ? 'hotel' : 'attraction'));
                                const marker = L.marker(latLng, {
                                    icon: markerByTypeWithOrder(markerType, markerIndex),
                                }).addTo(itineraryDataLayer);
                                marker.bindPopup(`#${markerIndex} | Day ${day} | ${markerType.toUpperCase()} | ${point.name}`);
                                markerIndex += 1;
                            });
                            const validPolylineCoords = polylineCoords
                                .filter((coord) => coord && Number.isFinite(coord.lat) && Number.isFinite(coord.lng));
                            let displayedRouteCoords = validPolylineCoords;
                            if (validPolylineCoords.length >= 2) {
                                let skipRouteDrawForThisSegment = false;
                                // Keep OSRM requests bounded to avoid URL overflow and reduce flakiness.
                                if (validPolylineCoords.length <= 25) {
                                    try {
                                        const roadRoute = await fetchRoadRouteGeometry(validPolylineCoords, routeSignal);
                                        if (renderSeq !== mapRenderSeq) return;
                                        if (roadRoute && roadRoute.length >= 2) {
                                            displayedRouteCoords = roadRoute;
                                        }
                                    } catch (fetchError) {
                                        if (renderSeq !== mapRenderSeq) return;
                                        if (fetchError?.name === 'AbortError') {
                                            // Render is being superseded by a newer run; skip drawing this segment
                                            // to avoid transient straight-line artifacts.
                                            skipRouteDrawForThisSegment = true;
                                        }
                                        if (fetchError?.name !== 'AbortError') {
                                            console.warn('Road route fetch failed, fallback to straight polyline.', fetchError);
                                        }
                                    }
                                }
                                if (!skipRouteDrawForThisSegment) {
                                    const route = L.polyline(displayedRouteCoords, {
                                        color: routePalette[(Number(day) - 1) % routePalette.length],
                                        weight: 4,
                                        opacity: 0.95,
                                        interactive: false,
                                        bubblingMouseEvents: false,
                                    }).addTo(itineraryDataLayer);
                                    itineraryRouteLayers.push(route);
                                }
                            }
                            for (let i = 0; i < safePoints.length - 1; i++) {
                                const from = safePoints[i];
                                const to = safePoints[i + 1];
                                if (!isFiniteLatLng(from) || !isFiniteLatLng(to)) continue;
                                const minutes = Math.max(0, Number(from.travelMinutes || 0));
                                if (minutes <= 0) continue;
                                const normFrom = normalizeLatLngPair(from.lat, from.lng);
                                const normTo = normalizeLatLngPair(to.lat, to.lng);
                                if (!normFrom || !normTo) continue;
                                const midLat = (normFrom.lat + normTo.lat) / 2;
                                const midLng = (normFrom.lng + normTo.lng) / 2;
                                const midLatLng = toLeafletLatLng(midLat, midLng);
                                if (!midLatLng) continue;
                                const badgeLatLng = closestPointOnRoute(displayedRouteCoords, midLatLng) || midLatLng;
                                const badge = L.tooltip({
                                    permanent: true,
                                    direction: 'top',
                                    className: 'itinerary-map-travel-badge',
                                    offset: [0, -6],
                                    interactive: false,
                                })
                                    .setLatLng(badgeLatLng)
                                    .setContent(`${minutes}m`)
                                    .addTo(itineraryDataLayer);
                                itineraryRouteLayers.push(badge);
                            }
                        }
                        const validBounds = bounds
                            .filter((coord) =>
                                Array.isArray(coord) &&
                                normalizeLatLngPair(coord[0], coord[1]) !== null
                            );
                        if (validBounds.length === 0) {
                            map.setView([-2.5, 118], 4);
                            return;
                        }
                        if (validBounds.length === 1) {
                            map.setView(validBounds[0], 13);
                        } else {
                            map.fitBounds(validBounds, { padding: [24, 24] });
                        }
                    } catch (error) {
                        if (error?.name !== 'AbortError') {
                            console.error('Failed to render itinerary map:', error);
                        }
                        setTimeout(() => {
                            hardResetItineraryMap();
                        }, 0);
                    }
                };
                let renderQueued = false;
                const requestRenderItineraryMap = () => {
                    if (renderQueued) return;
                    renderQueued = true;
                    const runner = () => {
                        renderQueued = false;
                        renderItineraryMap();
                    };
                    if (typeof window !== 'undefined' && typeof window.requestAnimationFrame === 'function') {
                        window.requestAnimationFrame(runner);
                    } else {
                        setTimeout(runner, 0);
                    }
                };
                mapDayTabsEl?.addEventListener('click', (event) => {
                    const target = event.target instanceof HTMLElement ? event.target.closest('button[data-map-day]') : null;
                    if (!target) return;
                    const dayRaw = String(target.dataset.mapDay ?? '').trim();
                    const parsedDay = Number(dayRaw);
                    mapSelectedDay = dayRaw === '' || !Number.isFinite(parsedDay) || parsedDay < 1 ? null : parsedDay;
                    requestRenderItineraryMap();
                });
                const rebuildTravelConnectors = (sec) => {
                    const container = sec.querySelector('.day-items');
                    if (!container) return;
                    container.querySelectorAll('.travel-connector').forEach((el) => el.remove());
                    const rows = [...container.querySelectorAll('.schedule-row')];
                    rows.forEach((row, index) => {
                        const hiddenTravel = row.querySelector('.item-travel');
                        if (!hiddenTravel) return;
                        const isLast = index === rows.length - 1;
                        const connector = document.createElement('div');
                        connector.className = 'travel-connector mt-2 flex min-h-[74px] items-stretch overflow-hidden rounded-lg border border-sky-200 bg-sky-50 dark:border-sky-700/60 dark:bg-sky-900/25';
                        connector.innerHTML = `
                <div class="flex w-12 shrink-0 items-center justify-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true" focusable="false">
                        <path d="M5.5 11.5L7.3 6.9C7.6 6.1 8.3 5.5 9.2 5.5h5.6c.9 0 1.6.6 1.9 1.4l1.8 4.6c1 .2 1.8 1.1 1.8 2.2v2.3c0 .8-.7 1.5-1.5 1.5h-.5a2.3 2.3 0 01-4.6 0h-4.4a2.3 2.3 0 01-4.6 0h-.5c-.8 0-1.5-.7-1.5-1.5v-2.3c0-1.1.8-2 1.8-2.2zm3.1-4.2L7.2 11h9.6l-1.4-3.7a.8.8 0 00-.7-.5H9.3c-.3 0-.6.2-.7.5zM8.2 18.9c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9zm7.6 0c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9z"/>
                    </svg>
                </div>
                <div class="flex-1 p-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400">${isLast ? 'Travel to End Point (minutes)' : 'Travel to next item (minutes)'}</label>
                    <input type="number" min="0" step="5" class="travel-connector-input mt-1 dark:border-gray-600 app-input">
                </div>
            `;
                        const input = connector.querySelector('.travel-connector-input');
                        input.value = hiddenTravel.value || '';
                        input.addEventListener('input', () => {
                            const parsed = parseInt(input.value || '', 10);
                            hiddenTravel.value = Number.isFinite(parsed) ? String(Math.max(0, parsed)) :
                                '';
                            recalcNoConnectorRebuild();
                        });
                        row.insertAdjacentElement('afterend', connector);
                    });
                };
                const getPointLabelFromTypeAndItem = (typeSelect, itemSelect, previousDayEndLabel = 'Not set') => {
                    const type = normalizePointType(typeSelect?.value || '');
                    if (type === 'previous_day_end') {
                        return previousDayEndLabel || 'Not set';
                    }
                    if (!isHotelPointType(type) && type !== 'airport') {
                        return 'Not set';
                    }
                    const option = itemSelect?.selectedOptions?.[0];
                    if (!option || !String(option.value || '').trim()) {
                        return 'Not set';
                    }
                    return (option.textContent || '').trim() || 'Not set';
                };
                const recalcDay = async (sec) => {
                    const rows = [...sec.querySelectorAll('.schedule-row')];
                    const chosen = rows.filter(selected);
                    syncMainExperienceSelection(sec);
                    const start = toMin(sec.querySelector('.day-start-time')?.value || '');
                    const startTravelRaw = sec.querySelector('.day-start-travel')?.value || '';
                    const startTravelMinutes = startTravelRaw !== '' ? Math.max(0, parseInt(startTravelRaw, 10)) :
                    0;
                    const day = Number(sec.dataset.day || '1');
                    const prevSec = day > 1 ? daySections.querySelector(`.day-section[data-day="${day - 1}"]`) :
                        null;
                    const prevEndLabel = prevSec ?
                        getPointLabelFromTypeAndItem(
                            prevSec.querySelector('.day-end-point-type'),
                            prevSec.querySelector('.day-end-point-item'),
                            'Not set',
                        ) :
                        'Not set';
                    const startPointLabel = getPointLabelFromTypeAndItem(
                        sec.querySelector('.day-start-point-type'),
                        sec.querySelector('.day-start-point-item'),
                        prevEndLabel,
                    );
                    const endPointLabel = getPointLabelFromTypeAndItem(
                        sec.querySelector('.day-end-point-type'),
                        sec.querySelector('.day-end-point-item'),
                        'Not set',
                    );
                    const startAtLabel = sec.querySelector('.day-starts-at-label');
                    const endsAtLabel = sec.querySelector('.day-ends-at-label');
                    const dayEndTimeInput = sec.querySelector('.day-end-time');
                    if (startAtLabel) startAtLabel.textContent = startPointLabel;
                    if (endsAtLabel) endsAtLabel.textContent = endPointLabel;
                    let cur = start;
                    rows.forEach((r) => {
                        const seq = r.querySelector('.item-seq-badge');
                        if (!chosen.includes(r)) {
                            r.querySelector('.item-start').value = '';
                            r.querySelector('.item-end').value = '';
                            r.querySelector('.item-order').value = '';
                            if (seq) seq.textContent = '-';
                        }
                    });
                    chosen.forEach((r, i) => {
                        r.querySelector('.item-order').value = String(i + 1);
                        const seq = r.querySelector('.item-seq-badge');
                        if (seq) seq.textContent = String(i + 1);
                    });
                    if (!chosen.length || start === null) {
                        if (dayEndTimeInput) dayEndTimeInput.value = '';
                        return;
                    }
                    cur = start + (Number.isFinite(startTravelMinutes) ? startTravelMinutes : 0);
                    chosen.forEach((r) => {
                        const opt = activeSelect(r)?.selectedOptions?.[0];
                        const dur = Math.max(1, parseInt(opt?.dataset?.duration || '120', 10));
                        r.querySelector('.item-start').value = fromMin(cur);
                        r.querySelector('.item-end').value = fromMin(cur + dur);
                        const travel = Math.max(0, parseInt(r.querySelector('.item-travel').value || '0',
                            10));
                        cur += dur + travel;
                    });
                    if (dayEndTimeInput) dayEndTimeInput.value = fromMin(cur);
                };
                const recalcAll = async () => {
                    for (const sec of [...daySections.querySelectorAll('.day-section')].sort((a, b) => Number(a
                            .dataset.day) - Number(b.dataset.day))) await recalcDay(sec);
                };
                const reindex = () => {
                    let ai = 0,
                        bi = 0,
                        fi = 0;
                    [...daySections.querySelectorAll('.day-section')].sort((a, b) => Number(a.dataset.day) - Number(b
                        .dataset.day)).forEach((sec) => {
                        let order = 0;
                        const day = Number(sec.dataset.day || '1');
                        sec.querySelectorAll('.schedule-row').forEach((r) => {
                            const a = r.querySelector('.item-attraction'),
                                b = r.querySelector('.item-activity'),
                                f = r.querySelector('.item-fnb'),
                                p = r.querySelector('.item-pax'),
                                d = r.querySelector('.item-day'),
                                s = r.querySelector('.item-start'),
                                e = r.querySelector('.item-end'),
                                t = r.querySelector('.item-travel'),
                                o = r.querySelector('.item-order');
                            [a, b, f, p, d, s, e, t, o].forEach((el) => el?.removeAttribute('name'));
                            d.value = String(day);
                            if (!selected(r)) return;
                            order += 1;
                            o.value = String(order);
                            if (rowType(r) === 'activity') {
                                b.name = `itinerary_activity_items[${bi}][activity_id]`;
                                d.name = `itinerary_activity_items[${bi}][day_number]`;
                                p.name = `itinerary_activity_items[${bi}][pax]`;
                                s.name = `itinerary_activity_items[${bi}][start_time]`;
                                e.name = `itinerary_activity_items[${bi}][end_time]`;
                                t.name = `itinerary_activity_items[${bi}][travel_minutes_to_next]`;
                                o.name = `itinerary_activity_items[${bi}][visit_order]`;
                                bi++;
                            } else if (rowType(r) === 'fnb') {
                                f.name = `itinerary_food_beverage_items[${fi}][food_beverage_id]`;
                                d.name = `itinerary_food_beverage_items[${fi}][day_number]`;
                                p.name = `itinerary_food_beverage_items[${fi}][pax]`;
                                s.name = `itinerary_food_beverage_items[${fi}][start_time]`;
                                e.name = `itinerary_food_beverage_items[${fi}][end_time]`;
                                t.name = `itinerary_food_beverage_items[${fi}][travel_minutes_to_next]`;
                                o.name = `itinerary_food_beverage_items[${fi}][visit_order]`;
                                fi++;
                            } else {
                                a.name = `itinerary_items[${ai}][tourist_attraction_id]`;
                                d.name = `itinerary_items[${ai}][day_number]`;
                                s.name = `itinerary_items[${ai}][start_time]`;
                                e.name = `itinerary_items[${ai}][end_time]`;
                                t.name = `itinerary_items[${ai}][travel_minutes_to_next]`;
                                o.name = `itinerary_items[${ai}][visit_order]`;
                                ai++;
                            }
                        });
                    });
                };
                const syncDayPointOptionRules = () => {
                    const sections = [...daySections.querySelectorAll('.day-section')].sort((a, b) => Number(a.dataset
                        .day) - Number(b.dataset.day));
                    const totalDays = sections.length;
                    sections.forEach((section, idx) => {
                        const day = idx + 1;
                        section.dataset.day = String(day);
                        section.querySelector('.day-title-label') && (section.querySelector('.day-title-label')
                            .textContent = `Day ${day}`);
                        section.querySelector('.day-start-point-label') && (section.querySelector(
                            '.day-start-point-label').textContent = `Day ${day} Start Point`);
                        section.querySelector('.day-end-point-label') && (section.querySelector(
                            '.day-end-point-label').textContent = `Day ${day} End Point`);
                        section.querySelector('.day-include-label') && (section.querySelector(
                            '.day-include-label').textContent = `Day ${day} Includes`);
                        section.querySelector('.day-exclude-label') && (section.querySelector(
                            '.day-exclude-label').textContent = `Day ${day} Excludes`);

                        const startType = section.querySelector('.day-start-point-type');
                        const startItem = section.querySelector('.day-start-point-item');
                        const startRoomSelect = section.querySelector('.day-start-room-select');
                        const startRoom = section.querySelector('.day-start-room-count');
                        const endType = section.querySelector('.day-end-point-type');
                        const endItem = section.querySelector('.day-end-point-item');
                        const endRoomSelect = section.querySelector('.day-end-room-select');
                        const endRoom = section.querySelector('.day-end-room-count');
                        const transportUnit = section.querySelector('.day-transport-unit');
                        const transportDay = section.querySelector('.day-transport-day');
                        const dayStartTimeInput = section.querySelector('.day-start-time');
                        const startTravelInput = section.querySelector('.day-start-travel');
                        const dayIncludeInput = section.querySelector('.day-include');
                        const dayExcludeInput = section.querySelector('.day-exclude');
                        const mainExperienceTypeInput = section.querySelector('.day-main-experience-type');
                        const mainExperienceItemInput = section.querySelector('.day-main-experience-item');

                        if (startType) startType.name = `daily_start_point_types[${day}]`;
                        if (startItem) startItem.name = `daily_start_point_items[${day}]`;
                        if (startRoomSelect) startRoomSelect.name = `daily_start_point_room_ids[${day}]`;
                        if (startRoom) startRoom.name = `daily_start_point_room_counts[${day}]`;
                        if (endType) endType.name = `daily_end_point_types[${day}]`;
                        if (endItem) endItem.name = `daily_end_point_items[${day}]`;
                        if (endRoomSelect) endRoomSelect.name = `daily_end_point_room_ids[${day}]`;
                        if (endRoom) endRoom.name = `daily_end_point_room_counts[${day}]`;
                        if (transportUnit) transportUnit.name =
                            `daily_transport_units[${day}][transport_unit_id]`;
                        if (transportDay) {
                            transportDay.name = `daily_transport_units[${day}][day_number]`;
                            transportDay.value = String(day);
                        }
                        if (dayStartTimeInput) dayStartTimeInput.name = `day_start_times[${day}]`;
                        if (startTravelInput) startTravelInput.name = `day_start_travel_minutes[${day}]`;
                        if (dayIncludeInput) dayIncludeInput.name = `day_includes[${day}]`;
                        if (dayExcludeInput) dayExcludeInput.name = `day_excludes[${day}]`;
                        if (mainExperienceTypeInput) mainExperienceTypeInput.name =
                            `daily_main_experience_types[${day}]`;
                        if (mainExperienceItemInput) mainExperienceItemInput.name =
                            `daily_main_experience_items[${day}]`;

                        if (startType) {
                            const previousOption = startType.querySelector('option[value="previous_day_end"]');
                            if (day === 1) {
                                previousOption?.remove();
                                if (startType.value === 'previous_day_end' || startType.value === '') {
                                    startType.value = 'airport';
                                }
                            } else {
                                if (!previousOption) {
                                    startType.insertAdjacentHTML('afterbegin',
                                        '<option value="previous_day_end">Previous Day Endpoint (Auto)</option>'
                                        );
                                }
                                if (startType.value === '') {
                                    startType.value = 'previous_day_end';
                                }
                            }
                        }

                        if (endType) {
                            const airportOption = endType.querySelector('option[value="airport"]');
                            if (!airportOption) {
                                endType.insertAdjacentHTML('beforeend', '<option value="airport">Airport</option>');
                            }
                        }
                    });
                };
                const updateDayPointTheme = (section) => {
                    if (!section) return;
                    const startCard = section.querySelector('.day-start-point-card');
                    const endCard = section.querySelector('.day-end-point-card');
                    const startType = normalizePointType(section.querySelector('.day-start-point-type')?.value || '');
                    const endType = normalizePointType(section.querySelector('.day-end-point-type')?.value || '');
                    if (startCard) {
                        startCard.classList.remove('theme-airport', 'theme-hotel');
                        if (startType === 'airport') startCard.classList.add('theme-airport');
                        if (isHotelPointType(startType)) startCard.classList.add('theme-hotel');
                    }
                    if (endCard) {
                        endCard.classList.remove('theme-airport', 'theme-hotel');
                        if (endType === 'airport') endCard.classList.add('theme-airport');
                        if (isHotelPointType(endType)) endCard.classList.add('theme-hotel');
                    }
                };
                const updateScheduleRowTheme = (row) => {
                    if (!row) return;
                    const type = rowType(row);
                    row.classList.remove('item-theme-attraction', 'item-theme-activity', 'item-theme-fnb');
                    if (type === 'activity') {
                        row.classList.add('item-theme-activity');
                    } else if (type === 'fnb') {
                        row.classList.add('item-theme-fnb');
                    } else {
                        row.classList.add('item-theme-attraction');
                    }
                };
                const pointOptionCache = new WeakMap();
                const destinationInput = document.getElementById('itinerary-destination');
                const normalizeDestination = (value) => String(value || '').toLowerCase().trim();
                const matchesDestinationOption = (option) => {
                    const keyword = normalizeDestination(destinationInput?.value || '');
                    if (!keyword) return true;
                    const city = normalizeDestination(option.dataset.city);
                    const province = normalizeDestination(option.dataset.province);
                    const location = normalizeDestination(option.dataset.location);
                    const destination = normalizeDestination(option.dataset.destination);
                    return city.includes(keyword) || province.includes(keyword) || location.includes(keyword) ||
                        destination.includes(keyword);
                };
                const syncPointItemVisibility = () => {
                    daySections.querySelectorAll('.day-section').forEach((section) => {
                        const startType = section.querySelector('.day-start-point-type');
                        const startItem = section.querySelector('.day-start-point-item');
                        const startRoomWrap = section.querySelector('.day-start-room-wrap');
                        const startRoomSelect = section.querySelector('.day-start-room-select');
                        const startRoomInput = section.querySelector('.day-start-room-count');
                        const endType = section.querySelector('.day-end-point-type');
                        const endItem = section.querySelector('.day-end-point-item');
                        const endRoomWrap = section.querySelector('.day-end-room-wrap');
                        const endRoomSelect = section.querySelector('.day-end-room-select');
                        const endRoomInput = section.querySelector('.day-end-room-count');

                        const applyFilter = (typeSelect, itemSelect) => {
                            if (!typeSelect || !itemSelect) return;
                            const selectedType = normalizePointType(typeSelect.value || '');
                            const requiresItem = isHotelPointType(selectedType) || selectedType === 'airport';
                            const selectedValue = String(itemSelect.value || '');
                            let allOptions = pointOptionCache.get(itemSelect);
                            if (!allOptions) {
                                allOptions = [];
                                Array.from(itemSelect.options).forEach((opt) => {
                                    const cacheClone = opt.cloneNode(true);
                                    cacheClone.hidden = false;
                                    cacheClone.disabled = false;
                                    allOptions.push(cacheClone);
                                });
                                pointOptionCache.set(itemSelect, allOptions);
                            }

                            itemSelect.innerHTML = '';
                            const placeholder = allOptions[0] ? allOptions[0].cloneNode(true) : null;
                            if (placeholder) {
                                placeholder.selected = true;
                                itemSelect.appendChild(placeholder);
                            }

                            if (!requiresItem) {
                                itemSelect.disabled = true;
                                itemSelect.value = '';
                                return;
                            }

                            itemSelect.disabled = false;
                            let appendedCount = 0;
                            allOptions.slice(1).forEach((option) => {
                                const pointType = normalizePointType(option.dataset.pointType || '');
                                if (pointType !== selectedType) return;
                                if (!matchesDestinationOption(option)) return;
                                const clone = option.cloneNode(true);
                                clone.hidden = false;
                                clone.disabled = false;
                                if (clone.value === selectedValue) {
                                    clone.selected = true;
                                }
                                itemSelect.appendChild(clone);
                                appendedCount += 1;
                            });

                            // Safety fallback for Day End Point Airport:
                            // if destination filter yields zero matches, still show airport list.
                            const isEndPointSelect = itemSelect.classList.contains('day-end-point-item');
                            if (isEndPointSelect && selectedType === 'airport' && appendedCount === 0) {
                                allOptions.slice(1).forEach((option) => {
                                    const pointType = normalizePointType(option.dataset.pointType || '');
                                    if (pointType !== 'airport') return;
                                    const clone = option.cloneNode(true);
                                    clone.hidden = false;
                                    clone.disabled = false;
                                    if (clone.value === selectedValue) {
                                        clone.selected = true;
                                    }
                                    itemSelect.appendChild(clone);
                                });
                            });

                            if (selectedValue !== '' &&
                                !Array.from(itemSelect.options).some((option) => option.value === selectedValue)) {
                                itemSelect.value = '';
                            }
                        };

                        applyFilter(startType, startItem);
                        applyFilter(endType, endItem);

                        const startHotel = isHotelPointType(startType?.value || '');
                        if (startRoomWrap) startRoomWrap.classList.toggle('hidden', !startHotel);
                        if (startRoomSelect) {
                            const selectedHotelId = String(startItem?.value || '');
                            Array.from(startRoomSelect.options).forEach((option, idx) => {
                                if (idx === 0) {
                                    option.hidden = false;
                                    option.disabled = false;
                                    return;
                                }
                                const match = String(option.dataset.hotelId || '') ===
                                    selectedHotelId;
                                option.hidden = !match;
                                option.disabled = !match;
                            });
                            startRoomSelect.disabled = !startHotel;
                            if (!startHotel) {
                                startRoomSelect.value = '';
                            } else if (String(startRoomSelect.value || '') === '' || startRoomSelect
                                .selectedOptions?.[0]?.hidden) {
                                startRoomSelect.value = '';
                            }
                        }
                        if (startRoomInput) {
                            startRoomInput.disabled = !startHotel;
                            if (!startHotel) startRoomInput.value = '1';
                        }

                        const endHotel = isHotelPointType(endType?.value || '');
                        if (endRoomWrap) endRoomWrap.classList.toggle('hidden', !endHotel);
                        if (endRoomSelect) {
                            const selectedHotelId = String(endItem?.value || '');
                            Array.from(endRoomSelect.options).forEach((option, idx) => {
                                if (idx === 0) {
                                    option.hidden = false;
                                    option.disabled = false;
                                    return;
                                }
                                const match = String(option.dataset.hotelId || '') ===
                                    selectedHotelId;
                                option.hidden = !match;
                                option.disabled = !match;
                            });
                            endRoomSelect.disabled = !endHotel;
                            if (!endHotel) {
                                endRoomSelect.value = '';
                            } else if (String(endRoomSelect.value || '') === '' || endRoomSelect.selectedOptions
                                ?.[0]?.hidden) {
                                endRoomSelect.value = '';
                            }
                        }
                        if (endRoomInput) {
                            endRoomInput.disabled = !endHotel;
                            if (!endHotel) endRoomInput.value = '1';
                        }
                    });
                };
                const syncMainExperienceSelection = (section, changedRow = null) => {
                    if (!section) return;
                    const typeSelect = section.querySelector('.day-main-experience-type');
                    const itemSelect = section.querySelector('.day-main-experience-item');
                    if (!typeSelect || !itemSelect) return;
                    const rows = [...section.querySelectorAll('.schedule-row')];
                    if (changedRow && changedRow.querySelector('.item-main-experience')?.checked) {
                        rows.forEach((row) => {
                            if (row !== changedRow) {
                                const checkbox = row.querySelector('.item-main-experience');
                                if (checkbox) checkbox.checked = false;
                            }
                        });
                    }

                    let selectedMainRow = null;
                    rows.forEach((row) => {
                        const checkbox = row.querySelector('.item-main-experience');
                        const isEligible = selected(row);
                        if (checkbox && !isEligible) {
                            checkbox.checked = false;
                        }
                        let isChecked = checkbox?.checked === true && isEligible;
                        if (isChecked && selectedMainRow && checkbox) {
                            checkbox.checked = false;
                            isChecked = false;
                        }
                        row.classList.toggle('ring-2', isChecked);
                        row.classList.toggle('ring-amber-300', isChecked);
                        row.classList.toggle('border-amber-400', isChecked);
                        row.classList.toggle('bg-amber-50/40', isChecked);
                        row.classList.toggle('dark:border-amber-500/60', isChecked);
                        row.classList.toggle('dark:bg-amber-900/10', isChecked);
                        if (isChecked && !selectedMainRow) {
                            selectedMainRow = row;
                        }
                    });

                    if (!selectedMainRow) {
                        typeSelect.value = '';
                        itemSelect.value = '';
                        return;
                    }

                    const type = rowType(selectedMainRow);
                    const itemId = String(activeSelect(selectedMainRow)?.value || '');
                    if (itemId === '') {
                        const checkbox = selectedMainRow.querySelector('.item-main-experience');
                        if (checkbox) checkbox.checked = false;
                        typeSelect.value = '';
                        itemSelect.value = '';
                        selectedMainRow.classList.remove('ring-2', 'ring-amber-300', 'border-amber-400',
                            'bg-amber-50/40', 'dark:border-amber-500/60', 'dark:bg-amber-900/10');
                        return;
                    }

                    typeSelect.value = type;
                    itemSelect.value = itemId;
                };
                const buildHotelStaysPayload = (durationDays) => {
                    const perDay = [];
                    for (let day = 1; day <= durationDays; day++) {
                        const section = daySections.querySelector(`.day-section[data-day="${day}"]`);
                        const typeSelect = section?.querySelector('.day-end-point-type');
                        const itemSelect = section?.querySelector('.day-end-point-item');
                        const roomInput = section?.querySelector('.day-end-room-count');
                        const selectedType = normalizePointType(typeSelect?.value || '');
                        const hotelId = isHotelPointType(selectedType) ? parseInt(itemSelect?.value || '0', 10) : 0;
                        const roomCount = isHotelPointType(selectedType) ?
                            Math.max(1, parseInt(roomInput?.value || '1', 10)) :
                            0;
                        if (hotelId > 0) {
                            perDay.push({
                                day,
                                hotelId,
                                roomCount
                            });
                        }
                    }

                    const stays = [];
                    perDay.forEach((item) => {
                        const last = stays[stays.length - 1];
                        if (last && last.hotelId === item.hotelId && last.roomCount === item
                            .roomCount && (last.dayNumber + last.nightCount) === item.day) {
                            last.nightCount += 1;
                        } else {
                            stays.push({
                                hotelId: item.hotelId,
                                dayNumber: item.day,
                                nightCount: 1,
                                roomCount: item.roomCount,
                            });
                        }
                    });
                    return stays;
                };
                const syncHotelStaysHidden = () => {
                    if (!hotelStaysHidden) return;
                    const totalDays = Math.max(1, parseInt(durationInput.value || '1', 10));
                    const stays = buildHotelStaysPayload(totalDays);
                    let html = '';
                    stays.forEach((stay, index) => {
                        html += `
                        <input type="hidden" name="hotel_stays[${index}][hotel_id]" value="${stay.hotelId}" class="app-input">
                        <input type="hidden" name="hotel_stays[${index}][day_number]" value="${stay.dayNumber}" class="app-input">
                        <input type="hidden" name="hotel_stays[${index}][night_count]" value="${stay.nightCount}" class="app-input">
                        <input type="hidden" name="hotel_stays[${index}][room_count]" value="${stay.roomCount}" class="app-input">
                    `;
                    });
                    hotelStaysHidden.innerHTML = html;
                };
                const updateDayEndpointBadges = () => {
                    const sections = [...daySections.querySelectorAll('.day-section')].sort((a, b) => Number(a.dataset
                        .day) - Number(b.dataset.day));
                    let previousEndLabel = 'Not set';
                    sections.forEach((section) => {
                        const startLabel = getPointLabelFromTypeAndItem(
                            section.querySelector('.day-start-point-type'),
                            section.querySelector('.day-start-point-item'),
                            previousEndLabel,
                        );
                        const endLabel = getPointLabelFromTypeAndItem(
                            section.querySelector('.day-end-point-type'),
                            section.querySelector('.day-end-point-item'),
                            'Not set',
                        );
                        const startBadge = section.querySelector('.day-starts-at-label');
                        const endBadge = section.querySelector('.day-ends-at-label');
                        if (startBadge) startBadge.textContent = startLabel;
                        if (endBadge) endBadge.textContent = endLabel;
                        previousEndLabel = endLabel;
                    });
                };
                const recalcNoConnectorRebuild = async () => {
                    syncDayPointOptionRules();
                    syncPointItemVisibility();
                    await recalcAll();
                    reindex();
                    syncHotelStaysHidden();
                    updateDayEndpointBadges();
                    daySections.querySelectorAll('.day-section').forEach(updateDayPointTheme);
                    requestRenderItineraryMap();
                };
                const recalc = async () => {
                    daySections.querySelectorAll('.day-section').forEach(rebuildTravelConnectors);
                    await recalcNoConnectorRebuild();
                };
                const clearEndPointValidationState = () => {
                    daySections.querySelectorAll('.day-end-point-select').forEach((select) => {
                        select.classList.remove('border-rose-500', 'focus:border-rose-500',
                            'focus:ring-rose-500');
                    });
                };
                const initSortable = (sec) => {
                    const container = sec.querySelector('.day-items');
                    if (!container || container.dataset.sortableInit || typeof Sortable === 'undefined') return;
                    Sortable.create(container, {
                        group: {
                            name: 'itinerary-day-items',
                            pull: true,
                            put: true,
                        },
                        animation: 200,
                        forceFallback: true,
                        fallbackTolerance: 3,
                        draggable: '.schedule-row',
                        handle: '.drag-handle',
                        ghostClass: 'schedule-row-ghost',
                        chosenClass: 'schedule-row-chosen',
                        onEnd: () => recalc(),
                    });
                    container.dataset.sortableInit = '1';
                };
                const bindRow = (r) => {
                    updateScheduleRowTheme(r);
                    r.querySelector('.item-type')?.addEventListener('change', (e) => {
                        toggleType(r, e.target.value, true);
                        updateScheduleRowTheme(r);
                        recalc();
                    });
                    r.querySelector('.item-attraction')?.addEventListener('change', recalc);
                    r.querySelector('.item-activity')?.addEventListener('change', recalc);
                    r.querySelector('.item-fnb')?.addEventListener('change', recalc);
                    r.querySelector('.item-main-experience')?.addEventListener('change', () => {
                        const section = r.closest('.day-section');
                        syncMainExperienceSelection(section, r);
                        recalcNoConnectorRebuild();
                    });
                    r.querySelector('.remove-row')?.addEventListener('click', () => {
                        if (daySections.querySelectorAll('.schedule-row').length <= 1) return;
                        r.remove();
                        recalc();
                    });
                    toggleType(r, rowType(r), false);
                    updateScheduleRowTheme(r);
                };
                const cloneRow = (sec, type) => {
                    const src = sec.querySelector('.schedule-row');
                    if (!src) return;
                    const r = src.cloneNode(true);
                    r.querySelector('.item-attraction').value = '';
                    r.querySelector('.item-activity').value = '';
                    r.querySelector('.item-fnb').value = '';
                    r.querySelector('.item-pax').value = '1';
                    r.querySelector('.item-start').value = '';
                    r.querySelector('.item-end').value = '';
                    r.querySelector('.item-travel').value = '';
                    r.querySelector('.item-order').value = '';
                    const mainCheckbox = r.querySelector('.item-main-experience');
                    if (mainCheckbox) mainCheckbox.checked = false;
                    const seq = r.querySelector('.item-seq-badge');
                    if (seq) seq.textContent = '-';
                    sec.querySelector('.day-items').appendChild(r);
                    bindRow(r);
                    toggleType(r, type, false);
                    recalc();
                };
                const syncDurationNights = () => {
                    if (!durationNightsInput) return;
                    const days = Math.max(1, parseInt(durationInput.value || '1', 10));
                    const nights = Math.max(0, parseInt(durationNightsInput.value || '0', 10));
                    if (nights > days) {
                        durationNightsInput.value = String(days);
                    }
                };
                daySections.querySelectorAll('.day-section').forEach((sec) => {
                    sec.querySelectorAll('.schedule-row').forEach(bindRow);
                    sec.querySelector('.add-attraction')?.addEventListener('click', () => cloneRow(sec,
                        'attraction'));
                    sec.querySelector('.add-activity')?.addEventListener('click', () => cloneRow(sec, 'activity'));
                    sec.querySelector('.add-fnb')?.addEventListener('click', () => cloneRow(sec, 'fnb'));
                    sec.querySelector('.day-start-point-type')?.addEventListener('change', () => {
                        updateDayPointTheme(sec);
                        syncPointItemVisibility();
                        recalcNoConnectorRebuild();
                    });
                    sec.querySelector('.day-start-point-item')?.addEventListener('change',
                    recalcNoConnectorRebuild);
                    sec.querySelector('.day-start-point-item')?.addEventListener('change', syncPointItemVisibility);
                    sec.querySelector('.day-start-room-select')?.addEventListener('change',
                        recalcNoConnectorRebuild);
                    sec.querySelector('.day-start-room-count')?.addEventListener('input', recalcNoConnectorRebuild);
                    sec.querySelector('.day-end-point-type')?.addEventListener('change', () => {
                        updateDayPointTheme(sec);
                        syncPointItemVisibility();
                        recalcNoConnectorRebuild();
                    });
                    sec.querySelector('.day-end-point-select')?.addEventListener('change', (event) => {
                        event.target.classList.remove('border-rose-500', 'focus:border-rose-500',
                            'focus:ring-rose-500');
                        recalcNoConnectorRebuild();
                    });
                    sec.querySelector('.day-end-point-item')?.addEventListener('change', syncPointItemVisibility);
                    sec.querySelector('.day-end-room-select')?.addEventListener('change', recalcNoConnectorRebuild);
                    sec.querySelector('.day-end-room-count')?.addEventListener('input', recalcNoConnectorRebuild);
                    sec.querySelector('.day-start-travel')?.addEventListener('input', recalcNoConnectorRebuild);
                    sec.querySelector('.day-start-time')?.addEventListener('change', recalc);
                    sec.querySelector('.day-transport-unit')?.addEventListener('change', recalcNoConnectorRebuild);
                    initSortable(sec);
                    updateDayPointTheme(sec);
                });
                durationInput.addEventListener('change', () => {
                    let d = Math.max(1, parseInt(durationInput.value || '1', 10));
                    durationInput.value = String(d);
                    syncDurationNights();
                    let secs = [...daySections.querySelectorAll('.day-section')];
                    for (let i = 1; i <= d; i++) {
                        if (!daySections.querySelector(`.day-section[data-day="${i}"]`) && secs.length) {
                            const c = secs[0].cloneNode(true);
                            c.dataset.day = String(i);
                            const cloneDayTitle = c.querySelector('.day-title-label');
                            if (cloneDayTitle) cloneDayTitle.textContent = `Day ${i}`;
                            const cloneDayStartTime = c.querySelector('.day-start-time');
                            if (cloneDayStartTime) {
                                cloneDayStartTime.value = '';
                                cloneDayStartTime.name = `day_start_times[${i}]`;
                            }
                            const cloneDayEndTime = c.querySelector('.day-end-time');
                            if (cloneDayEndTime) cloneDayEndTime.value = '';
                            const startsAtLabel = c.querySelector('.day-starts-at-label');
                            if (startsAtLabel) startsAtLabel.textContent = 'Not set';
                            const endsAtLabel = c.querySelector('.day-ends-at-label');
                            if (endsAtLabel) endsAtLabel.textContent = 'Not set';
                            const dayStartPointType = c.querySelector('.day-start-point-type');
                            if (dayStartPointType) {
                                dayStartPointType.name = `daily_start_point_types[${i}]`;
                                dayStartPointType.value = i === 1 ? 'airport' : 'previous_day_end';
                            }
                            const dayStartPointItem = c.querySelector('.day-start-point-item');
                            if (dayStartPointItem) {
                                dayStartPointItem.name = `daily_start_point_items[${i}]`;
                                dayStartPointItem.value = '';
                            }
                            const dayStartRoomSelect = c.querySelector('.day-start-room-select');
                            if (dayStartRoomSelect) {
                                dayStartRoomSelect.name = `daily_start_point_room_ids[${i}]`;
                                dayStartRoomSelect.value = '';
                                dayStartRoomSelect.disabled = true;
                            }
                            const dayStartRoomInput = c.querySelector('.day-start-room-count');
                            const dayStartRoomWrap = c.querySelector('.day-start-room-wrap');
                            if (dayStartRoomInput) {
                                dayStartRoomInput.name = `daily_start_point_room_counts[${i}]`;
                                dayStartRoomInput.value = '1';
                                dayStartRoomInput.disabled = true;
                            }
                            dayStartRoomWrap?.classList.add('hidden');
                            const dayEndPointType = c.querySelector('.day-end-point-type');
                            if (dayEndPointType) {
                                dayEndPointType.name = `daily_end_point_types[${i}]`;
                                dayEndPointType.value = 'hotel';
                            }
                            const dayEndPointSelect = c.querySelector('.day-end-point-item');
                            if (dayEndPointSelect) {
                                dayEndPointSelect.name = `daily_end_point_items[${i}]`;
                                dayEndPointSelect.value = '';
                            }
                            const dayEndRoomSelect = c.querySelector('.day-end-room-select');
                            if (dayEndRoomSelect) {
                                dayEndRoomSelect.name = `daily_end_point_room_ids[${i}]`;
                                dayEndRoomSelect.value = '';
                                dayEndRoomSelect.disabled = false;
                            }
                            const dayEndRoomInput = c.querySelector('.day-end-room-count');
                            const dayEndRoomWrap = c.querySelector('.day-end-room-wrap');
                            if (dayEndRoomInput) {
                                dayEndRoomInput.name = `daily_end_point_room_counts[${i}]`;
                                dayEndRoomInput.value = '1';
                                dayEndRoomInput.disabled = false;
                            }
                            dayEndRoomWrap?.classList.remove('hidden');
                            const dayStartTravelInput = c.querySelector('.day-start-travel');
                            if (dayStartTravelInput) {
                                dayStartTravelInput.name = `day_start_travel_minutes[${i}]`;
                                dayStartTravelInput.value = '';
                            }
                            const dayIncludeInput = c.querySelector('.day-include');
                            if (dayIncludeInput) {
                                dayIncludeInput.name = `day_includes[${i}]`;
                                dayIncludeInput.value = '';
                            }
                            const dayExcludeInput = c.querySelector('.day-exclude');
                            if (dayExcludeInput) {
                                dayExcludeInput.name = `day_excludes[${i}]`;
                                dayExcludeInput.value = '';
                            }
                            const dayMainExperienceType = c.querySelector('.day-main-experience-type');
                            if (dayMainExperienceType) {
                                dayMainExperienceType.name = `daily_main_experience_types[${i}]`;
                                dayMainExperienceType.value = '';
                            }
                            const dayMainExperienceItem = c.querySelector('.day-main-experience-item');
                            if (dayMainExperienceItem) {
                                dayMainExperienceItem.name = `daily_main_experience_items[${i}]`;
                                dayMainExperienceItem.value = '';
                            }
                            const dayTransportUnit = c.querySelector('.day-transport-unit');
                            const dayTransportDay = c.querySelector('.day-transport-day');
                            if (dayTransportUnit) {
                                dayTransportUnit.name = `daily_transport_units[${i}][transport_unit_id]`;
                                dayTransportUnit.value = '';
                            }
                            if (dayTransportDay) {
                                dayTransportDay.name = `daily_transport_units[${i}][day_number]`;
                                dayTransportDay.value = String(i);
                            }
                            c.querySelectorAll('.travel-connector').forEach((el) => el.remove());
                            const rows = [...c.querySelectorAll('.schedule-row')];
                            rows.slice(1).forEach((r) => r.remove());
                            const r = c.querySelector('.schedule-row');
                            if (r) {
                                r.dataset.itemType = 'attraction';
                                r.querySelector('.item-type').value = 'attraction';
                                r.querySelector('.item-attraction').value = '';
                                r.querySelector('.item-activity').value = '';
                                r.querySelector('.item-fnb').value = '';
                                r.querySelector('.item-pax').value = '1';
                                r.querySelector('.item-start').value = '';
                                r.querySelector('.item-end').value = '';
                                r.querySelector('.item-travel').value = '';
                                r.querySelector('.item-day').value = String(i);
                                r.querySelector('.item-order').value = '';
                                const mainCheckbox = r.querySelector('.item-main-experience');
                                if (mainCheckbox) mainCheckbox.checked = false;
                                const seq = r.querySelector('.item-seq-badge');
                                if (seq) seq.textContent = '-';
                            }
                            const dayItems = c.querySelector('.day-items');
                            if (dayItems) delete dayItems.dataset.sortableInit;
                            daySections.appendChild(c);
                            c.querySelectorAll('.schedule-row').forEach(bindRow);
                            c.querySelector('.add-attraction')?.addEventListener('click', () => cloneRow(c,
                                'attraction'));
                            c.querySelector('.add-activity')?.addEventListener('click', () => cloneRow(c,
                                'activity'));
                            c.querySelector('.add-fnb')?.addEventListener('click', () => cloneRow(c, 'fnb'));
                            c.querySelector('.day-start-point-type')?.addEventListener('change', () => {
                                syncPointItemVisibility();
                                recalcNoConnectorRebuild();
                            });
                            c.querySelector('.day-start-point-item')?.addEventListener('change',
                                recalcNoConnectorRebuild);
                            c.querySelector('.day-start-point-item')?.addEventListener('change',
                                syncPointItemVisibility);
                            c.querySelector('.day-start-room-select')?.addEventListener('change',
                                recalcNoConnectorRebuild);
                            c.querySelector('.day-start-room-count')?.addEventListener('input',
                                recalcNoConnectorRebuild);
                            c.querySelector('.day-end-point-type')?.addEventListener('change', () => {
                                syncPointItemVisibility();
                                recalcNoConnectorRebuild();
                            });
                            c.querySelector('.day-end-point-select')?.addEventListener('change', (event) => {
                                event.target.classList.remove('border-rose-500', 'focus:border-rose-500',
                                    'focus:ring-rose-500');
                                recalcNoConnectorRebuild();
                            });
                            c.querySelector('.day-end-point-item')?.addEventListener('change',
                                syncPointItemVisibility);
                            c.querySelector('.day-end-room-select')?.addEventListener('change',
                                recalcNoConnectorRebuild);
                            c.querySelector('.day-end-room-count')?.addEventListener('input',
                                recalcNoConnectorRebuild);
                            c.querySelector('.day-start-travel')?.addEventListener('input',
                                recalcNoConnectorRebuild);
                            c.querySelector('.day-start-time')?.addEventListener('change', recalc);
                            c.querySelector('.day-transport-unit')?.addEventListener('change',
                                recalcNoConnectorRebuild);
                            initSortable(c);
                        }
                    } [...daySections.querySelectorAll('.day-section')].forEach((s) => {
                        if (Number(s.dataset.day) > d) s.remove();
                    });
                    recalc();
                });
                durationNightsInput?.addEventListener('change', syncDurationNights);
                form?.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    await recalcAll();
                    reindex();
                    syncHotelStaysHidden();
                    const hasA = [...daySections.querySelectorAll('.schedule-row')].some((r) => rowType(r) ===
                        'attraction' && selected(r));
                    if (!hasA) {
                        alert('Minimal 1 attraction wajib diisi.');
                        return;
                    }
                    clearEndPointValidationState();
                    const invalidEndPointDays = [];
                    [...daySections.querySelectorAll('.day-section')]
                    .sort((a, b) => Number(a.dataset.day) - Number(b.dataset.day))
                        .forEach((section) => {
                            const day = Number(section.dataset.day || '1');
                            const endPointSelect = section.querySelector('.day-end-point-select');
                            const isEmpty = !endPointSelect || String(endPointSelect.value || '').trim() ===
                                '';
                            if (isEmpty) {
                                invalidEndPointDays.push(day);
                                endPointSelect?.classList.add('border-rose-500', 'focus:border-rose-500',
                                    'focus:ring-rose-500');
                            }
                        });
                    if (invalidEndPointDays.length > 0) {
                        const firstInvalidDay = invalidEndPointDays[0];
                        daySections.querySelector(`.day-section[data-day="${firstInvalidDay}"]`)
                            ?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                            });
                        alert(`End Point wajib diisi untuk Day: ${invalidEndPointDays.join(', ')}.`);
                        return;
                    }
                    form.submit();
                });
                recalc();
            })
            ();
        </script>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            (function() {
                const destinationInput = document.getElementById('itinerary-destination');
                const destinationDropdown = document.getElementById('itinerary-destination-dropdown');
                if (!destinationInput || !destinationDropdown) return;

                const normalize = (value) => String(value || '').toLowerCase().trim();
                const endpoint = destinationInput.dataset.endpoint || '';
                const suggestionLimit = 12;
                let fetchToken = 0;
                let activeIndex = -1;

                const debounce = (fn, wait = 250) => {
                    let timer = null;
                    return (...args) => {
                        if (timer) clearTimeout(timer);
                        timer = setTimeout(() => fn(...args), wait);
                    };
                };

                const hideDropdown = () => {
                    destinationDropdown.classList.add('hidden');
                    destinationDropdown.innerHTML = '';
                    activeIndex = -1;
                };

                const setActiveItem = (idx) => {
                    const options = destinationDropdown.querySelectorAll('[data-destination-value]');
                    options.forEach((node, nodeIndex) => {
                        const active = nodeIndex === idx;
                        node.classList.toggle('bg-indigo-50', active);
                        node.classList.toggle('dark:bg-indigo-900/30', active);
                    });
                    activeIndex = idx;
                };

                const selectSuggestion = (value) => {
                    destinationInput.value = value;
                    hideDropdown();
                    applyDestinationFilter();
                };

                const renderSuggestions = (items) => {
                    if (!items.length) {
                        hideDropdown();
                        return;
                    }
                    let html = '';
                    items.forEach((item, idx) => {
                        const safeValue = String(item).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        html +=
                            `<button type="button" data-index="${idx}" data-destination-value="${safeValue}"  class="block w-full rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 dark:text-gray-100 dark:hover:bg-indigo-900/30">${safeValue}</button>`;
                    });
                    destinationDropdown.innerHTML = html;
                    destinationDropdown.classList.remove('hidden');
                    setActiveItem(-1);
                };

                const fetchSuggestions = async (keyword) => {
                    if (!endpoint) return;
                    const token = ++fetchToken;
                    const params = new URLSearchParams({
                        q: keyword,
                        limit: String(suggestionLimit),
                    });
                    try {
                        const response = await fetch(`${endpoint}?${params.toString()}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });
                        if (!response.ok) {
                            hideDropdown();
                            return;
                        }
                        const payload = await response.json();
                        if (token !== fetchToken) return;
                        const items = Array.isArray(payload?.data) ? payload.data : [];
                        renderSuggestions(items);
                    } catch (_) {
                        hideDropdown();
                    }
                };

                const matchesDestination = (option, keyword) => {
                    if (!keyword) return true;
                    const city = normalize(option.dataset.city);
                    const province = normalize(option.dataset.province);
                    const location = normalize(option.dataset.location);
                    const destination = normalize(option.dataset.destination);
                    return city.includes(keyword) ||
                        province.includes(keyword) ||
                        location.includes(keyword) ||
                        destination.includes(keyword);
                };

                const applyFilterToSelect = (select) => {
                    if (!select) return;
                    const keyword = normalize(destinationInput.value);
                    const selectedValue = select.value;
                    Array.from(select.options).forEach((option, idx) => {
                        if (idx === 0) {
                            option.hidden = false;
                            return;
                        }
                        const selected = option.value === selectedValue;
                        option.hidden = !matchesDestination(option, keyword) && !selected;
                    });
                };

                const applyDestinationFilter = () => {
                    document.querySelectorAll(
                            '.item-attraction, .item-activity, .item-fnb, .day-start-point-item, .day-end-point-item, .day-transport-unit'
                            )
                        .forEach(applyFilterToSelect);
                };

                const fetchSuggestionsDebounced = debounce((keyword) => {
                    fetchSuggestions(keyword);
                }, 300);

                destinationInput.addEventListener('input', () => {
                    applyDestinationFilter();
                    fetchSuggestionsDebounced(destinationInput.value.trim());
                });
                destinationInput.addEventListener('change', applyDestinationFilter);
                document.addEventListener('change', (event) => {
                    if (event.target.matches('.day-start-point-type, .day-end-point-type')) {
                        applyDestinationFilter();
                    }
                });
                destinationInput.addEventListener('focus', () => {
                    fetchSuggestions(destinationInput.value.trim());
                });
                destinationInput.addEventListener('keydown', (event) => {
                    const options = destinationDropdown.querySelectorAll('[data-destination-value]');
                    if (!options.length) return;

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        const next = activeIndex < options.length - 1 ? activeIndex + 1 : 0;
                        setActiveItem(next);
                        return;
                    }
                    if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        const next = activeIndex > 0 ? activeIndex - 1 : options.length - 1;
                        setActiveItem(next);
                        return;
                    }
                    if (event.key === 'Enter') {
                        if (activeIndex >= 0 && options[activeIndex]) {
                            event.preventDefault();
                            selectSuggestion(options[activeIndex].dataset.destinationValue || '');
                        } else {
                            hideDropdown();
                        }
                        return;
                    }
                    if (event.key === 'Escape') {
                        hideDropdown();
                    }
                });

                destinationDropdown.addEventListener('click', (event) => {
                    const target = event.target.closest('[data-destination-value]');
                    if (!target) return;
                    selectSuggestion(target.dataset.destinationValue || '');
                });

                document.addEventListener('click', (event) => {
                    if (event.target === destinationInput) return;
                    if (destinationDropdown.contains(event.target)) return;
                    hideDropdown();
                });

                const observer = new MutationObserver(() => {
                    applyDestinationFilter();
                });
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });

                applyDestinationFilter();
            })
            ();
        </script>
    @endpush
@endonce
