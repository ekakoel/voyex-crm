@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $itinerary = $itinerary ?? null;
    $inquiries = $inquiries ?? collect();
    $airports = $airports ?? collect();
    $hotels = $hotels ?? collect();
    $transportUnits = $transportUnits ?? collect();
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
        $rawAttractions = isset($itinerary)
            ? $itinerary->touristAttractions
                ->map(
                    fn($a) => [
                        'tourist_attraction_id' => $a->id,
                        'day_number' => $a->pivot->day_number ?? 1,
                        'start_time' => $a->pivot->start_time ? substr((string) $a->pivot->start_time, 0, 5) : '',
                        'end_time' => $a->pivot->end_time ? substr((string) $a->pivot->end_time, 0, 5) : '',
                        'travel_minutes_to_next' => $a->pivot->travel_minutes_to_next ?? null,
                        'visit_order' => $a->pivot->visit_order ?? null,
                    ],
                )
                ->values()
                ->toArray()
            : [];
    }

    $rawActivities = old('itinerary_activity_items');
    if (!is_array($rawActivities)) {
        $rawActivities = isset($itinerary)
            ? $itinerary->itineraryActivities
                ->map(
                    fn($a) => [
                        'activity_id' => $a->activity_id,
                        'pax' => $a->pax ?? 1,
                        'day_number' => $a->day_number ?? 1,
                        'start_time' => $a->start_time ? substr((string) $a->start_time, 0, 5) : '',
                        'end_time' => $a->end_time ? substr((string) $a->end_time, 0, 5) : '',
                        'travel_minutes_to_next' => $a->travel_minutes_to_next ?? null,
                        'visit_order' => $a->visit_order ?? null,
                    ],
                )
                ->values()
                ->toArray()
            : [];
    }
    $rawFoodBeverages = old('itinerary_food_beverage_items');
    if (!is_array($rawFoodBeverages)) {
        $rawFoodBeverages = isset($itinerary)
            ? $itinerary->itineraryFoodBeverages
                ->map(
                    fn($f) => [
                        'food_beverage_id' => $f->food_beverage_id,
                        'pax' => $f->pax ?? 1,
                        'day_number' => $f->day_number ?? 1,
                        'start_time' => $f->start_time ? substr((string) $f->start_time, 0, 5) : '',
                        'end_time' => $f->end_time ? substr((string) $f->end_time, 0, 5) : '',
                        'travel_minutes_to_next' => $f->travel_minutes_to_next ?? null,
                        'visit_order' => $f->visit_order ?? null,
                    ],
                )
                ->values()
                ->toArray()
            : [];
    }

    $durationDays = max(1, (int) old('duration_days', $itinerary->duration_days ?? 1));
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

    $inquiryPreviewMap = $inquiries->mapWithKeys(function ($inquiry) {
        $latestFollowUp = $inquiry->followUps->first();
        return [
            (string) $inquiry->id => [
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
            ],
        ];
    });
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
                                 class="add-attraction rounded-lg border border-indigo-300 px-3 py-1 text-xs font-medium text-indigo-700">Add
                                Attraction</button>
                            <button type="button"
                                 class="add-activity rounded-lg border border-emerald-300 px-3 py-1 text-xs font-medium text-emerald-700">Add
                                Activity</button>
                            <button type="button"
                                 class="add-fnb rounded-lg border border-amber-300 px-3 py-1 text-xs font-medium text-amber-700">Add
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
                                    <option value="{{ $unit->id }}" data-city="{{ $unit->transport->city ?? '' }}"
                                        data-province="{{ $unit->transport->province ?? '' }}"
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
                                        @foreach ($hotels as $hotel)
                                            <option value="{{ $hotel->id }}" data-point-type="hotel"
                                                data-location="{{ $hotel->address ?? '' }}"
                                                data-city="{{ $hotel->city ?? '' }}"
                                                data-province="{{ $hotel->province ?? '' }}"
                                                data-latitude="{{ $hotel->latitude ?? '' }}"
                                                data-longitude="{{ $hotel->longitude ?? '' }}"
                                                @selected($startType === 'hotel' && $startItem === (string) $hotel->id)>
                                                {{ $hotel->name }}{{ !empty($hotel->city) ? ' (' . $hotel->city . ')' : '' }}
                                            </option>
                                        @endforeach
                                        @foreach ($airports ?? collect() as $airport)
                                            <option value="{{ $airport->id }}" data-point-type="airport"
                                                data-location="{{ $airport->location ?? '' }}"
                                                data-city="{{ $airport->city ?? '' }}"
                                                data-province="{{ $airport->province ?? '' }}"
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
                                        @foreach ($touristAttractions as $a)
                                            <option value="{{ $a->id }}"
                                                data-duration="{{ $a->ideal_visit_minutes ?? 120 }}"
                                                data-city="{{ $a->city ?? '' }}"
                                                data-province="{{ $a->province ?? '' }}"
                                                data-latitude="{{ $a->latitude }}"
                                                data-longitude="{{ $a->longitude }}" @selected((string) ($r['tourist_attraction_id'] ?? '') === (string) $a->id)>
                                                {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <select
                                            class="item-activity {{ $r['item_type'] !== 'activity' ? 'hidden' : '' }} dark:border-gray-600 app-input">
                                            <option value="">Select activity</option>
                                            @foreach ($activities ?? collect() as $a)
                                                <option value="{{ $a->id }}"
                                                    data-duration="{{ $a->duration_minutes ?? 60 }}"
                                                    data-city="{{ $a->vendor->city ?? '' }}"
                                                    data-province="{{ $a->vendor->province ?? '' }}"
                                                    data-latitude="{{ $a->vendor->latitude ?? '' }}"
                                                    data-longitude="{{ $a->vendor->longitude ?? '' }}"
                                                    @selected((string) ($r['activity_id'] ?? '') === (string) $a->id)>{{ $a->name }}</option>
                                            @endforeach
                                        </select>
                                        <select
                                            class="item-fnb {{ $r['item_type'] !== 'fnb' ? 'hidden' : '' }} dark:border-gray-600 app-input">
                                            <option value="">Select F&B</option>
                                            @foreach ($foodBeverages ?? collect() as $f)
                                                <option value="{{ $f->id }}"
                                                    data-duration="{{ $f->duration_minutes ?? 60 }}"
                                                    data-city="{{ $f->vendor->city ?? '' }}"
                                                    data-province="{{ $f->vendor->province ?? '' }}"
                                                    data-latitude="{{ $f->vendor->latitude ?? '' }}"
                                                    data-longitude="{{ $f->vendor->longitude ?? '' }}"
                                                    @selected((string) ($r['food_beverage_id'] ?? '') === (string) $f->id)>{{ $f->name }}</option>
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
                                        @foreach ($touristAttractions as $a)
                                            <option value="{{ $a->id }}"
                                                data-duration="{{ $a->ideal_visit_minutes ?? 120 }}"
                                                data-city="{{ $a->city ?? '' }}"
                                                data-province="{{ $a->province ?? '' }}"
                                                data-latitude="{{ $a->latitude }}"
                                                data-longitude="{{ $a->longitude }}">{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex flex-col gap-2 sm:flex-row"><select
                                            class="item-activity hidden dark:border-gray-600 app-input">
                                            <option value="">Select activity</option>
                                            @foreach ($activities ?? collect() as $a)
                                                <option value="{{ $a->id }}"
                                                    data-duration="{{ $a->duration_minutes ?? 60 }}"
                                                    data-city="{{ $a->vendor->city ?? '' }}"
                                                    data-province="{{ $a->vendor->province ?? '' }}"
                                                    data-latitude="{{ $a->vendor->latitude ?? '' }}"
                                                    data-longitude="{{ $a->vendor->longitude ?? '' }}">
                                                    {{ $a->name }}</option>
                                            @endforeach
                                        </select><select
                                            class="item-fnb hidden dark:border-gray-600 app-input">
                                            <option value="">Select F&B</option>
                                            @foreach ($foodBeverages ?? collect() as $f)
                                                <option value="{{ $f->id }}"
                                                    data-duration="{{ $f->duration_minutes ?? 60 }}"
                                                    data-city="{{ $f->vendor->city ?? '' }}"
                                                    data-province="{{ $f->vendor->province ?? '' }}"
                                                    data-latitude="{{ $f->vendor->latitude ?? '' }}"
                                                    data-longitude="{{ $f->vendor->longitude ?? '' }}">
                                                    {{ $f->name }}</option>
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
                                        @foreach ($hotels as $hotel)
                                            <option value="{{ $hotel->id }}" data-point-type="hotel"
                                                data-location="{{ $hotel->address ?? '' }}"
                                                data-city="{{ $hotel->city ?? '' }}"
                                                data-province="{{ $hotel->province ?? '' }}"
                                                data-latitude="{{ $hotel->latitude ?? '' }}"
                                                data-longitude="{{ $hotel->longitude ?? '' }}"
                                                @selected($endType === 'hotel' && $endItem === (string) $hotel->id)>
                                                {{ $hotel->name }}{{ !empty($hotel->city) ? ' (' . $hotel->city . ')' : '' }}
                                            </option>
                                        @endforeach
                                        @foreach ($airports ?? collect() as $airport)
                                            <option value="{{ $airport->id }}" data-point-type="airport"
                                                data-location="{{ $airport->location ?? '' }}"
                                                data-city="{{ $airport->city ?? '' }}"
                                                data-province="{{ $airport->province ?? '' }}"
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
            .itinerary-marker-badge {
                position: relative;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 34px;
                height: 34px;
                border-radius: 9999px;
                color: #fff;
                border: 2px solid rgba(255, 255, 255, 0.96);
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.22);
                font-size: 14px;
                line-height: 1;
            }
            .itinerary-marker-badge.attraction {
                background: #0ea5e9;
            }
            .itinerary-marker-badge.activity {
                background: #10b981;
            }
            .itinerary-marker-badge.fnb {
                background: #f59e0b;
            }
            .itinerary-marker-badge.airport {
                background: #6366f1;
            }
            .itinerary-marker-badge.hotel {
                background: #2563eb;
            }
            .itinerary-marker-badge.is-highlighted {
                transform: scale(1.12);
                box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.95), 0 16px 30px rgba(15, 23, 42, 0.3);
                z-index: 2;
            }
            .itinerary-marker-number {
                position: absolute;
                right: -4px;
                top: -5px;
                min-width: 16px;
                height: 16px;
                padding: 0 4px;
                border-radius: 9999px;
                background: #0f172a;
                color: #fff;
                border: 1px solid rgba(255, 255, 255, 0.92);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 9px;
                font-weight: 700;
                line-height: 1;
            }
            .travel-time-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 22px;
                padding: 0 8px;
                border-radius: 9999px;
                background: rgba(15, 23, 42, 0.88);
                color: #fff;
                font-size: 10px;
                font-weight: 700;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.2);
                white-space: nowrap;
            }
            .leaflet-routing-container {
                display: none !important;
            }
        </style>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (() => {
                const inquiryPreviewMap = @json($inquiryPreviewMap);
                const inquirySelect = document.getElementById('inquiry-select');
                const detailEmpty = document.getElementById('inquiry-detail-empty');
                const detailContent = document.getElementById('inquiry-detail-content');
                const detailField = (id) => document.getElementById(id);
                const setDetail = () => {
                    if (!inquirySelect || !detailEmpty || !detailContent) return;
                    const key = String(inquirySelect.value || '');
                    const detail = inquiryPreviewMap[key] || null;
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
                const routeDebugSummary = document.getElementById('itinerary-route-debug-summary');
                const routeDebugDetails = document.getElementById('itinerary-route-debug-details');
                const form = daySections?.closest('form');
                if (!daySections || !durationInput || !mapEl || typeof L === 'undefined') return;
                const map = L.map(mapEl).setView([-6.2, 106.816666], 5);
                const baseTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                const markers = L.layerGroup().addTo(map);
                const routeLayers = [];
                const clearDynamicMapLayers = () => {
                    markers.clearLayers();
                    markerLookup.clear();
                    map.eachLayer((layer) => {
                        if (layer === baseTileLayer || layer === markers) return;
                        map.removeLayer(layer);
                    });
                    const panes = typeof map.getPanes === 'function' ? map.getPanes() : null;
                    [
                        panes?.markerPane,
                        panes?.shadowPane,
                        panes?.overlayPane,
                        panes?.popupPane,
                        panes?.tooltipPane,
                    ].forEach((pane) => {
                        if (!pane) return;
                        while (pane.firstChild) {
                            pane.removeChild(pane.firstChild);
                        }
                    });
                    routeLayers.length = 0;
                };
                const drawRouteLine = (coordinates, color = '#ef4444') => {
                    const line = L.polyline(coordinates, {
                        color,
                        weight: 7,
                        opacity: 1,
                        lineCap: 'round',
                        lineJoin: 'round',
                    }).addTo(map);
                    routeLayers.push(line);
                    if (typeof line.bringToFront === 'function') {
                        line.bringToFront();
                    }
                    return line;
                };
                const setRouteDebug = (summaryLines = [], detailLines = []) => {
                    if (routeDebugSummary) {
                        routeDebugSummary.innerHTML = summaryLines.length ? summaryLines.join('<br>') : 'No route debug data.';
                    }
                    if (routeDebugDetails) {
                        routeDebugDetails.textContent = detailLines.join('\n');
                    }
                };
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
                    const byType = Object.fromEntries(candidates.map((candidate) => [candidate.type, candidate.select]));
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
                const markerTypeClass = (type) => {
                    if (type === 'activity') return 'activity';
                    if (type === 'fnb') return 'fnb';
                    if (type === 'airport') return 'airport';
                    if (type === 'hotel') return 'hotel';
                    return 'attraction';
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
                const markerTypeIcon = (type) => {
                    if (type === 'activity') return 'fa-solid fa-person-hiking';
                    if (type === 'fnb') return 'fa-solid fa-utensils';
                    if (type === 'airport') return 'fa-solid fa-plane';
                    if (type === 'hotel') return 'fa-solid fa-bed';
                    return 'fa-solid fa-location-dot';
                };
                const createBadgeIcon = (order, type, highlighted = false) => L.divIcon({
                    className: '',
                    html: `<div class="itinerary-marker-badge ${markerTypeClass(type)} ${highlighted ? 'is-highlighted' : ''}"><i class="${markerTypeIcon(type)}"></i><span class="itinerary-marker-number">${order}</span></div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                const travelBadgeIcon = (minutes) => L.divIcon({
                    className: 'travel-time-label',
                    html: `<div class="travel-time-badge">${minutes} m</div>`,
                    iconSize: [0, 0],
                    iconAnchor: [0, 0]
                });
                const parseItemOptionPoint = (option, typeOverride = null) => {
                    if (!option) return null;
                    const lat = parseFloat(option.dataset.latitude || '');
                    const lng = parseFloat(option.dataset.longitude || '');
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
                    return {
                        lat,
                        lng,
                        name: option.textContent.trim(),
                        type: normalizePointType(typeOverride || option.dataset.pointType || '', 'hotel'),
                    };
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
                    return city.includes(keyword) || province.includes(keyword) || location.includes(keyword);
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
                                allOptions = Array.from(itemSelect.options).map((opt) => opt.cloneNode(true));
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
                            allOptions.slice(1).forEach((option) => {
                                const pointType = normalizePointType(option.dataset.pointType || '');
                                if (pointType !== selectedType) return;
                                if (selectedType !== 'airport' && !matchesDestinationOption(option)) return;
                                const clone = option.cloneNode(true);
                                if (clone.value === selectedValue) {
                                    clone.selected = true;
                                }
                                itemSelect.appendChild(clone);
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
                const getDayStartPoint = async (day, previousEndPoint = null) => {
                    const section = daySections.querySelector(`.day-section[data-day="${day}"]`);
                    if (!section) return null;
                    const typeSelect = section.querySelector('.day-start-point-type');
                    const itemSelect = section.querySelector('.day-start-point-item');
                    const selectedType = normalizePointType(typeSelect?.value || '');
                    if (selectedType === 'previous_day_end') {
                        return previousEndPoint;
                    }
                    if (isHotelPointType(selectedType) || selectedType === 'airport') {
                        return parseItemOptionPoint(itemSelect?.selectedOptions?.[0] || null, selectedType);
                    }
                    return previousEndPoint;
                };
                const getDayEndPoint = async (day) => {
                    const section = daySections.querySelector(`.day-section[data-day="${day}"]`);
                    if (!section) return null;
                    const typeSelect = section.querySelector('.day-end-point-type');
                    const itemSelect = section.querySelector('.day-end-point-item');
                    const selectedType = normalizePointType(typeSelect?.value || '');
                    if (isHotelPointType(selectedType) || selectedType === 'airport') {
                        return parseItemOptionPoint(itemSelect?.selectedOptions?.[0] || null, selectedType);
                    }
                    return null;
                };
                const buildDayAnchors = (durationDays) => {
                    const startByDay = {};
                    const endByDay = {};
                    return {
                        startByDay,
                        endByDay
                    };
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
                    hotelStaysHidden.innerHTML = stays.map((stay, index) => `
                        <input type="hidden" name="hotel_stays[${index}][hotel_id]" value="${stay.hotelId}" class="app-input">
                        <input type="hidden" name="hotel_stays[${index}][day_number]" value="${stay.dayNumber}" class="app-input">
                        <input type="hidden" name="hotel_stays[${index}][night_count]" value="${stay.nightCount}" class="app-input">
                        <input type="hidden" name="hotel_stays[${index}][room_count]" value="${stay.roomCount}" class="app-input">
                    `).join('');
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
                const routeColors = ['#2563eb', '#16a34a', '#ea580c', '#db2777', '#7c3aed', '#0891b2'];
                const markerLookup = new Map();
                let highlightedMarker = null;
                let highlightedMarkerOrder = null;
                let highlightedMarkerType = null;
                let renderMapRunId = 0;
                
                const renderMap = async () => {
                    const currentRenderId = ++renderMapRunId;
                    const isStaleRender = () => currentRenderId !== renderMapRunId;
                    const debugSummary = [];
                    const debugDetails = [];
                    clearDynamicMapLayers();
                    highlightedMarker = null;
                    highlightedMarkerOrder = null;
                    highlightedMarkerType = null;
                    const totalDays = Math.max(1, parseInt(durationInput.value || '1', 10));
                    const anchors = buildDayAnchors(totalDays);
                    let previousEndPoint = null;
                    for (let day = 1; day <= totalDays; day++) {
                        const startPoint = await getDayStartPoint(day, previousEndPoint);
                        const endPoint = await getDayEndPoint(day);
                        if (isStaleRender()) return;
                        anchors.startByDay[day] = startPoint;
                        // Only render end point when explicitly selected.
                        anchors.endByDay[day] = endPoint;
                        // Still carry forward the last explicit endpoint for "previous_day_end".
                        previousEndPoint = endPoint || previousEndPoint;
                    }
                    debugSummary.push(`Total days: ${totalDays}`);
                    for (let day = 1; day <= totalDays; day++) {
                        const startAnchor = anchors.startByDay[day];
                        const endAnchor = anchors.endByDay[day];
                        debugDetails.push(
                            `Anchors Day ${day}: start=${startAnchor ? `${startAnchor.name} [${startAnchor.lat}, ${startAnchor.lng}]` : 'null'} | end=${endAnchor ? `${endAnchor.name} [${endAnchor.lat}, ${endAnchor.lng}]` : 'null'}`
                        );
                    }

                    const points = [];
                    const rowDebugLines = [];
                    const scheduleRows = [...daySections.querySelectorAll('.schedule-row')];
                    scheduleRows.forEach((r, rowIndex) => {
                        const selection = getRowSelection(r);
                        const opt = selection.option;
                        const label = opt?.textContent?.trim() || '(empty)';
                        const day = parseInt(r.querySelector('.item-day')?.value || '1', 10);
                        const order = parseInt(r.querySelector('.item-order')?.value || '0', 10);
                        const lat = parseFloat(opt?.dataset?.latitude || '');
                        const lng = parseFloat(opt?.dataset?.longitude || '');
                        const attractionValue = String(r.querySelector('.item-attraction')?.value || '');
                        const activityValue = String(r.querySelector('.item-activity')?.value || '');
                        const fnbValue = String(r.querySelector('.item-fnb')?.value || '');
                        const typeFieldValue = String(r.querySelector('.item-type')?.value || '');

                        if (!opt) {
                            rowDebugLines.push(
                                `Row ${rowIndex + 1} | Day ${day} | type=${selection.type} | source=${selection.source} | typeField=${typeFieldValue} | values[a=${attractionValue || '-'}, act=${activityValue || '-'}, fnb=${fnbValue || '-'}] | skipped: no selected option`
                            );
                            return;
                        }

                        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                            rowDebugLines.push(
                                `Row ${rowIndex + 1} | Day ${day} | type=${selection.type} | source=${selection.source} | typeField=${typeFieldValue} | values[a=${attractionValue || '-'}, act=${activityValue || '-'}, fnb=${fnbValue || '-'}] | ${label} | skipped: invalid coordinates [${opt?.dataset?.latitude || ''}, ${opt?.dataset?.longitude || ''}]`
                            );
                            return;
                        }

                        const travelRaw = r.querySelector('.item-travel')?.value || '';
                        const travelInput = travelRaw !== '' ? parseInt(travelRaw, 10) : null;
                        rowDebugLines.push(
                            `Row ${rowIndex + 1} | Day ${day} | order=${order} | type=${selection.type} | source=${selection.source} | typeField=${typeFieldValue} | values[a=${attractionValue || '-'}, act=${activityValue || '-'}, fnb=${fnbValue || '-'}] | ${label} [${lat}, ${lng}]`
                        );
                        points.push({
                            lat,
                            lng,
                            name: label,
                            type: selection.type,
                            day,
                            order,
                            isMainExperience: r.querySelector('.item-main-experience')?.checked ===
                                true,
                            travelInput: Number.isFinite(travelInput) ? Math.max(0, travelInput) :
                                null
                        });
                    });
                    debugSummary.push(`Schedule rows: ${scheduleRows.length}`);
                    debugSummary.push(`Schedule points parsed: ${points.length}`);
                    debugDetails.push('Schedule rows:');
                    debugDetails.push(...rowDebugLines);
                    const scheduleByDay = points.reduce((acc, point) => {
                        const key = String(point.day);
                        (acc[key] = acc[key] || []).push(point);
                        return acc;
                    }, {});
                    const routePointsByDay = {};
                    for (let day = 1; day <= totalDays; day++) {
                        const daySection = daySections.querySelector(`.day-section[data-day="${day}"]`);
                        const startTravelRaw = daySection?.querySelector('.day-start-travel')?.value || '';
                        const startTravelMinutes = startTravelRaw !== '' ? Math.max(0, parseInt(startTravelRaw,
                            10)) : null;
                        const daySchedule = (scheduleByDay[String(day)] || []).sort((a, b) => a.order - b.order);
                        const dayPoints = [];

                        const startAnchor = anchors.startByDay[day];
                        const endAnchor = anchors.endByDay[day];

                        if (startAnchor && Number.isFinite(startAnchor.lat) && Number.isFinite(startAnchor.lng)) {
                            dayPoints.push({
                                lat: startAnchor.lat,
                                lng: startAnchor.lng,
                                name: startAnchor.name,
                                type: normalizePointType(startAnchor.type || '', 'hotel'),
                                day,
                                order: 0,
                                anchorRole: 'start',
                                travelInput: Number.isFinite(startTravelMinutes) ? startTravelMinutes :
                                    null,
                            });
                        }

                        daySchedule.forEach((item, index) => {
                            dayPoints.push({
                                ...item,
                                order: index + 1,
                            });
                        });

                        const lastPoint = dayPoints[dayPoints.length - 1] || null;
                        if (endAnchor && Number.isFinite(endAnchor.lat) && Number.isFinite(endAnchor.lng)) {
                            const isDuplicateLast = lastPoint && Math.abs(lastPoint.lat - endAnchor.lat) <
                                0.000001 &&
                                Math.abs(lastPoint.lng - endAnchor.lng) < 0.000001;
                            if (!isDuplicateLast) {
                                dayPoints.push({
                                    lat: endAnchor.lat,
                                    lng: endAnchor.lng,
                                    name: endAnchor.name,
                                    type: normalizePointType(endAnchor.type || '', 'hotel'),
                                    day,
                                    order: dayPoints.length + 1,
                                    anchorRole: 'end',
                                    travelInput: null,
                                });
                            }
                        }

                        if (dayPoints.length > 0) {
                            routePointsByDay[String(day)] = dayPoints;
                        }
                        debugDetails.push(
                            `Day ${day}: ${dayPoints.length} point(s) | ` +
                            dayPoints.map((point) => `${point.order}:${point.name} [${point.lat}, ${point.lng}]`).join(' -> ')
                        );
                    }

                    const allPoints = Object.values(routePointsByDay).flat();
                    debugSummary.push(`Renderable points: ${allPoints.length}`);
                    if (!allPoints.length) {
                        if (isStaleRender()) return;
                        setRouteDebug(debugSummary, debugDetails);
                        map.setView([-6.2, 106.816666], 5);
                        return;
                    }

                    const ll = [];
                    const scheduleIndexByDay = {};
                    const dayMeta = {};
                    const sortedPoints = allPoints.sort((a, b) => (a.day - b.day) || (a.order - b.order));

                    sortedPoints.forEach((p) => {
                        const dayKey = String(p.day);
                        if (!dayMeta[dayKey]) {
                            dayMeta[dayKey] = {
                                hasStart: false,
                                hasEnd: false,
                                maxScheduleOrder: 0,
                            };
                        }
                        const isAnchor = Boolean(p.anchorRole && p.anchorRole !== '');
                        if (isAnchor) {
                            if (p.anchorRole === 'start') dayMeta[dayKey].hasStart = true;
                            if (p.anchorRole === 'end') dayMeta[dayKey].hasEnd = true;
                            return;
                        }

                        const fallbackIndex = (scheduleIndexByDay[dayKey] || 0) + 1;
                        const scheduleOrder = Number.isFinite(p.order) && p.order > 0 ? p.order : fallbackIndex;
                        scheduleIndexByDay[dayKey] = fallbackIndex;
                        p._scheduleOrder = scheduleOrder;
                        if (scheduleOrder > dayMeta[dayKey].maxScheduleOrder) {
                            dayMeta[dayKey].maxScheduleOrder = scheduleOrder;
                        }
                    });

                    sortedPoints.forEach((p) => {
                        const pt = [p.lat, p.lng];
                        ll.push(pt);
                        const labelMap = {
                            attraction: 'Attraction',
                            activity: 'Activity',
                            fnb: 'F&B',
                            hotel: 'Hotel',
                            airport: 'Airport',
                        };
                        const label = labelMap[p.type] || 'Point';
                        const markerType = p.type === 'activity' ?
                            'activity' :
                            (p.type === 'fnb' ?
                                'fnb' :
                                (p.type === 'attraction' ? 'attraction' : (p.type === 'airport' ?
                                    'airport' : 'hotel')));
                        const dayKey = String(p.day);
                        const isAnchor = Boolean(p.anchorRole && p.anchorRole !== '');
                        const meta = dayMeta[dayKey] || {
                            hasStart: false,
                            hasEnd: false,
                            maxScheduleOrder: 0
                        };
                        const baseIndex = meta.hasStart ? 1 : 0;
                        const scheduleOrder = !isAnchor ? (p._scheduleOrder || 1) : 0;
                        const badgeNo = isAnchor ?
                            (p.anchorRole === 'start' ? 1 : (baseIndex + meta.maxScheduleOrder + (meta.hasEnd ?
                                1 : 0))) :
                            (baseIndex + scheduleOrder);
                        const marker = L.marker(pt, {
                                icon: createBadgeIcon(badgeNo, markerType, p.isMainExperience === true)
                            })
                            .bindPopup(`#${badgeNo} | Day ${p.day} | ${label}: ${p.name}`)
                            .addTo(markers);
                        
                        // Store marker in lookup for schedule item button clicks
                        const markerKey = String(isAnchor ? `${p.anchorRole}-point-day-${p.day}` : `schedule-${p.day}-${scheduleOrder}`);
                        markerLookup.set(markerKey, { marker, badgeNo, type: markerType });
                        
                        // Keep anchor points consistent with other items: icon + number only (no labels).
                    });
                    if (isStaleRender()) return;

                    for (const [dayKey, dayPoints] of Object.entries(routePointsByDay)) {
                        const sorted = dayPoints.sort((a, b) => a.order - b.order);
                        if (sorted.length < 2) {
                            debugDetails.push(`Day ${dayKey}: skipped route, only ${sorted.length} point.`);
                            continue;
                        }
                        const color = routeColors[(parseInt(dayKey, 10) - 1) % routeColors.length];
                        const fallbackCoordinates = sorted.map((point) => [point.lat, point.lng]);
                        const fallbackLine = drawRouteLine(fallbackCoordinates, color);
                        debugDetails.push(`Day ${dayKey}: fallback line drawn with ${fallbackCoordinates.length} coordinates.`);

                        const coordinates = sorted.map((point) => `${point.lng},${point.lat}`).join(';');
                        try {
                            const response = await fetch(
                                `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson`
                            );
                            if (isStaleRender()) return;
                            debugDetails.push(`Day ${dayKey}: OSRM HTTP ${response.status}.`);
                            const data = await response.json();
                            if (isStaleRender()) return;
                            const geometry = data?.routes?.[0]?.geometry;
                            if (geometry && Array.isArray(geometry.coordinates) && geometry.coordinates.length > 1) {
                                map.removeLayer(fallbackLine);
                                routeLayers.pop();
                                const routeCoordinates = geometry.coordinates.map((point) => [point[1], point[0]]);
                                drawRouteLine(routeCoordinates, color);
                                debugDetails.push(`Day ${dayKey}: OSRM success, geometry points ${geometry.coordinates.length}.`);
                            } else {
                                debugDetails.push(`Day ${dayKey}: OSRM returned no geometry.`);
                            }
                        } catch (error) {
                            debugDetails.push(`Day ${dayKey}: OSRM error -> ${error?.message || 'unknown error'}`);
                        }

                        for (let i = 0; i < sorted.length - 1; i++) {
                            const from = sorted[i];
                            const to = sorted[i + 1];
                            if (from.travelInput === null) continue;
                            const midLat = (from.lat + to.lat) / 2;
                            const midLng = (from.lng + to.lng) / 2;
                            const badge = L.marker([midLat, midLng], {
                                icon: travelBadgeIcon(from.travelInput),
                                interactive: false
                            }).addTo(map);
                            routeLayers.push(badge);
                        }
                    }

                    if (isStaleRender()) return;
                    debugSummary.push(`Marker layers: ${markers.getLayers().length}`);
                    debugSummary.push(`Active route layers: ${routeLayers.length}`);
                    setRouteDebug(debugSummary, debugDetails);
                    if (ll.length === 1) map.setView(ll[0], 14);
                    else map.fitBounds(ll, {
                        padding: [20, 20]
                    });
                };
                
                const focusSchedulePoint = async (day, markerKey) => {
                    if (!Number.isFinite(day) || !markerKey) return;
                    
                    const markerData = markerLookup.get(String(markerKey));
                    if (!markerData?.marker) return;
                    
                    const marker = markerData.marker;
                    const badgeNo = markerData.badgeNo;
                    
                    // Reset previous highlight
                    if (highlightedMarker && highlightedMarkerOrder !== null) {
                        highlightedMarker.setIcon(createBadgeIcon(highlightedMarkerOrder, highlightedMarkerType, false));
                    }
                    
                    // Highlight new marker
                    marker.setIcon(createBadgeIcon(badgeNo, markerData.type, true));
                    highlightedMarker = marker;
                    highlightedMarkerOrder = badgeNo;
                    highlightedMarkerType = markerData.type;
                    
                    // Pan to marker and show popup
                    map.panTo(marker.getLatLng(), { animate: true, duration: 0.35 });
                    marker.openPopup();
                };
                
                const attachScheduleItemButtonListeners = () => {
                    const scheduleItemButtons = document.querySelectorAll('.schedule-item-index-btn');
                    scheduleItemButtons.forEach((button) => {
                        // Remove old listeners by replacing with cloned node
                        const newButton = button.cloneNode(true);
                        button.parentNode.replaceChild(newButton, button);
                        
                        // Attach new listener
                        newButton.addEventListener('click', async () => {
                            const day = Number(newButton.dataset.day || '');
                            const markerKey = String(newButton.dataset.markerKey || '');
                            if (Number.isFinite(day) && markerKey) {
                                await focusSchedulePoint(day, markerKey);
                            }
                        });
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
                    await renderMap();
                    attachScheduleItemButtonListeners();
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
                attachScheduleItemButtonListeners();
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
                    destinationDropdown.innerHTML = items
                        .map((item, idx) => {
                            const safeValue = String(item).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                            return `<button type="button" data-index="${idx}" data-destination-value="${safeValue}"  class="block w-full rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 dark:text-gray-100 dark:hover:bg-indigo-900/30">${safeValue}</button>`;
                        })
                        .join('');
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
                    return city.includes(keyword) || province.includes(keyword);
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
