@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $itinerary = $itinerary ?? null;
    $inquiries = $inquiries ?? collect();
    $airports = $airports ?? collect();
    $hotels = $hotels ?? collect();
    $transportUnits = $transportUnits ?? collect();
    $islandTransfers = $islandTransfers ?? collect();
    $destinations = $destinations ?? collect();
    $destinationNameById = $destinations->pluck('name', 'id')->toArray();
    $prefillInquiryId = $prefillInquiryId ?? null;
    $normalizePointType = static fn ($value, string $default = ''): string => trim((string) $value) !== ''
        ? trim((string) $value)
        : $default;
    $selectedInquiryId = old('inquiry_id', $itinerary->inquiry_id ?? $prefillInquiryId);
    $durationDays = max(1, min(7, (int) old('duration_days', $itinerary->duration_days ?? 1)));
    $durationNights = max(
        0,
        min(
            6,
            $durationDays,
            (int) old('duration_nights', $itinerary->duration_nights ?? max(0, ($itinerary->duration_days ?? 1) - 1)),
        ),
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
    $rawIslandTransfers = old('itinerary_island_transfer_items');
    if (!is_array($rawIslandTransfers)) {
        $rawIslandTransfers = [];
        if (isset($itinerary)) {
            foreach ($itinerary->itineraryIslandTransfers as $t) {
                $rawIslandTransfers[] = [
                    'island_transfer_id' => $t->island_transfer_id,
                    'pax' => $t->pax ?? 1,
                    'day_number' => $t->day_number ?? 1,
                    'start_time' => $t->start_time ? substr((string) $t->start_time, 0, 5) : '',
                    'end_time' => $t->end_time ? substr((string) $t->end_time, 0, 5) : '',
                    'travel_minutes_to_next' => $t->travel_minutes_to_next ?? null,
                    'visit_order' => $t->visit_order ?? null,
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
    $islandTransfersSorted = collect($islandTransfers ?? [])
        ->sortBy(function ($item) {
            $vendor = strtolower(trim((string) ($item->vendor?->name ?? '')));
            $name = strtolower(trim((string) ($item->name ?? '')));
            return $vendor . '|' . $name;
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
    $itemRegions = $touristAttractionsSorted
        ->pluck('city')
        ->merge($activitiesSorted->pluck('vendor.city'))
        ->merge($islandTransfersSorted->pluck('vendor.city'))
        ->merge($foodBeveragesSorted->pluck('vendor.city'))
        ->map(fn ($city) => trim((string) $city))
        ->filter(fn ($city) => $city !== '')
        ->unique()
        ->sort(fn ($a, $b) => strcasecmp($a, $b))
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
    $dailyStartHotelBookingModes = old('daily_start_hotel_booking_modes');
    $dailyEndHotelBookingModes = old('daily_end_hotel_booking_modes');
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
                $dailyEndPointTypes[$day] = $normalizePointType($dayPoint->end_point_type ?? '', '');
                $dailyEndPointItems[$day] =
                    (string) ($dailyEndPointTypes[$day] === 'airport'
                        ? $dayPoint->end_airport_id ?? ''
                        : $dayPoint->end_hotel_id ?? '');
                $dailyEndPointRoomIds[$day] = (string) ($dayPoint->end_hotel_room_id ?? '');
                $dailyEndHotelBookingModes[$day] = (string) ($dayPoint->end_hotel_booking_mode ?? 'arranged');
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
                    $dailyEndHotelBookingModes[$day] = 'arranged';
                }
            }
        }
    }
    if (!is_array($dailyEndHotelBookingModes)) {
        $dailyEndHotelBookingModes = [];
    }
    if (!is_array($dailyStartHotelBookingModes)) {
        $dailyStartHotelBookingModes = [];
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
    $dailyTransportUnitItemsByDay = [];
    if (is_array($dailyTransportUnitItems)) {
        foreach ($dailyTransportUnitItems as $transportRow) {
            if (!is_array($transportRow)) {
                continue;
            }
            $day = (int) ($transportRow['day_number'] ?? 0);
            if ($day <= 0) {
                continue;
            }
            $dailyTransportUnitItemsByDay[$day] = $dailyTransportUnitItemsByDay[$day] ?? [];
            $dailyTransportUnitItemsByDay[$day][] = [
                'day_number' => $day,
                'transport_unit_id' => (string) ($transportRow['transport_unit_id'] ?? ''),
            ];
        }
    } elseif (isset($itinerary)) {
        foreach ($itinerary->itineraryTransportUnits as $transportItem) {
            $day = (int) ($transportItem->day_number ?? 0);
            if ($day <= 0) {
                continue;
            }
            $dailyTransportUnitItemsByDay[$day] = $dailyTransportUnitItemsByDay[$day] ?? [];
            $dailyTransportUnitItemsByDay[$day][] = [
                'day_number' => $day,
                'transport_unit_id' => (string) ($transportItem->transport_unit_id ?? ''),
            ];
        }
    }
    $dailyTransportUnitItems = [];
    for ($day = 1; $day <= $durationDays; $day++) {
        $rowsForDay = $dailyTransportUnitItemsByDay[$day] ?? [];
        if (!is_array($rowsForDay) || count($rowsForDay) === 0) {
            $rowsForDay = [[
                'day_number' => $day,
                'transport_unit_id' => '',
            ]];
        }
        $dailyTransportUnitItems[$day] = array_values(array_slice($rowsForDay, 0, 10));
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
                $dailyStartPointTypes[$day] = $normalizePointType($dayPoint->start_point_type ?? '', '');
                $dailyStartPointItems[$day] =
                    (string) ($dailyStartPointTypes[$day] === 'airport'
                        ? $dayPoint->start_airport_id ?? ''
                        : $dayPoint->start_hotel_id ?? '');
                $dailyStartPointRoomIds[$day] = (string) ($dayPoint->start_hotel_room_id ?? '');
                $dailyStartHotelBookingModes[$day] = (string) ($dayPoint->start_hotel_booking_mode ?? 'arranged');
            }
        }
        for ($day = 1; $day <= $durationDays; $day++) {
            if (!isset($dailyStartPointTypes[$day])) {
                $dailyStartPointTypes[$day] = '';
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
    $itineraryInclude = old('itinerary_include', (string) ($itinerary->itinerary_include ?? ''));
    $itineraryExclude = old('itinerary_exclude', (string) ($itinerary->itinerary_exclude ?? ''));

    $rows = collect();
    foreach ($rawAttractions as $i => $item) {
        $rows->push([
            'item_type' => 'attraction',
            'tourist_attraction_id' => $item['tourist_attraction_id'] ?? '',
            'activity_id' => '',
            'island_transfer_id' => '',
            'food_beverage_id' => '',
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
        $activityId = (int) ($item['activity_id'] ?? 0);
        $rows->push([
            'item_type' => 'activity',
            'tourist_attraction_id' => '',
            'activity_id' => $activityId > 0 ? (string) $activityId : '',
            'island_transfer_id' => '',
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
    foreach ($rawIslandTransfers as $i => $item) {
        $islandTransferId = (int) ($item['island_transfer_id'] ?? 0);
        $rows->push([
            'item_type' => 'transfer',
            'tourist_attraction_id' => '',
            'activity_id' => '',
            'island_transfer_id' => $islandTransferId > 0 ? (string) $islandTransferId : '',
            'food_beverage_id' => '',
            'pax' => max(1, (int) ($item['pax'] ?? 1)),
            'day_number' => (int) ($item['day_number'] ?? 1),
            'start_time' => $item['start_time'] ?? '',
            'end_time' => $item['end_time'] ?? '',
            'travel_minutes_to_next' => $item['travel_minutes_to_next'] ?? '',
            'visit_order' => $item['visit_order'] ?? null,
            '_sort' => 150000 + $i,
        ]);
    }
    foreach ($rawFoodBeverages as $i => $item) {
        $rows->push([
            'item_type' => 'fnb',
            'tourist_attraction_id' => '',
            'activity_id' => '',
            'island_transfer_id' => '',
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
            'created_at_iso' => $inquiry->created_at ? $inquiry->created_at->toIso8601String() : null,
            'notes' => \App\Support\SafeRichText::sanitize($inquiry->notes ?? null) ?: '-',
            'reminder_note' => \App\Support\SafeRichText::sanitize($latestFollowUp?->note ?? null) ?: '-',
            'reminder_reason' => \App\Support\SafeRichText::sanitize($latestFollowUp?->done_reason ?? null) ?: '-',
        ];
    }
@endphp

<div class="space-y-4 itinerary-form-page" data-itinerary-wizard>
    <div class="itinerary-wizard-shell rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="itinerary-wizard-steps">
            <button type="button" class="itinerary-wizard-step is-active" data-wizard-step-chip="1">
                <span class="itinerary-wizard-step__index">1</span>
                <span class="itinerary-wizard-step__label">{{ __('itinerary_form.wizard.step_basic_info') }}</span>
            </button>
            <button type="button" class="itinerary-wizard-step" data-wizard-step-chip="2">
                <span class="itinerary-wizard-step__index">2</span>
                <span class="itinerary-wizard-step__label">{{ __('itinerary_form.wizard.step_day_planner') }}</span>
            </button>
            <button type="button" class="itinerary-wizard-step" data-wizard-step-chip="3">
                <span class="itinerary-wizard-step__index">3</span>
                <span class="itinerary-wizard-step__label">{{ __('itinerary_form.wizard.step_include_exclude') }}</span>
            </button>
            <button type="button" class="itinerary-wizard-step" data-wizard-step-chip="4">
                <span class="itinerary-wizard-step__index">4</span>
                <span class="itinerary-wizard-step__label">{{ __('itinerary_form.wizard.step_review') }}</span>
            </button>
        </div>
        <div class="itinerary-wizard-progress-track mt-2">
            <div class="itinerary-wizard-progress-fill" data-wizard-progress-fill style="width: 25%;"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-9">
    <section data-wizard-step="1" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Inquiry') }} <span><i>{{ __('(Optional)') }}</i></span></label>
        <select id="inquiry-select" name="inquiry_id"
            class="mt-1 dark:border-gray-600 app-input">
            <option value="">{{ __('Independent itinerary (no inquiry)') }}</option>
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
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Title') }}</label>
        <input name="title" value="{{ old('title', $itinerary->title ?? '') }}"
            class="mt-1 dark:border-gray-600 app-input"
            placeholder="{{ __('Example: 3D2N Tour Package') }}"
            required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Order Number') }} <span><i>{{ __('(Optional)') }}</i></span></label>
        <input name="order_number" value="{{ old('order_number', $itinerary->order_number ?? '') }}"
            class="mt-1 dark:border-gray-600 app-input"
            placeholder="{{ __('Example: CODE260424A') }}">
        @error('order_number')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
        <div class="md:col-span-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Destination') }}</label>
            <div class="relative mt-1">
                <input id="itinerary-destination" name="destination"
                    value="{{ old('destination', $itinerary->destination ?? '') }}"
                    data-endpoint="{{ route('itineraries.destination-suggestions') }}" autocomplete="off"
                    placeholder="{{ __('Example: Bali, Lombok, Jakarta') }}"
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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Duration (Days)') }}</label>
            <input id="duration-days" name="duration_days" type="number" min="1" max="10" step="1" inputmode="numeric"
                value="{{ $durationDays }}"
                class="mt-1 dark:border-gray-600 app-input"
                required>
        </div>
        <div class="md:col-span-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Duration (Nights)') }}</label>
            <input id="duration-nights" name="duration_nights" type="number" min="0" max="6" step="1" inputmode="numeric"
                value="{{ $durationNights }}"
                class="mt-1 dark:border-gray-600 app-input"
                required>
            @error('duration_nights')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Description') }} <span><i>{{ __('(Optional)') }}</i></span></label>
        <textarea name="description" rows="4"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $itinerary->description ?? '') }}</textarea>
    </div>
    </section>

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

    <section data-wizard-step="2" class="space-y-2 hidden">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('itinerary_form.wizard.schedule_items_optional') }}</p>
        <div class="itinerary-day-wizard rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-900/40">
            <div class="flex items-center justify-between gap-2">
                <button type="button" class="btn-secondary-sm" data-day-wizard-prev>{{ __('itinerary_form.wizard.previous_day') }}</button>
                <div id="itinerary-day-wizard-tabs" class="itinerary-day-wizard-tabs flex flex-wrap items-center justify-center gap-2"></div>
                <button type="button" class="btn-secondary-sm" data-day-wizard-next>{{ __('itinerary_form.wizard.next_day') }}</button>
            </div>
            <p class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">{{ __('itinerary_form.wizard.focus_one_day_hint') }}</p>
        </div>
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
                        $startType = $normalizePointType($dailyStartPointTypes[$day] ?? '', '');
                        $startItem = (string) ($dailyStartPointItems[$day] ?? '');
                        $startRoomId = (string) ($dailyStartPointRoomIds[$day] ?? '');
                        $startHotelBookingMode = (string) ($dailyStartHotelBookingModes[$day] ?? 'arranged');
                        if (!in_array($startHotelBookingMode, ['arranged', 'self'], true)) {
                            $startHotelBookingMode = 'arranged';
                        }
                        $endType = $normalizePointType($dailyEndPointTypes[$day] ?? '', '');
                        $endItem = (string) ($dailyEndPointItems[$day] ?? '');
                        $endRoomId = (string) ($dailyEndPointRoomIds[$day] ?? '');
                        $endHotelBookingMode = (string) ($dailyEndHotelBookingModes[$day] ?? 'arranged');
                        if (!in_array($endHotelBookingMode, ['arranged', 'self'], true)) {
                            $endHotelBookingMode = 'arranged';
                        }
                        $dayStartTravelMinutes = old(
                            "day_start_travel_minutes.$day",
                            isset($existingDayPoint)
                                ? (string) ($existingDayPoint->day_start_travel_minutes ?? '')
                                : '',
                        );
                        $mainExperienceType = (string) ($dailyMainExperienceTypes[$day] ?? '');
                        $mainExperienceItem = (string) ($dailyMainExperienceItems[$day] ?? '');
                    @endphp
                    <div class="day-card-header mb-3 min-w-0">
                        <div class="app-day-header day-card-header-pill min-w-0 flex-1">
                            <p class="day-title-label app-day-header-title">Day {{ $day }}</p>
                            <p class="day-endpoint-badge app-day-header-meta">
                                Starts at: <span class="day-starts-at-label">{{ __('Not set') }}</span>
                                <span class="mx-1">|</span>
                                Ends at: <span class="day-ends-at-label">{{ __('Not set') }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 xl:flex-nowrap">
                        <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                            <label class="whitespace-nowrap text-xs text-gray-500">{{ __('Start Tour') }}</label>
                            <input type="time" value="{{ $dayStart }}"
                                name="day_start_times[{{ $day }}]"
                                class="day-start-time dark:border-gray-600 app-input w-full sm:w-36">
                            <label class="whitespace-nowrap text-xs text-gray-500">{{ __('End Tour') }}</label>
                            <input type="time" value=""
                                class="day-end-time text-gray-700 dark:border-gray-600 app-input w-full sm:w-36"
                                readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        @php
                            $dayTransportRows = $dailyTransportUnitItems[$day] ?? [];
                            if (!is_array($dayTransportRows) || count($dayTransportRows) === 0) {
                                $dayTransportRows = [[
                                    'day_number' => $day,
                                    'transport_unit_id' => '',
                                ]];
                            }
                        @endphp
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                Day {{ $day }} Transport Units
                            </label>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
                                <div class="day-transport-units space-y-2 md:col-span-9">
                                    @foreach ($dayTransportRows as $transportRowIndex => $transportRow)
                                        <div class="day-transport-row flex items-center gap-2">
                                            <select
                                                class="day-transport-unit dark:border-gray-600 app-input flex-1">
                                                <option value="">{{ __('Select transport unit') }}</option>
                                                @foreach ($transportUnits ?? collect() as $unit)
                                                    <option value="{{ $unit->id }}"
                                                        data-city="{{ $unit->transport?->vendor?->city ?? '' }}"
                                                        data-province="{{ $unit->transport?->vendor?->province ?? '' }}"
                                                        data-location="{{ $unit->transport?->vendor?->location ?? '' }}"
                                                        data-destination="{{ $unit->transport?->vendor?->destination?->name ?? '' }}"
                                                        @selected((string) ($transportRow['transport_unit_id'] ?? '') === (string) $unit->id)>
                                                        {{ $unit->transport?->name ?? $unit->name }}{{ !empty($unit->seat_capacity) ? ' (' . $unit->seat_capacity . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" class="day-transport-day app-input" value="{{ $day }}">
                                            <button
                                                type="button"
                                                class="btn-ghost-sm day-transport-remove-btn {{ count($dayTransportRows) > 1 ? '' : 'hidden' }}"
                                                data-remove-transport="1"
                                                title="{{ __('Remove transport') }}"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="md:col-span-3 md:justify-self-end md:sticky md:top-2 self-start">
                                    <button type="button" class="btn-outline-sm min-h-[42px] h-[42px] w-full md:w-auto" data-add-transport="1">
                                        + Transport
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mb-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3 day-start-point-card dark:border-slate-600 dark:bg-slate-900/25">
                        <div class="space-y-2">
                            <label
                                class="day-start-point-label mb-1 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                <span class="mr-2 day-start-point-seq inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span>
                                <span>Day {{ $day }} Start Point</span>
                            </label>
                            <div class="mb-3 flex flex-col gap-2 md:flex-row">
                                <div class="md:w-1/4">
                                    <select name="daily_start_point_types[{{ $day }}]"
                                        class="day-start-point-type dark:border-gray-600 app-input">
                                        <option value="" @selected($startType === '')>{{ __('Not set') }}</option>
                                        @if ($day !== 1)
                                            <option value="previous_day_end" @selected($startType === 'previous_day_end')>Previous Day
                                                Endpoint (Auto)</option>
                                        @endif
                                        <option value="hotel" @selected($startType === 'hotel')>{{ __('Hotel') }}</option>
                                        <option value="airport" @selected($startType === 'airport')>{{ __('Airport') }}</option>
                                    </select>
                                </div>
                                <div class="md:w-1/4">
                                    <select name="daily_start_point_items[{{ $day }}]"
                                        class="day-start-point-item dark:border-gray-600 app-input">
                                        <option value="">{{ __('Select start point item') }}</option>
                                        @foreach ($hotelsSorted as $hotel)
                                            <option value="{{ $hotel->id }}" data-point-type="hotel"
                                                data-location="{{ $hotel->address ?? '' }}"
                                                data-city="{{ $hotel->city ?? '' }}"
                                                data-province="{{ $hotel->province ?? '' }}"
                                                data-destination="{{ $destinationNameById[$hotel->destination_id] ?? '' }}"
                                                data-latitude="{{ $hotel->latitude ?? '' }}"
                                                data-longitude="{{ $hotel->longitude ?? '' }}"
                                                @selected($startType === 'hotel' && $startItem === (string) $hotel->id)>
                                                {{ $hotel->name }}
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
                                <div class="day-start-room-wrap {{ $startType === 'hotel' ? '' : 'hidden' }} md:w-1/4">
                                    <select name="daily_start_point_room_ids[{{ $day }}]"
                                        class="day-start-room-select dark:border-gray-600 app-input"
                                        {{ $startType === 'hotel' ? '' : 'disabled' }}>
                                        <option value="">{{ __('Select room') }}</option>
                                        @foreach ($hotels as $hotel)
                                            @foreach ($hotel->rooms ?? collect() as $room)
                                                <option value="{{ $room->id }}"
                                                    data-hotel-id="{{ $hotel->id }}"
                                                    @selected($startType === 'hotel' && $startRoomId === (string) $room->id)>
                                                    {{ $room->rooms }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                                <div class="day-start-booking-wrap {{ $startType === 'hotel' ? '' : 'hidden' }} md:w-1/4">
                                    <select name="daily_start_hotel_booking_modes[{{ $day }}]"
                                        class="day-start-booking-mode dark:border-gray-600 app-input"
                                        {{ $startType === 'hotel' ? '' : 'disabled' }}>
                                        <option value="arranged" @selected($startHotelBookingMode === 'arranged')>{{ __('Hotel arranged by us') }}</option>
                                        <option value="self" @selected($startHotelBookingMode === 'self')>{{ __('Self-booked hotel') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="travel-connector mb-3 w-full md:w-1/2">
                        <div class="input-with-left-affix">
                            <span class="input-left-affix">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true" focusable="false">
                                    <path d="M5.5 11.5L7.3 6.9C7.6 6.1 8.3 5.5 9.2 5.5h5.6c.9 0 1.6.6 1.9 1.4l1.8 4.6c1 .2 1.8 1.1 1.8 2.2v2.3c0 .8-.7 1.5-1.5 1.5h-.5a2.3 2.3 0 01-4.6 0h-4.4a2.3 2.3 0 01-4.6 0h-.5c-.8 0-1.5-.7-1.5-1.5v-2.3c0-1.1.8-2 1.8-2.2zm3.1-4.2L7.2 11h9.6l-1.4-3.7a.8.8 0 00-.7-.5H9.3c-.3 0-.6.2-.7.5zM8.2 18.9c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9zm7.6 0c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9z"/>
                                </svg>
                            </span>
                            <input type="number" min="0" step="5"
                                name="day_start_travel_minutes[{{ $day }}]"
                                value="{{ $dayStartTravelMinutes }}"
                                class="day-start-travel dark:border-gray-600 app-input"
                                placeholder="{{ __('Estimated travel time to the next item (minutes)') }}"
                                data-next-placeholder="{{ __('Estimated travel time to the next item (minutes)') }}"
                                data-endpoint-placeholder="{{ __('Estimated travel time to end point (minutes)') }}">
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
                                            : ($r['item_type'] === 'transfer'
                                                ? (string) ($r['island_transfer_id'] ?? '')
                                                : (string) ($r['food_beverage_id'] ?? '')));
                                $isRowMainExperience =
                                    $mainExperienceType !== '' &&
                                    $mainExperienceType === (string) ($r['item_type'] ?? '') &&
                                    $mainExperienceItem !== '' &&
                                    $mainExperienceItem === $rowItemId;
                            @endphp
                            <div class="schedule-row rounded-lg border border-slate-200 bg-slate-50/70 p-2.5 dark:border-slate-600 dark:bg-slate-900/30"
                                data-item-type="{{ $r['item_type'] }}">
                                <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                                    <button type="button"
                                         class="drag-handle inline-flex h-7 w-7 items-center justify-center rounded-lg border border-gray-300 text-base leading-none text-gray-600 dark:border-gray-600 dark:text-gray-300"
                                        title="{{ __('Drag to reorder') }}" aria-label="Drag to reorder">::</button>
                                    <div class="grid w-full grid-cols-1 gap-2 sm:w-auto sm:grid-cols-[120px_120px_auto_auto] sm:items-center">
                                        <p class="item-time-text text-xs font-medium text-gray-700 dark:text-gray-200 sm:col-span-2">
                                            Start Time: <span class="item-start-text">{{ !empty($r['start_time']) ? $r['start_time'] : '--:-- --' }}</span>
                                            <span class="mx-1">|</span>
                                            End Time: <span class="item-end-text">{{ !empty($r['end_time']) ? $r['end_time'] : '--:-- --' }}</span>
                                        </p>
                                        <input type="hidden" value="{{ $r['start_time'] ?? '' }}"
                                            class="item-start app-input">
                                        <input type="hidden" value="{{ $r['end_time'] ?? '' }}"
                                            class="item-end app-input">
                                        <label
                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                                            <input type="checkbox"
                                                class="item-main-experience rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                                                @checked($isRowMainExperience)>
                                            <span>{{ __('Highlight') }}</span>
                                        </label>
                                        <button type="button"
                                             class="remove-row inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-300 text-sm font-semibold leading-none text-rose-700"
                                            title="{{ __('Remove item') }}" aria-label="Remove item">&times;</button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-2 lg:grid-cols-12 lg:items-end">
                                    <div class="lg:col-span-1">
                                        <span
                                            class="item-seq-badge inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <select
                                            class="item-type dark:border-gray-600 app-input">
                                            <option value="attraction" @selected($r['item_type'] === 'attraction')>{{ __('Attraction') }}</option>
                                            <option value="activity" @selected($r['item_type'] === 'activity')>{{ __('Activity') }}</option>
                                            <option value="transfer" @selected($r['item_type'] === 'transfer')>{{ __('Inter Island Transfer') }}</option>
                                            <option value="fnb" @selected($r['item_type'] === 'fnb')>{{ __('F&B') }}</option>
                                        </select>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <select class="item-region dark:border-gray-600 app-input">
                                            <option value="">{{ __('All Regions') }}</option>
                                            @foreach ($itemRegions as $region)
                                                <option value="{{ $region }}">{{ $region }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="min-w-0 lg:col-span-6">
                                        <div class="item-attraction-wrap relative {{ $r['item_type'] !== 'attraction' ? 'hidden' : '' }}">
                                            <p class="item-attraction-notice mb-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ __('Example: Attraction Name, Region, Destination') }}
                                            </p>
                                            <input type="text"
                                                class="item-attraction-search dark:border-gray-600 app-input"
                                                placeholder="{{ __('Type: Attraction, Region, Destination') }}"
                                                autocomplete="off">
                                            <div
                                                class="item-attraction-dropdown absolute z-30 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-gray-300 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"></div>
                                            <select
                                                class="item-attraction hidden dark:border-gray-600 app-input">
                                                <option value="">{{ __('Select attraction') }}</option>
                                                @foreach ($touristAttractionsSorted as $a)
                                                    @php
                                                        $attractionDestination = trim((string) ($a->destination?->name ?? ($destinationNameById[$a->destination_id] ?? '')));
                                                    @endphp
                                                    <option value="{{ $a->id }}"
                                                        data-duration="{{ $a->ideal_visit_minutes ?? 120 }}"
                                                        data-destination="{{ $attractionDestination }}"
                                                        data-location="{{ $a->location ?? '' }}"
                                                        data-city="{{ $a->city ?? '' }}"
                                                        data-province="{{ $a->province ?? '' }}"
                                                        data-latitude="{{ $a->latitude }}"
                                                        data-longitude="{{ $a->longitude }}" @selected((string) ($r['tourist_attraction_id'] ?? '') === (string) $a->id)>
                                                        {{ $a->name }}, {{ !empty($a->city) ? $a->city : (!empty($a->province) ? $a->province : '-') }}, {{ $attractionDestination !== '' ? $attractionDestination : '-' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="item-activity-wrap relative {{ $r['item_type'] !== 'activity' ? 'hidden' : '' }}">
                                            <p class="item-activity-notice mb-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ __('Example: Activity Name, Region, Vendor') }}
                                            </p>
                                            <input type="text"
                                                class="item-activity-search dark:border-gray-600 app-input"
                                                placeholder="{{ __('Type: Activity, Region, Vendor') }}"
                                                autocomplete="off">
                                            <div
                                                class="item-activity-dropdown absolute z-30 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-gray-300 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"></div>
                                            <select
                                                class="item-activity hidden dark:border-gray-600 app-input">
                                                <option value="">{{ __('Select activity') }}</option>
                                                @foreach ($activitiesSorted as $a)
                                                    <option value="{{ $a->id }}"
                                                        data-duration="{{ $a->duration_minutes ?? 60 }}"
                                                        data-destination="{{ $a->vendor?->destination?->name ?? '' }}"
                                                        data-location="{{ $a->vendor?->location ?? '' }}"
                                                        data-city="{{ $a->vendor?->city ?? '' }}"
                                                        data-province="{{ $a->vendor?->province ?? '' }}"
                                                        data-latitude="{{ $a->vendor?->latitude ?? '' }}"
                                                        data-longitude="{{ $a->vendor?->longitude ?? '' }}"
                                                        @selected((string) ($r['activity_id'] ?? '') === (string) $a->id)>{{ $a->name }}, {{ !empty($a->vendor?->city) ? $a->vendor->city : (!empty($a->vendor?->province) ? $a->vendor->province : '-') }}, {{ !empty($a->vendor?->name) ? $a->vendor->name : '-' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <select
                                            class="item-transfer {{ $r['item_type'] !== 'transfer' ? 'hidden' : '' }} dark:border-gray-600 app-input">
                                            <option value="">{{ __('Select inter island transfer') }}</option>
                                            @foreach ($islandTransfersSorted as $t)
                                                <option value="{{ $t->id }}"
                                                    data-duration="{{ $t->duration_minutes ?? 60 }}"
                                                    data-city="{{ $t->vendor?->city ?? '' }}"
                                                    data-province="{{ $t->vendor?->province ?? '' }}"
                                                    data-latitude="{{ $t->arrival_latitude ?? '' }}"
                                                    data-longitude="{{ $t->arrival_longitude ?? '' }}"
                                                    data-departure-latitude="{{ $t->departure_latitude ?? '' }}"
                                                    data-departure-longitude="{{ $t->departure_longitude ?? '' }}"
                                                    data-arrival-latitude="{{ $t->arrival_latitude ?? '' }}"
                                                    data-arrival-longitude="{{ $t->arrival_longitude ?? '' }}"
                                                    data-route-geojson='@json($t->route_geojson ?? null, JSON_UNESCAPED_SLASHES)'
                                                    @selected((string) ($r['island_transfer_id'] ?? '') === (string) $t->id)>{{ $t->name }} - {{ !empty($t->vendor?->name) ? $t->vendor->name : '-' }}</option>
                                            @endforeach
                                        </select>
                                        <div class="item-fnb-wrap relative {{ $r['item_type'] !== 'fnb' ? 'hidden' : '' }}">
                                            <p class="item-fnb-notice mb-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ __('Example: F&B Name, Region, Vendor') }}
                                            </p>
                                            <input type="text"
                                                class="item-fnb-search dark:border-gray-600 app-input"
                                                placeholder="{{ __('Type: F&B Name, Region, Vendor') }}"
                                                autocomplete="off">
                                            <div
                                                class="item-fnb-dropdown absolute z-30 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-gray-300 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"></div>
                                            <select
                                                class="item-fnb hidden dark:border-gray-600 app-input">
                                                <option value="">{{ __('Select F&B') }}</option>
                                                @foreach ($foodBeveragesSorted as $f)
                                                    <option value="{{ $f->id }}"
                                                        data-duration="{{ $f->duration_minutes ?? 60 }}"
                                                        data-city="{{ $f->vendor?->city ?? '' }}"
                                                        data-province="{{ $f->vendor?->province ?? '' }}"
                                                        data-latitude="{{ $f->vendor?->latitude ?? '' }}"
                                                        data-longitude="{{ $f->vendor?->longitude ?? '' }}"
                                                        @selected((string) ($r['food_beverage_id'] ?? '') === (string) $f->id)>{{ $f->name }} - {{ !empty($f->vendor?->name) ? $f->vendor->name : '-' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <input type="hidden" value="{{ $r['pax'] ?? 1 }}" class="item-pax app-input">
                                    </div>
                                </div>

                                <input type="hidden" class="item-travel app-input"
                                    value="{{ $r['travel_minutes_to_next'] }}">
                                <input type="hidden" class="item-day app-input" value="{{ $day }}">
                                <input type="hidden" class="item-order app-input" value="{{ $r['visit_order'] ?? '' }}">
                            </div>
                        @empty
                            <div class="schedule-row schedule-row-template hidden rounded-lg border border-blue-200 bg-blue-50/60 p-2.5 dark:border-blue-700/60 dark:bg-blue-900/25"
                                data-item-type="attraction" data-row-template="1">
                                <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                                    <button type="button"
                                         class="drag-handle inline-flex h-7 w-7 items-center justify-center rounded-lg border border-gray-300 text-base leading-none text-gray-600 dark:border-gray-600 dark:text-gray-300"
                                        title="{{ __('Drag to reorder') }}" aria-label="Drag to reorder">::</button>
                                    <div class="grid w-full grid-cols-1 gap-2 sm:w-auto sm:grid-cols-[120px_120px_auto_auto] sm:items-center">
                                        <p class="item-time-text text-xs font-medium text-gray-700 dark:text-gray-200 sm:col-span-2">
                                            Start Time: <span class="item-start-text">--:-- --</span>
                                            <span class="mx-1">|</span>
                                            End Time: <span class="item-end-text">--:-- --</span>
                                        </p>
                                        <input type="hidden"
                                            class="item-start app-input">
                                        <input type="hidden"
                                            class="item-end app-input">
                                        <label
                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                                            <input type="checkbox"
                                                class="item-main-experience rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                                            <span>{{ __('Highlight') }}</span>
                                        </label>
                                        <button type="button"
                                             class="remove-row inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-300 text-sm font-semibold leading-none text-rose-700"
                                            title="{{ __('Remove item') }}" aria-label="Remove item">&times;</button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-2 lg:grid-cols-12 lg:items-end">
                                    <div class="lg:col-span-1">
                                        <span
                                            class="item-seq-badge inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <select
                                            class="item-type dark:border-gray-600 app-input">
                                            <option value="attraction">{{ __('Attraction') }}</option>
                                            <option value="activity">{{ __('Activity') }}</option>
                                            <option value="transfer">{{ __('Inter Island Transfer') }}</option>
                                            <option value="fnb">{{ __('F&B') }}</option>
                                        </select>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <select class="item-region dark:border-gray-600 app-input">
                                            <option value="">{{ __('All Regions') }}</option>
                                            @foreach ($itemRegions as $region)
                                                <option value="{{ $region }}">{{ $region }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="min-w-0 lg:col-span-6">
                                        <div class="item-attraction-wrap relative">
                                            <p class="item-attraction-notice mb-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ __('Example: Attraction Name, Region, Destination') }}
                                            </p>
                                            <input type="text"
                                                class="item-attraction-search dark:border-gray-600 app-input"
                                                placeholder="{{ __('Type: Attraction, Region, Destination') }}"
                                                autocomplete="off">
                                            <div
                                                class="item-attraction-dropdown absolute z-30 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-gray-300 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"></div>
                                            <select
                                                class="item-attraction hidden dark:border-gray-600 app-input">
                                                <option value="">{{ __('Select attraction') }}</option>
                                                @foreach ($touristAttractionsSorted as $a)
                                                    @php
                                                        $attractionDestination = trim((string) ($a->destination?->name ?? ($destinationNameById[$a->destination_id] ?? '')));
                                                    @endphp
                                                    <option value="{{ $a->id }}"
                                                        data-duration="{{ $a->ideal_visit_minutes ?? 120 }}"
                                                        data-destination="{{ $attractionDestination }}"
                                                        data-location="{{ $a->location ?? '' }}"
                                                        data-city="{{ $a->city ?? '' }}"
                                                        data-province="{{ $a->province ?? '' }}"
                                                        data-latitude="{{ $a->latitude }}"
                                                        data-longitude="{{ $a->longitude }}">{{ $a->name }}, {{ !empty($a->city) ? $a->city : (!empty($a->province) ? $a->province : '-') }}, {{ $attractionDestination !== '' ? $attractionDestination : '-' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="item-activity-wrap relative hidden">
                                            <p class="item-activity-notice mb-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ __('Example: Activity Name, Region, Vendor') }}
                                            </p>
                                            <input type="text"
                                                class="item-activity-search dark:border-gray-600 app-input"
                                                placeholder="{{ __('Type: Activity, Region, Vendor') }}"
                                                autocomplete="off">
                                            <div
                                                class="item-activity-dropdown absolute z-30 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-gray-300 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"></div>
                                            <select
                                                class="item-activity hidden dark:border-gray-600 app-input">
                                                <option value="">{{ __('Select activity') }}</option>
                                                @foreach ($activitiesSorted as $a)
                                                    <option value="{{ $a->id }}"
                                                        data-duration="{{ $a->duration_minutes ?? 60 }}"
                                                        data-destination="{{ $a->vendor?->destination?->name ?? '' }}"
                                                        data-location="{{ $a->vendor?->location ?? '' }}"
                                                        data-city="{{ $a->vendor?->city ?? '' }}"
                                                        data-province="{{ $a->vendor?->province ?? '' }}"
                                                        data-latitude="{{ $a->vendor?->latitude ?? '' }}"
                                                        data-longitude="{{ $a->vendor?->longitude ?? '' }}">
                                                        {{ $a->name }}, {{ !empty($a->vendor?->city) ? $a->vendor->city : (!empty($a->vendor?->province) ? $a->vendor->province : '-') }}, {{ !empty($a->vendor?->name) ? $a->vendor->name : '-' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <select
                                            class="item-transfer hidden dark:border-gray-600 app-input">
                                            <option value="">{{ __('Select inter island transfer') }}</option>
                                            @foreach ($islandTransfersSorted as $t)
                                                <option value="{{ $t->id }}"
                                                    data-duration="{{ $t->duration_minutes ?? 60 }}"
                                                    data-city="{{ $t->vendor?->city ?? '' }}"
                                                    data-province="{{ $t->vendor?->province ?? '' }}"
                                                    data-latitude="{{ $t->arrival_latitude ?? '' }}"
                                                    data-longitude="{{ $t->arrival_longitude ?? '' }}"
                                                    data-departure-latitude="{{ $t->departure_latitude ?? '' }}"
                                                    data-departure-longitude="{{ $t->departure_longitude ?? '' }}"
                                                    data-arrival-latitude="{{ $t->arrival_latitude ?? '' }}"
                                                    data-arrival-longitude="{{ $t->arrival_longitude ?? '' }}"
                                                    data-route-geojson='@json($t->route_geojson ?? null, JSON_UNESCAPED_SLASHES)'>
                                                    {{ $t->name }} - {{ !empty($t->vendor?->name) ? $t->vendor->name : '-' }}</option>
                                            @endforeach
                                        </select>
                                        <div class="item-fnb-wrap relative hidden">
                                            <p class="item-fnb-notice mb-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ __('Example: F&B Name, Region, Vendor') }}
                                            </p>
                                            <input type="text"
                                                class="item-fnb-search dark:border-gray-600 app-input"
                                                placeholder="{{ __('Type: F&B Name, Region, Vendor') }}"
                                                autocomplete="off">
                                            <div
                                                class="item-fnb-dropdown absolute z-30 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-gray-300 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"></div>
                                            <select
                                                class="item-fnb hidden dark:border-gray-600 app-input">
                                                <option value="">{{ __('Select F&B') }}</option>
                                                @foreach ($foodBeveragesSorted as $f)
                                                    <option value="{{ $f->id }}"
                                                        data-duration="{{ $f->duration_minutes ?? 60 }}"
                                                        data-city="{{ $f->vendor?->city ?? '' }}"
                                                        data-province="{{ $f->vendor?->province ?? '' }}"
                                                        data-latitude="{{ $f->vendor?->latitude ?? '' }}"
                                                        data-longitude="{{ $f->vendor?->longitude ?? '' }}">
                                                        {{ $f->name }} - {{ !empty($f->vendor?->name) ? $f->vendor->name : '-' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <input type="hidden" value="1" class="item-pax app-input">
                                    </div>
                                </div>

                                <input type="hidden" class="item-travel app-input" value="">
                                <input type="hidden" class="item-day app-input" value="{{ $day }}"><input
                                    type="hidden" class="item-order app-input" value="">
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-800 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-200">
                            You can add new items (Attraction, Activity, Inter Island Transfer, or F&amp;B) to this itinerary.
                        </div>
                        <button
                            type="button"
                            class="add-item btn-outline-sm min-h-[42px] h-[42px] px-4"
                        >
                            + Add Item
                        </button>
                    </div>
                    <div
                        class="mt-3 mb-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3 day-end-point-card dark:border-slate-600 dark:bg-slate-900/25">
                        <div class="space-y-2">
                            <div class="mb-1 flex items-start justify-between gap-3">
                                <label
                                    class="day-end-point-label flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                    <span class="mr-2 day-end-point-seq inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span>
                                    <span>Day {{ $day }} End Point</span>
                                </label>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">
                                    End Time: <span class="day-end-time-endpoint-text text-gray-800 dark:text-gray-100">-</span>
                                </p>
                            </div>
                            <div class="flex flex-col gap-2 md:flex-row">
                                <div class="md:w-1/4">
                                    <select name="daily_end_point_types[{{ $day }}]"
                                        class="day-end-point-type dark:border-gray-600 app-input">
                                        <option value="" @selected($endType === '')>{{ __('Not set') }}</option>
                                        <option value="hotel" @selected($endType === 'hotel')>{{ __('Hotel') }}</option>
                                        @if ($day === $durationDays)
                                            <option value="airport" @selected($endType === 'airport')>{{ __('Airport') }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="md:w-1/4">
                                    <select name="daily_end_point_items[{{ $day }}]"
                                        class="day-end-point-item day-end-point-select dark:border-gray-600 app-input">
                                        <option value="">{{ __('Select end point item') }}</option>
                                        @foreach ($hotelsSorted as $hotel)
                                            <option value="{{ $hotel->id }}" data-point-type="hotel"
                                                data-location="{{ $hotel->address ?? '' }}"
                                                data-city="{{ $hotel->city ?? '' }}"
                                                data-province="{{ $hotel->province ?? '' }}"
                                                data-destination="{{ $destinationNameById[$hotel->destination_id] ?? '' }}"
                                                data-latitude="{{ $hotel->latitude ?? '' }}"
                                                data-longitude="{{ $hotel->longitude ?? '' }}"
                                                @selected($endType === 'hotel' && $endItem === (string) $hotel->id)>
                                                {{ $hotel->name }}
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
                                <div class="day-end-room-wrap {{ $endType === 'hotel' ? '' : 'hidden' }} md:w-1/4">
                                    <select name="daily_end_point_room_ids[{{ $day }}]"
                                        class="day-end-room-select dark:border-gray-600 app-input"
                                        {{ $endType === 'hotel' ? '' : 'disabled' }}>
                                        <option value="">{{ __('Select room') }}</option>
                                        @foreach ($hotels as $hotel)
                                            @foreach ($hotel->rooms ?? collect() as $room)
                                                <option value="{{ $room->id }}"
                                                    data-hotel-id="{{ $hotel->id }}"
                                                    @selected($endType === 'hotel' && $endRoomId === (string) $room->id)>
                                                    {{ $room->rooms }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                                <div class="day-end-booking-wrap {{ $endType === 'hotel' ? '' : 'hidden' }} md:w-1/4">
                                    <select name="daily_end_hotel_booking_modes[{{ $day }}]"
                                        class="day-end-booking-mode dark:border-gray-600 app-input"
                                        {{ $endType === 'hotel' ? '' : 'disabled' }}>
                                        <option value="arranged" @selected($endHotelBookingMode === 'arranged')>{{ __('Hotel arranged by us') }}</option>
                                        <option value="self" @selected($endHotelBookingMode === 'self')>{{ __('Self-booked hotel') }}</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="daily_main_experience_types[{{ $day }}]"
                                class="day-main-experience-type app-input" value="{{ $mainExperienceType }}">
                            <input type="hidden" name="daily_main_experience_items[{{ $day }}]"
                                class="day-main-experience-item app-input" value="{{ $mainExperienceItem }}">
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
        @error('itinerary_island_transfer_items')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('itinerary_food_beverage_items')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('daily_main_experience_items.*')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('daily_start_hotel_booking_modes.*')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('daily_end_hotel_booking_modes.*')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </section>

    <section data-wizard-step="3" class="space-y-3 hidden">
        <div data-wizard-review-card="basic-info" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.wizard.include_exclude_title') }}</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('itinerary_form.wizard.include_exclude_hint') }}</p>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                        {{ ui_phrase('modules_itineraries_itinerary_include') }}
                    </label>
                    <textarea name="itinerary_include"
                        class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        rows="6" placeholder="{{ __('itinerary_form.wizard.include_placeholder') }}">{{ $itineraryInclude }}</textarea>
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">
                        {{ ui_phrase('modules_itineraries_itinerary_exclude') }}
                    </label>
                    <textarea name="itinerary_exclude"
                        class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        rows="6" placeholder="{{ __('itinerary_form.wizard.exclude_placeholder') }}">{{ $itineraryExclude }}</textarea>
                </div>
            </div>
        </div>
        @error('itinerary_include')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('itinerary_exclude')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </section>

    <section data-wizard-step="4" class="space-y-3 hidden">
        <div data-wizard-review-card="day-planner" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.review.title') }}</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('itinerary_form.review.subtitle') }}</p>
        </div>

        <div data-wizard-review-card="include-exclude" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.review.basic_info') }}</h4>
            <dl class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50">
                    <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('common_title') }}</dt>
                    <dd class="font-semibold text-gray-800 dark:text-gray-100" data-wizard-review-title>-</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('itinerary_form.review.order_number') }}</dt>
                    <dd class="font-semibold text-gray-800 dark:text-gray-100" data-wizard-review-order-number>-</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50">
                    <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('common_inquiry') }}</dt>
                    <dd class="font-semibold text-gray-800 dark:text-gray-100" data-wizard-review-inquiry>-</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50">
                    <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('common_destination') }}</dt>
                    <dd class="font-semibold text-gray-800 dark:text-gray-100" data-wizard-review-destination>-</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50">
                    <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('common_duration') }}</dt>
                    <dd class="font-semibold text-gray-800 dark:text-gray-100" data-wizard-review-duration>-</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('itinerary_form.review.selected_schedule_items') }}</dt>
                    <dd class="font-semibold text-gray-800 dark:text-gray-100" data-wizard-review-items>-</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/50 sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('common_description') }}</dt>
                    <dd class="whitespace-pre-line font-semibold text-gray-800 dark:text-gray-100" data-wizard-review-description>-</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.review.day_planner') }}</h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('itinerary_form.review.day_planner_hint') }}</p>
            <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2" data-wizard-review-days></div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.review.include_exclude') }}</h4>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 dark:border-emerald-700/60 dark:bg-emerald-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ ui_phrase('common_includes') }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-gray-800 dark:text-gray-100" data-wizard-review-include>-</p>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 dark:border-rose-700/60 dark:bg-rose-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">{{ ui_phrase('common_excludes') }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-gray-800 dark:text-gray-100" data-wizard-review-exclude>-</p>
                </div>
            </div>
        </div>
    </section>

    <div class="flex items-center gap-2">
        <button type="button" class="btn-secondary hidden" data-wizard-prev>{{ __('itinerary_form.buttons.back') }}</button>
        <button type="button" class="btn-primary" data-wizard-next>{{ __('itinerary_form.buttons.next') }}</button>
        <button type="submit" class="btn-primary hidden" data-wizard-submit>{{ $buttonLabel }}</button>
        <a href="{{ route('itineraries.index') }}"
             class="btn-secondary">{{ __('itinerary_form.buttons.cancel') }}</a>
    </div>
        </div>

        <aside class="space-y-4 xl:col-span-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                data-wizard-sidebar-panel="1"
                id="inquiry-detail-card">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.sidebar.inquiry_detail.title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('itinerary_form.sidebar.inquiry_detail.subtitle') }}</p>
                <div id="inquiry-detail-empty" class="mt-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
                    {{ __('itinerary_form.sidebar.inquiry_detail.empty') }}
                </div>
                <div id="inquiry-detail-content" class="mt-3 hidden space-y-2 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/50">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('common_inquiry') }}</p>
                            <p id="inq-detail-number" class="font-semibold text-gray-800 dark:text-gray-100">-</p>
                        </div>
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/50">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('common_customer') }}</p>
                            <p id="inq-detail-customer" class="font-semibold text-gray-800 dark:text-gray-100">-</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/50">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('common_status') }}</p>
                            <p id="inq-detail-status" class="font-semibold text-gray-800 dark:text-gray-100">-</p>
                        </div>
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/50">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('common_priority') }}</p>
                            <p id="inq-detail-priority" class="font-semibold text-gray-800 dark:text-gray-100">-</p>
                        </div>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/50">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('itinerary_form.sidebar.inquiry_detail.source_assigned_deadline') }}</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-100">
                            <span id="inq-detail-source">-</span> &bull;
                            <span id="inq-detail-assigned">-</span> &bull;
                            <span id="inq-detail-deadline">-</span>
                        </p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/50">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('common_created') }}</p>
                        <p id="inq-detail-created" class="font-semibold text-gray-800 dark:text-gray-100">-</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/50">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('common_notes') }}</p>
                        <div id="inq-detail-notes" class="text-gray-700 dark:text-gray-200">-</div>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900/50">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('itinerary_form.sidebar.inquiry_detail.reminder') }}</p>
                        <div id="inq-detail-reminder-note" class="text-gray-700 dark:text-gray-200">-</div>
                        <div id="inq-detail-reminder-reason" class="mt-1 text-gray-600 dark:text-gray-300">-</div>
                    </div>
                </div>
            </div>

            <div class="day-planner-map-sticky rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                data-wizard-sidebar-panel="2">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.sidebar.map_card.title') }}</h3>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('itinerary_form.sidebar.map_card.step') }}</span>
                </div>
                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">{{ __('itinerary_form.sidebar.map_card.subtitle') }}</p>
                <div id="itinerary-map" class="h-80 w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-900/40"></div>
                <div id="itinerary-map-legend" class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-300"></div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                data-wizard-sidebar-panel="3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.sidebar.include_exclude_tips.title') }}</h3>
                <ul class="mt-2 list-disc space-y-1 pl-4 text-xs text-gray-600 dark:text-gray-300">
                    <li>{{ __('itinerary_form.sidebar.include_exclude_tips.item_1') }}</li>
                    <li>{{ __('itinerary_form.sidebar.include_exclude_tips.item_2') }}</li>
                    <li>{{ __('itinerary_form.sidebar.include_exclude_tips.item_3') }}</li>
                </ul>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                data-wizard-sidebar-panel="4">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('itinerary_form.sidebar.final_check.title') }}</h3>
                <ul class="mt-2 list-disc space-y-1 pl-4 text-xs text-gray-600 dark:text-gray-300">
                    <li>{{ __('itinerary_form.sidebar.final_check.item_1') }}</li>
                    <li>{{ __('itinerary_form.sidebar.final_check.item_2') }}</li>
                    <li>{{ __('itinerary_form.sidebar.final_check.item_3') }}</li>
                    <li>{{ __('itinerary_form.sidebar.final_check.item_4') }}</li>
                </ul>
            </div>
        </aside>
    </div>
</div>

@once
    @push('styles')
        <style>
            .itinerary-wizard-steps {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.5rem;
            }
            .itinerary-wizard-step {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.4rem;
                border: 1px solid #d1d5db;
                border-radius: 9999px;
                background: #f8fafc;
                color: #64748b;
                font-size: 0.75rem;
                font-weight: 600;
                padding: 0.3rem 0.65rem;
                transition: all 120ms ease;
            }
            .itinerary-wizard-step.is-active {
                border-color: #2563eb;
                background: #eff6ff;
                color: #1d4ed8;
            }
            .itinerary-wizard-step__index {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.1rem;
                height: 1.1rem;
                border-radius: 9999px;
                border: 1px solid currentColor;
                font-size: 0.65rem;
                font-weight: 700;
            }
            .itinerary-wizard-step__label {
                line-height: 1.15;
                text-align: center;
            }
            @media (max-width: 639px) {
                .itinerary-wizard-step {
                    flex-direction: column;
                    gap: 0.2rem;
                    border-radius: 0.75rem;
                    padding: 0.42rem 0.3rem;
                    min-height: 3.15rem;
                }
                .itinerary-wizard-step__index {
                    width: 1.2rem;
                    height: 1.2rem;
                }
                .itinerary-wizard-step__label {
                    font-size: 0.66rem;
                    letter-spacing: 0.01em;
                }
            }
            .itinerary-wizard-progress-track {
                height: 0.35rem;
                border-radius: 9999px;
                background: #e2e8f0;
                overflow: hidden;
            }
            .itinerary-wizard-progress-fill {
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%);
                transition: width 180ms ease;
            }
            .itinerary-day-wizard-tabs .itinerary-day-wizard-tab {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                border: 1px solid #cbd5e1;
                border-radius: 9999px;
                background: #fff;
                color: #475569;
                font-size: 0.72rem;
                font-weight: 600;
                padding: 0.22rem 0.65rem;
                transition: all 120ms ease;
            }
            .itinerary-day-wizard-tabs .itinerary-day-wizard-tab.is-active {
                border-color: #2563eb;
                background: #eff6ff;
                color: #1d4ed8;
            }
            .itinerary-day-wizard-tab__state {
                display: inline-flex;
                align-items: center;
                border-radius: 9999px;
                padding: 0.08rem 0.45rem;
                font-size: 0.63rem;
                font-weight: 700;
                line-height: 1.2;
            }
            .itinerary-day-wizard-tab__state.is-complete {
                background: #dcfce7;
                color: #15803d;
            }
            .itinerary-day-wizard-tab__state.is-incomplete {
                background: #fef3c7;
                color: #b45309;
            }
            .day-title-label {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
            }
            .day-section[data-day-complete="1"] .day-title-label::after {
                content: attr(data-day-status-label);
                display: inline-flex;
                align-items: center;
                border-radius: 9999px;
                background: #dcfce7;
                color: #166534;
                font-size: 0.62rem;
                font-weight: 700;
                padding: 0.08rem 0.45rem;
            }
            .day-section[data-day-complete="0"] .day-title-label::after {
                content: attr(data-day-status-label);
                display: inline-flex;
                align-items: center;
                border-radius: 9999px;
                background: #fef3c7;
                color: #b45309;
                font-size: 0.62rem;
                font-weight: 700;
                padding: 0.08rem 0.45rem;
            }
            .day-section[data-day-hidden="1"] {
                display: none !important;
            }
            .dark .itinerary-wizard-step {
                border-color: #475569;
                background: #0f172a;
                color: #cbd5e1;
            }
            .dark .itinerary-wizard-step.is-active {
                border-color: #1d4ed8;
                background: rgba(30, 64, 175, 0.35);
                color: #bfdbfe;
            }
            .dark .itinerary-wizard-progress-track {
                background: #1e293b;
            }
            .dark .itinerary-day-wizard-tabs .itinerary-day-wizard-tab {
                border-color: #475569;
                background: #0f172a;
                color: #cbd5e1;
            }
            .dark .itinerary-day-wizard-tabs .itinerary-day-wizard-tab.is-active {
                border-color: #2563eb;
                background: rgba(30, 64, 175, 0.4);
                color: #bfdbfe;
            }
            .dark .itinerary-day-wizard-tab__state.is-complete {
                background: rgba(22, 163, 74, 0.32);
                color: #86efac;
            }
            .dark .itinerary-day-wizard-tab__state.is-incomplete {
                background: rgba(217, 119, 6, 0.3);
                color: #fcd34d;
            }
            .dark .day-section[data-day-complete="1"] .day-title-label::after {
                background: rgba(22, 163, 74, 0.32);
                color: #86efac;
            }
            .dark .day-section[data-day-complete="0"] .day-title-label::after {
                background: rgba(217, 119, 6, 0.3);
                color: #fcd34d;
            }
            .schedule-row.item-theme-attraction {
                border-color: #bfdbfe !important;
                background-color: rgba(59, 130, 246, 0.12) !important;
            }
            .schedule-row.item-theme-activity {
                border-color: #a7f3d0 !important;
                background-color: rgba(16, 185, 129, 0.12) !important;
            }
            .schedule-row.item-theme-transfer {
                border-color: #c4b5fd !important;
                background-color: rgba(139, 92, 246, 0.12) !important;
            }
            .schedule-row.item-theme-fnb {
                border-color: #fde68a !important;
                background-color: rgba(245, 158, 11, 0.12) !important;
            }
            .day-items {
                position: relative;
            }
            .schedule-row-ghost {
                opacity: 0.28 !important;
                border-style: dashed !important;
            }
            .schedule-row-chosen {
                cursor: grabbing !important;
            }
            .schedule-row-fallback {
                opacity: 0 !important;
                transform: none !important;
                box-shadow: none !important;
                border-color: transparent !important;
                background: transparent !important;
                pointer-events: none !important;
                z-index: 9999 !important;
            }
            .dark .schedule-row.item-theme-attraction {
                border-color: rgba(59, 130, 246, 0.45) !important;
                background-color: rgba(30, 64, 175, 0.25) !important;
            }
            .dark .schedule-row.item-theme-activity {
                border-color: rgba(16, 185, 129, 0.5) !important;
                background-color: rgba(6, 78, 59, 0.35) !important;
            }
            .dark .schedule-row.item-theme-transfer {
                border-color: rgba(139, 92, 246, 0.55) !important;
                background-color: rgba(76, 29, 149, 0.35) !important;
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
            .itinerary-map-marker--transfer {
                background: #8b5cf6;
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
            .required-asterisk {
                color: #dc2626;
                margin-left: 2px;
                font-weight: 700;
            }
            .input-with-left-affix {
                position: relative;
            }
            .input-with-left-affix .app-input {
                padding-left: 2.4rem !important;
            }
            .input-left-affix {
                pointer-events: none;
                position: absolute;
                left: 0.75rem;
                top: 50%;
                transform: translateY(-50%);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #64748b;
            }
            .dark .input-left-affix {
                color: #cbd5e1;
            }
            @media (min-width: 1280px) {
                .day-planner-map-sticky {
                    position: sticky;
                    top: 18px;
                    align-self: flex-start;
                }
            }
            [data-wizard-step="4"] [data-wizard-review-description] ul,
            [data-wizard-step="4"] [data-wizard-review-description] ol,
            [data-wizard-step="4"] [data-wizard-review-include] ul,
            [data-wizard-step="4"] [data-wizard-review-include] ol,
            [data-wizard-step="4"] [data-wizard-review-exclude] ul,
            [data-wizard-step="4"] [data-wizard-review-exclude] ol {
                list-style-position: inside;
                padding-left: 0.5rem;
                margin-left: 0;
            }
            [data-wizard-step="4"] [data-wizard-review-description] li::marker,
            [data-wizard-step="4"] [data-wizard-review-include] li::marker,
            [data-wizard-step="4"] [data-wizard-review-exclude] li::marker {
                color: #9ca3af;
            }
            .dark [data-wizard-step="4"] [data-wizard-review-description] li::marker,
            .dark [data-wizard-step="4"] [data-wizard-review-include] li::marker,
            .dark [data-wizard-step="4"] [data-wizard-review-exclude] li::marker {
                color: #94a3b8;
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
                const detailCard = document.getElementById('inquiry-detail-card');
                const detailEmpty = document.getElementById('inquiry-detail-empty');
                const detailContent = document.getElementById('inquiry-detail-content');
                const detailField = (id) => document.getElementById(id);
                const localizeIso = (iso) => {
                    const text = String(iso || '').trim();
                    if (!text) return '-';
                    const parsed = new Date(text);
                    if (Number.isNaN(parsed.getTime())) return '-';
                    const parts = new Intl.DateTimeFormat('en-CA', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                        timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    }).formatToParts(parsed);
                    const map = Object.fromEntries(parts.map((part) => [part.type, part.value]));
                    return `${map.year}-${map.month}-${map.day} (${map.hour}:${map.minute})`;
                };
                const setDetail = () => {
                    if (!inquirySelect || !detailEmpty || !detailContent) return;
                    const key = String(inquirySelect.value || '');
                    const detail = inquiryPreviewData[key] || null;
                    if (!detail) {
                        detailCard?.classList.remove('hidden');
                        detailEmpty.classList.remove('hidden');
                        detailContent.classList.add('hidden');
                        return;
                    }
                    detailCard?.classList.remove('hidden');
                    detailEmpty.classList.add('hidden');
                    detailContent.classList.remove('hidden');
                    detailField('inq-detail-number').textContent = detail.inquiry_number || '-';
                    detailField('inq-detail-customer').textContent = detail.customer || '-';
                    detailField('inq-detail-status').textContent = detail.status || '-';
                    detailField('inq-detail-priority').textContent = detail.priority || '-';
                    detailField('inq-detail-source').textContent = detail.source || '-';
                    detailField('inq-detail-assigned').textContent = detail.assigned_to || '-';
                    detailField('inq-detail-deadline').textContent = detail.deadline || '-';
                    detailField('inq-detail-created').textContent = localizeIso(detail.created_at_iso);
                    detailField('inq-detail-notes').innerHTML = detail.notes || '-';
                    detailField('inq-detail-reminder-note').innerHTML = detail.reminder_note || '-';
                    detailField('inq-detail-reminder-reason').innerHTML = detail.reminder_reason || '-';
                };
                inquirySelect?.addEventListener('change', setDetail);
                setDetail();

                const daySections = document.getElementById('day-sections');
                const durationInput = document.getElementById('duration-days');
                const durationNightsInput = document.getElementById('duration-nights');
                const MIN_DURATION_DAYS = 1;
                const MAX_DURATION_DAYS = 7;
                const MIN_DURATION_NIGHTS = 0;
                const MAX_DURATION_NIGHTS = 6;
                const clampDurationDays = (value) => {
                    const parsed = parseInt(String(value ?? ''), 10);
                    if (!Number.isFinite(parsed)) return MIN_DURATION_DAYS;
                    return Math.max(MIN_DURATION_DAYS, Math.min(MAX_DURATION_DAYS, parsed));
                };
                const deriveDurationNightsFromDays = (daysValue) => {
                    const days = clampDurationDays(daysValue);
                    return Math.max(MIN_DURATION_NIGHTS, Math.min(MAX_DURATION_NIGHTS, days - 1));
                };
                const clampDurationNights = (value, daysValue) => {
                    const parsed = parseInt(String(value ?? ''), 10);
                    const maxNightsByDays = deriveDurationNightsFromDays(daysValue);
                    if (!Number.isFinite(parsed)) {
                        return maxNightsByDays;
                    }
                    return Math.max(MIN_DURATION_NIGHTS, Math.min(maxNightsByDays, parsed));
                };
                const hotelStaysHidden = document.getElementById('hotel-stays-hidden');
                const mapEl = document.getElementById('itinerary-map');
                const mapDayTabsEl = document.getElementById('itinerary-map-day-tabs');
                const mapLegendEl = document.getElementById('itinerary-map-legend');
                const form = daySections?.closest('form');
                const activitySuggestionEndpoint = @json(route('itineraries.activity-suggestions'));
                const activityCreateEndpoint = @json(route('itineraries.activity-suggestions.store'));
                const attractionSuggestionEndpoint = @json(route('itineraries.tourist-attraction-suggestions'));
                const attractionCreateEndpoint = @json(route('itineraries.tourist-attraction-suggestions.store'));
                const fnbSuggestionEndpoint = @json(route('itineraries.food-beverage-suggestions'));
                const fnbCreateEndpoint = @json(route('itineraries.food-beverage-suggestions.store'));
                const csrfToken = form?.querySelector('input[name=\"_token\"]')?.value
                    || document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content')
                    || '';
                if (!daySections || !durationInput) return;
                const wizardRoot = document.querySelector('[data-itinerary-wizard]');
                const wizardStepPanels = wizardRoot ? [...wizardRoot.querySelectorAll('[data-wizard-step]')] : [];
                const wizardStepChips = wizardRoot ? [...wizardRoot.querySelectorAll('[data-wizard-step-chip]')] : [];
                const wizardSidebarPanels = wizardRoot ? [...wizardRoot.querySelectorAll('[data-wizard-sidebar-panel]')] : [];
                const wizardProgressFill = wizardRoot?.querySelector('[data-wizard-progress-fill]') || null;
                const wizardPrevButton = wizardRoot?.querySelector('[data-wizard-prev]') || null;
                const wizardNextButton = wizardRoot?.querySelector('[data-wizard-next]') || null;
                const wizardSubmitButton = wizardRoot?.querySelector('[data-wizard-submit]') || null;
                const dayWizardPrevButton = wizardRoot?.querySelector('[data-day-wizard-prev]') || null;
                const dayWizardNextButton = wizardRoot?.querySelector('[data-day-wizard-next]') || null;
                const dayWizardTabs = wizardRoot?.querySelector('#itinerary-day-wizard-tabs') || null;
                const itineraryTitleInput = wizardRoot?.querySelector('input[name="title"]') || null;
                const itineraryOrderNumberInput = wizardRoot?.querySelector('input[name="order_number"]') || null;
                const itineraryDestinationInput = wizardRoot?.querySelector('#itinerary-destination') || null;
                const itineraryDescriptionInput = wizardRoot?.querySelector('textarea[name="description"]') || null;
                const itineraryIncludeInput = wizardRoot?.querySelector('textarea[name="itinerary_include"]') || null;
                const itineraryExcludeInput = wizardRoot?.querySelector('textarea[name="itinerary_exclude"]') || null;
                const reviewTitleEl = wizardRoot?.querySelector('[data-wizard-review-title]') || null;
                const reviewOrderNumberEl = wizardRoot?.querySelector('[data-wizard-review-order-number]') || null;
                const reviewInquiryEl = wizardRoot?.querySelector('[data-wizard-review-inquiry]') || null;
                const reviewDestinationEl = wizardRoot?.querySelector('[data-wizard-review-destination]') || null;
                const reviewDurationEl = wizardRoot?.querySelector('[data-wizard-review-duration]') || null;
                const reviewItemsEl = wizardRoot?.querySelector('[data-wizard-review-items]') || null;
                const reviewDescriptionEl = wizardRoot?.querySelector('[data-wizard-review-description]') || null;
                const reviewDaysEl = wizardRoot?.querySelector('[data-wizard-review-days]') || null;
                const reviewIncludeEl = wizardRoot?.querySelector('[data-wizard-review-include]') || null;
                const reviewExcludeEl = wizardRoot?.querySelector('[data-wizard-review-exclude]') || null;
                const i18n = {
                    statusComplete: @json(__('itinerary_form.status.complete')),
                    statusIncomplete: @json(__('itinerary_form.status.incomplete')),
                    independentItinerary: @json(__('itinerary_form.review.independent_itinerary')),
                    day: @json(__('itinerary_form.labels.day')),
                    noPlannerData: @json(__('itinerary_form.review.no_planner_data')),
                    noScheduleItems: @json(__('itinerary_form.review.no_schedule_item_selected')),
                    tourTime: @json(__('itinerary_form.review.tour_time')),
                    transport: @json(__('itinerary_form.review.transport')),
                    startPoint: @json(__('itinerary_form.review.start_point')),
                    endPoint: @json(__('itinerary_form.review.end_point')),
                    previousDayEndpoint: @json(__('itinerary_form.points.previous_day_endpoint')),
                    airportNotSet: @json(__('itinerary_form.points.airport_not_set')),
                    hotelNotSet: @json(__('itinerary_form.points.hotel_not_set')),
                    selfBookedHotel: @json(__('itinerary_form.points.self_booked_hotel')),
                    selfBookedSuffix: @json(__('itinerary_form.points.self_booked_suffix')),
                    notSet: @json(__('itinerary_form.points.not_set')),
                    unnamedItem: @json(__('itinerary_form.review.unnamed_item')),
                    rowTypeAttraction: @json(__('itinerary_form.row_types.attraction')),
                    rowTypeActivity: @json(__('itinerary_form.row_types.activity')),
                    rowTypeTransfer: @json(__('itinerary_form.row_types.transfer')),
                    rowTypeFnb: @json(__('itinerary_form.row_types.fnb')),
                    selectedItemsPattern: @json(__('itinerary_form.patterns.selected_items')),
                    durationPattern: @json(__('itinerary_form.patterns.duration')),
                    travelMinutesPattern: @json(__('itinerary_form.labels.travel_minutes')),
                };
                let wizardStep = 1;
                let wizardActiveDay = 1;
                const WIZARD_STEP_MIN = 1;
                const WIZARD_STEP_MAX = 4;
                const clampWizardStep = (value) => {
                    const parsed = Number.parseInt(String(value ?? ''), 10);
                    if (!Number.isFinite(parsed)) return WIZARD_STEP_MIN;
                    return Math.max(WIZARD_STEP_MIN, Math.min(WIZARD_STEP_MAX, parsed));
                };
                const normalizeWizardDaySections = () =>
                    [...daySections.querySelectorAll('.day-section')]
                        .sort((a, b) => Number(a.dataset.day || '0') - Number(b.dataset.day || '0'));
                const clampWizardDay = (value) => {
                    const parsed = Number.parseInt(String(value ?? ''), 10);
                    const totalDays = clampDurationDays(durationInput.value || MIN_DURATION_DAYS);
                    if (!Number.isFinite(parsed)) return 1;
                    return Math.max(1, Math.min(totalDays, parsed));
                };
                const hasInputValue = (field) => String(field?.value || '').trim() !== '';
                const getRowSelectedValue = (row) => {
                    const type = String(row.querySelector('.item-type')?.value || 'attraction');
                    if (type === 'activity') return String(row.querySelector('.item-activity')?.value || '').trim();
                    if (type === 'transfer') return String(row.querySelector('.item-transfer')?.value || '').trim();
                    if (type === 'fnb') return String(row.querySelector('.item-fnb')?.value || '').trim();
                    return String(row.querySelector('.item-attraction')?.value || '').trim();
                };
                const evaluateDayCompletion = (section) => {
                    const day = Number.parseInt(String(section?.dataset?.day || ''), 10);
                    const dayNumber = Number.isFinite(day) ? day : 1;
                    const startTimeSet = hasInputValue(section?.querySelector('.day-start-time'));
                    const startType = normalizePointType(section?.querySelector('.day-start-point-type')?.value || '');
                    const startItemSet = hasInputValue(section?.querySelector('.day-start-point-item'));
                    const startRoomSet = hasInputValue(section?.querySelector('.day-start-room-select'));
                    const startBookingMode = String(section?.querySelector('.day-start-booking-mode')?.value || 'arranged');
                    const startSelfBooked = isSelfBookedHotelMode(startBookingMode);
                    let startConfigured = false;
                    if (startType === 'airport') {
                        startConfigured = startItemSet;
                    } else if (startType === 'hotel') {
                        startConfigured = startSelfBooked ? true : (startItemSet && startRoomSet);
                    } else if (startType === 'previous_day_end') {
                        if (dayNumber > 1) {
                            const prevSection = daySections.querySelector(`.day-section[data-day="${dayNumber - 1}"]`);
                            const prevEndType = normalizePointType(prevSection?.querySelector('.day-end-point-type')?.value || '');
                            const prevEndItemSet = hasInputValue(prevSection?.querySelector('.day-end-point-item'));
                            const prevEndRoomSet = hasInputValue(prevSection?.querySelector('.day-end-room-select'));
                            const prevEndMode = String(prevSection?.querySelector('.day-end-booking-mode')?.value || 'arranged');
                            const prevEndSelfBooked = isSelfBookedHotelMode(prevEndMode);
                            if (prevEndType === 'airport') {
                                startConfigured = prevEndItemSet;
                            } else if (prevEndType === 'hotel') {
                                startConfigured = prevEndSelfBooked ? true : (prevEndItemSet && prevEndRoomSet);
                            }
                        }
                    }
                    const endType = normalizePointType(section?.querySelector('.day-end-point-type')?.value || '');
                    const endItemSet = hasInputValue(section?.querySelector('.day-end-point-item'));
                    const endRoomSet = hasInputValue(section?.querySelector('.day-end-room-select'));
                    const endBookingMode = String(section?.querySelector('.day-end-booking-mode')?.value || 'arranged');
                    const endSelfBooked = isSelfBookedHotelMode(endBookingMode);
                    let endConfigured = false;
                    if (endType === 'airport') {
                        endConfigured = endItemSet;
                    } else if (endType === 'hotel') {
                        endConfigured = endSelfBooked ? true : (endItemSet && endRoomSet);
                    }
                    const activeRows = [...(section?.querySelectorAll('.schedule-row') || [])]
                        .filter((row) => !row.classList.contains('schedule-row-template') && !row.classList.contains('hidden'));
                    const hasPlannedItem = activeRows.some((row) => getRowSelectedValue(row) !== '');
                    return {
                        complete: startTimeSet && startConfigured && endConfigured && hasPlannedItem,
                    };
                };
                let dayCompletionStatusByDay = {};
                const applyDayCompletionIndicators = () => {
                    dayCompletionStatusByDay = {};
                    normalizeWizardDaySections().forEach((section) => {
                        const day = Number.parseInt(String(section.dataset.day || ''), 10);
                        const dayNumber = Number.isFinite(day) ? day : 1;
                        const status = evaluateDayCompletion(section);
                        section.dataset.dayComplete = status.complete ? '1' : '0';
                        section.dataset.dayStatusLabel = status.complete ? i18n.statusComplete : i18n.statusIncomplete;
                        dayCompletionStatusByDay[dayNumber] = status;
                    });
                    return dayCompletionStatusByDay;
                };
                const countSelectedScheduleItems = () => {
                    const rows = [...daySections.querySelectorAll('.schedule-row')]
                        .filter((row) => !row.classList.contains('schedule-row-template'));
                    let total = 0;
                    rows.forEach((row) => {
                        if (getRowSelectedValue(row) !== '') {
                            total += 1;
                        }
                    });
                    return total;
                };
                const reviewText = (value, fallback = '-') => {
                    const text = String(value ?? '').trim();
                    return text !== '' ? text : fallback;
                };
                const sanitizeRichHtml = (value) => {
                    const raw = String(value ?? '').trim();
                    if (raw === '') return '';
                    const container = document.createElement('div');
                    container.innerHTML = raw;
                    container.querySelectorAll('script, style, iframe, object, embed, form, meta, link').forEach((node) => node.remove());
                    container.querySelectorAll('*').forEach((node) => {
                        [...node.attributes].forEach((attr) => {
                            const attrName = String(attr.name || '').toLowerCase();
                            const attrValue = String(attr.value || '');
                            if (attrName.startsWith('on')) {
                                node.removeAttribute(attr.name);
                                return;
                            }
                            if ((attrName === 'href' || attrName === 'src') && /^\s*javascript:/i.test(attrValue)) {
                                node.removeAttribute(attr.name);
                                return;
                            }
                            if (attrName === 'style' && /(expression\s*\(|javascript:|url\s*\()/i.test(attrValue)) {
                                node.removeAttribute(attr.name);
                            }
                        });
                    });
                    return container.innerHTML.trim();
                };
                const reviewRichHtml = (value, fallback = '-') => {
                    const sanitized = sanitizeRichHtml(value);
                    if (sanitized !== '') return sanitized;
                    return `<span>${escapeHtml(fallback)}</span>`;
                };
                const escapeHtml = (value) => String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
                const reviewRowTypeLabel = (type) => {
                    if (type === 'activity') return i18n.rowTypeActivity;
                    if (type === 'transfer') return i18n.rowTypeTransfer;
                    if (type === 'fnb') return i18n.rowTypeFnb;
                    return i18n.rowTypeAttraction;
                };
                const reviewRowTypeBadgeClass = (type) => {
                    if (type === 'activity') return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700/60 dark:bg-emerald-900/20 dark:text-emerald-300';
                    if (type === 'transfer') return 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-700/60 dark:bg-purple-900/20 dark:text-purple-300';
                    if (type === 'fnb') return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700/60 dark:bg-amber-900/20 dark:text-amber-300';
                    return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700/60 dark:bg-blue-900/20 dark:text-blue-300';
                };
                const reviewPointLabel = (section, kind) => {
                    if (!section) return i18n.notSet;
                    const isStart = kind === 'start';
                    const typeSelect = section.querySelector(isStart ? '.day-start-point-type' : '.day-end-point-type');
                    const itemSelect = section.querySelector(isStart ? '.day-start-point-item' : '.day-end-point-item');
                    const bookingSelect = section.querySelector(isStart ? '.day-start-booking-mode' : '.day-end-booking-mode');
                    const pointType = normalizePointType(typeSelect?.value || '');
                    const pointItemText = String(itemSelect?.selectedOptions?.[0]?.textContent || '').trim();

                    if (pointType === 'previous_day_end') {
                        return i18n.previousDayEndpoint;
                    }
                    if (pointType === 'airport') {
                        return pointItemText !== '' ? pointItemText : i18n.airportNotSet;
                    }
                    if (pointType === 'hotel') {
                        if (isSelfBookedHotelMode(bookingSelect?.value || 'arranged')) {
                            if (isStart) {
                                return i18n.selfBookedHotel;
                            }
                            return pointItemText !== '' ? `${pointItemText} (${i18n.selfBookedSuffix})` : i18n.selfBookedHotel;
                        }
                        return pointItemText !== '' ? pointItemText : i18n.hotelNotSet;
                    }
                    return i18n.notSet;
                };
                const updateWizardReview = () => {
                    if (!wizardRoot) return;
                    const inquiryOptionText = String(inquirySelect?.selectedOptions?.[0]?.textContent || '').trim();
                    const destinationText = String(itineraryDestinationInput?.value || '').trim();
                    const titleText = String(itineraryTitleInput?.value || '').trim();
                    const orderNumberText = String(itineraryOrderNumberInput?.value || '').trim();
                    const descriptionText = String(itineraryDescriptionInput?.value || '').trim();
                    const includeText = String(itineraryIncludeInput?.value || '').trim();
                    const excludeText = String(itineraryExcludeInput?.value || '').trim();
                    const dayValue = clampDurationDays(durationInput.value || MIN_DURATION_DAYS);
                    const nightValue = clampDurationNights(durationNightsInput?.value || MIN_DURATION_NIGHTS, dayValue);
                    const selectedItemCount = countSelectedScheduleItems();
                    if (reviewTitleEl) reviewTitleEl.textContent = reviewText(titleText);
                    if (reviewOrderNumberEl) reviewOrderNumberEl.textContent = reviewText(orderNumberText);
                    if (reviewInquiryEl) reviewInquiryEl.textContent = inquiryOptionText !== '' ? inquiryOptionText : i18n.independentItinerary;
                    if (reviewDestinationEl) reviewDestinationEl.textContent = reviewText(destinationText);
                    if (reviewDurationEl) {
                        reviewDurationEl.textContent = i18n.durationPattern
                            .replace(':days', String(dayValue))
                            .replace(':nights', String(nightValue));
                    }
                    if (reviewItemsEl) {
                        reviewItemsEl.textContent = i18n.selectedItemsPattern.replace(':count', String(selectedItemCount));
                    }
                    if (reviewDescriptionEl) reviewDescriptionEl.innerHTML = reviewRichHtml(descriptionText);
                    if (reviewIncludeEl) reviewIncludeEl.innerHTML = reviewRichHtml(includeText);
                    if (reviewExcludeEl) reviewExcludeEl.innerHTML = reviewRichHtml(excludeText);

                    applyDayCompletionIndicators();
                    if (reviewDaysEl) {
                        const daySectionsSorted = normalizeWizardDaySections();
                        const dayCards = [];
                        for (let day = 1; day <= dayValue; day++) {
                            const section = daySectionsSorted.find((entry) => Number(entry.dataset.day || '0') === day) || null;
                            const isComplete = dayCompletionStatusByDay[day]?.complete === true;
                            const statusLabel = isComplete ? i18n.statusComplete : i18n.statusIncomplete;
                            const statusClass = isComplete
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700/60 dark:bg-emerald-900/20 dark:text-emerald-300'
                                : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700/60 dark:bg-amber-900/20 dark:text-amber-300';

                            if (!section) {
                                dayCards.push(`
                                    <article class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/30">
                                        <div class="flex items-center justify-between gap-2">
                                            <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-100">${i18n.day} ${day}</h5>
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold ${statusClass}">${statusLabel}</span>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">${i18n.noPlannerData}</p>
                                    </article>
                                `);
                                continue;
                            }

                            const startTimeText = reviewText(section.querySelector('.day-start-time')?.value, '--:--');
                            const endTimeText = reviewText(
                                section.querySelector('.day-end-time')?.value || section.querySelector('.day-end-time-endpoint-text')?.textContent,
                                '--:--',
                            );
                            const transportTexts = [...section.querySelectorAll('.day-transport-unit')]
                                .map((input) => String(input?.selectedOptions?.[0]?.textContent || '').trim())
                                .filter((text) => text !== '' && !text.toLowerCase().includes('select transport unit'));
                            const transportText = transportTexts.length > 0 ? transportTexts.join(', ') : i18n.notSet;
                            const startPointText = reviewPointLabel(section, 'start');
                            const endPointText = reviewPointLabel(section, 'end');
                            const selectedRows = [...section.querySelectorAll('.schedule-row')]
                                .filter((row) =>
                                    !row.classList.contains('schedule-row-template')
                                    && !row.classList.contains('hidden')
                                    && selected(row));
                            const scheduleItemsHtml = selectedRows.length
                                ? selectedRows.map((row) => {
                                    const selection = getRowSelection(row);
                                    const type = selection.type;
                                    const typeLabel = reviewRowTypeLabel(type);
                                    const typeClass = reviewRowTypeBadgeClass(type);
                                    const itemName = reviewText(selection.option?.textContent, i18n.unnamedItem);
                                    const itemStart = reviewText(row.querySelector('.item-start')?.value, '--:--');
                                    const itemEnd = reviewText(row.querySelector('.item-end')?.value, '--:--');
                                    const travelText = String(row.querySelector('.item-travel')?.value || '').trim();
                                    const extras = [];
                                    if (travelText !== '') extras.push(i18n.travelMinutesPattern.replace(':minutes', escapeHtml(travelText)));
                                    const extraMeta = extras.length ? `<p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">${extras.join(' | ')}</p>` : '';
                                    return `
                                        <li class="rounded-md border border-gray-200 bg-white px-2.5 py-2 dark:border-gray-700 dark:bg-gray-900/50">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-100">${escapeHtml(itemName)}</p>
                                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold ${typeClass}">${typeLabel}</span>
                                            </div>
                                            <p class="mt-1 text-[11px] text-gray-600 dark:text-gray-300">${escapeHtml(itemStart)} - ${escapeHtml(itemEnd)}</p>
                                            ${extraMeta}
                                        </li>
                                    `;
                                }).join('')
                                : `<p class="rounded-md border border-dashed border-gray-300 px-2.5 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">${i18n.noScheduleItems}</p>`;

                            const dayItemsHtml = `
                                <div class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-2 dark:border-slate-700 dark:bg-slate-900/40">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">${i18n.startPoint}</p>
                                    <p class="mt-1 text-xs font-medium text-gray-800 dark:text-gray-100">${escapeHtml(startPointText)}</p>
                                </div>
                                ${scheduleItemsHtml}
                                <div class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-2 dark:border-slate-700 dark:bg-slate-900/40">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">${i18n.endPoint}</p>
                                    <p class="mt-1 text-xs font-medium text-gray-800 dark:text-gray-100">${escapeHtml(endPointText)}</p>
                                </div>
                            `;

                            dayCards.push(`
                                <article class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/30">
                                    <div class="flex items-center justify-between gap-2">
                                        <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-100">${i18n.day} ${day}</h5>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold ${statusClass}">${statusLabel}</span>
                                    </div>
                                    <div class="mt-2 grid grid-cols-1 gap-1 text-xs text-gray-700 dark:text-gray-200">
                                        <p><span class="font-semibold">${i18n.tourTime}:</span> ${escapeHtml(startTimeText)} - ${escapeHtml(endTimeText)}</p>
                                        <p><span class="font-semibold">${i18n.transport}:</span> ${escapeHtml(transportText)}</p>
                                        <p><span class="font-semibold">${i18n.startPoint}:</span> ${escapeHtml(startPointText)}</p>
                                        <p><span class="font-semibold">${i18n.endPoint}:</span> ${escapeHtml(endPointText)}</p>
                                    </div>
                                    <div class="mt-2 space-y-1">${dayItemsHtml}</div>
                                </article>
                            `);
                        }
                        reviewDaysEl.innerHTML = dayCards.join('');

                    // hide review cards when their content is effectively empty
                    try {
                        const basicCardEl = wizardRoot.querySelector('[data-wizard-review-card="basic-info"]');
                        const dayCardEl = wizardRoot.querySelector('[data-wizard-review-card="day-planner"]');
                        const includeCardEl = wizardRoot.querySelector('[data-wizard-review-card="include-exclude"]');
                        const isBlank = (v) => String(v || '').trim() === '' || String(v || '').trim() === '-';
                        const inquiryHasValue = inquiryOptionText !== '' && inquiryOptionText !== i18n.independentItinerary;
                        const basicEmpty = isBlank(titleText) && isBlank(orderNumberText) && !inquiryHasValue && isBlank(destinationText) && isBlank(descriptionText) && selectedItemCount === 0;
                        if (basicCardEl) basicCardEl.classList.toggle('hidden', basicEmpty);
                        const daysEmpty = selectedItemCount === 0;
                        if (dayCardEl) dayCardEl.classList.toggle('hidden', daysEmpty);
                        const includeEmpty = isBlank(includeText) && isBlank(excludeText);
                        if (includeCardEl) includeCardEl.classList.toggle('hidden', includeEmpty);
                    } catch (__) {}
                    }
                };
                const renderDayWizardTabs = () => {
                    if (!dayWizardTabs) return;
                    applyDayCompletionIndicators();
                    const totalDays = clampDurationDays(durationInput.value || MIN_DURATION_DAYS);
                    let html = '';
                    for (let day = 1; day <= totalDays; day++) {
                        const activeClass = wizardActiveDay === day ? ' is-active' : '';
                        const isComplete = dayCompletionStatusByDay[day]?.complete === true;
                        const stateLabel = isComplete ? i18n.statusComplete : i18n.statusIncomplete;
                        const stateClass = isComplete ? 'is-complete' : 'is-incomplete';
                        html += `<button type="button" class="itinerary-day-wizard-tab${activeClass}" data-day-wizard-tab="${day}"><span>${i18n.day} ${day}</span><span class="itinerary-day-wizard-tab__state ${stateClass}">${stateLabel}</span></button>`;
                    }
                    dayWizardTabs.innerHTML = html;
                };
                const setWizardDay = (day) => {
                    if (!wizardRoot) return;
                    wizardActiveDay = clampWizardDay(day);
                    mapSelectedDay = wizardActiveDay;
                    const totalDays = clampDurationDays(durationInput.value || MIN_DURATION_DAYS);
                    const showDayNavButtons = totalDays > 1;
                    normalizeWizardDaySections().forEach((section) => {
                        const dayNumber = Number(section.dataset.day || '0');
                        section.dataset.dayHidden = wizardStep === 2 && dayNumber !== wizardActiveDay ? '1' : '0';
                    });
                    if (dayWizardPrevButton) {
                        dayWizardPrevButton.classList.toggle('hidden', !showDayNavButtons);
                        dayWizardPrevButton.disabled = wizardActiveDay <= 1;
                    }
                    if (dayWizardNextButton) {
                        dayWizardNextButton.classList.toggle('hidden', !showDayNavButtons);
                        dayWizardNextButton.disabled = wizardActiveDay >= totalDays;
                    }
                    renderDayWizardTabs();
                    setTimeout(() => {
                        try {
                            requestRenderItineraryMap();
                        } catch (_) {
                            // Ignore render queue call before map renderer is initialized.
                        }
                    }, 0);
                };
                const validateWizardStepOne = () => {
                    const panel = wizardRoot?.querySelector('[data-wizard-step="1"]');
                    if (!panel) return true;
                    const candidates = [...panel.querySelectorAll('input, select, textarea')]
                        .filter((field) => field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement);
                    for (const field of candidates) {
                        if (field.hasAttribute('required') && typeof field.reportValidity === 'function' && !field.checkValidity()) {
                            field.reportValidity();
                            field.focus();
                            return false;
                        }
                    }
                    return true;
                };
                const setWizardStep = (targetStep) => {
                    if (!wizardRoot) return;
                    wizardStep = clampWizardStep(targetStep);
                    wizardStepPanels.forEach((panel) => {
                        const panelStep = clampWizardStep(panel.dataset.wizardStep || WIZARD_STEP_MIN);
                        panel.classList.toggle('hidden', panelStep !== wizardStep);
                    });
                    wizardStepChips.forEach((chip) => {
                        const chipStep = clampWizardStep(chip.dataset.wizardStepChip || WIZARD_STEP_MIN);
                        chip.classList.toggle('is-active', chipStep === wizardStep);
                    });
                    wizardSidebarPanels.forEach((panel) => {
                        const panelStep = clampWizardStep(panel.dataset.wizardSidebarPanel || WIZARD_STEP_MIN);
                        panel.classList.toggle('hidden', panelStep !== wizardStep);
                    });
                    if (wizardProgressFill) {
                        wizardProgressFill.style.width = `${(wizardStep / WIZARD_STEP_MAX) * 100}%`;
                    }
                    wizardPrevButton?.classList.toggle('hidden', wizardStep === WIZARD_STEP_MIN);
                    wizardNextButton?.classList.toggle('hidden', wizardStep === WIZARD_STEP_MAX);
                    wizardSubmitButton?.classList.toggle('hidden', wizardStep !== WIZARD_STEP_MAX);
                    if (wizardSubmitButton) {
                        wizardSubmitButton.disabled = wizardStep !== WIZARD_STEP_MAX;
                    }
                    setWizardDay(wizardActiveDay);
                    updateWizardReview();
                };
                const syncWizardAfterDurationChange = () => {
                    if (!wizardRoot) return;
                    wizardActiveDay = clampWizardDay(wizardActiveDay);
                    setWizardDay(wizardActiveDay);
                    updateWizardReview();
                };
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
                const isSelfBookedHotelMode = (value) => {
                    const mode = String(value || '').trim().toLowerCase();
                    return mode === 'self' || mode === 'self-booked' || mode === 'self_booked' || mode === 'delft-booked' || mode === 'delft_booked';
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
                            type: 'transfer',
                            select: row.querySelector('.item-transfer')
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
                    const fallbackType = typeFieldValue === 'activity' || typeFieldValue === 'transfer' || typeFieldValue === 'fnb' ?
                        typeFieldValue :
                        (datasetTypeValue === 'activity' || datasetTypeValue === 'transfer' || datasetTypeValue === 'fnb' ? datasetTypeValue :
                            'attraction');
                    const preferredTypes = [];
                    [typeFieldValue, datasetTypeValue].forEach((type) => {
                        if ((type === 'attraction' || type === 'activity' || type === 'transfer' || type === 'fnb') &&
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
                const getAttractionElements = (row) => ({
                    wrap: row?.querySelector('.item-attraction-wrap') || null,
                    input: row?.querySelector('.item-attraction-search') || null,
                    dropdown: row?.querySelector('.item-attraction-dropdown') || null,
                    notice: row?.querySelector('.item-attraction-notice') || null,
                    select: row?.querySelector('.item-attraction') || null,
                });
                const setAttractionNotice = (row, message = '', tone = 'info') => {
                    const { notice, input } = getAttractionElements(row);
                    if (!notice) return;
                    if (String(message || '').trim() === '') {
                        notice.textContent = 'Example: Attraction Name, Region, Destination';
                        notice.classList.remove('text-rose-600', 'dark:text-rose-300', 'text-emerald-700', 'dark:text-emerald-300');
                        notice.classList.add('text-gray-500', 'dark:text-gray-400');
                        if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    notice.textContent = String(message);
                    notice.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-rose-600', 'dark:text-rose-300', 'text-emerald-700', 'dark:text-emerald-300');
                    if (tone === 'error') {
                        notice.classList.add('text-rose-600', 'dark:text-rose-300');
                        if (input) input.classList.add('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    if (tone === 'success') {
                        notice.classList.add('text-emerald-700', 'dark:text-emerald-300');
                        if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    notice.classList.add('text-gray-500', 'dark:text-gray-400');
                    if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                };
                const normalizeAttractionKeyword = (value) =>
                    String(value || '')
                        .toLowerCase()
                        .trim()
                        .replace(/\s+/g, ' ');
                const upsertAttractionOption = (select, item, preserveSelection = true) => {
                    if (!select || !item) return null;
                    const itemId = String(item.id ?? '').trim();
                    if (itemId === '') return null;
                    let option = [...select.options].find((candidate) => String(candidate.value) === itemId) || null;
                    if (!option) {
                        option = document.createElement('option');
                        option.value = itemId;
                        select.appendChild(option);
                    }
                    option.textContent = String(item.label || item.name || '').trim();
                    option.dataset.duration = String(Math.max(1, parseInt(String(item.ideal_visit_minutes || '120'), 10) || 120));
                    option.dataset.destination = String(item.destination || '');
                    option.dataset.location = String(item.location || '');
                    option.dataset.city = String(item.city || '');
                    option.dataset.province = String(item.province || '');
                    option.dataset.latitude = String(item.latitude ?? '');
                    option.dataset.longitude = String(item.longitude ?? '');
                    if (preserveSelection) {
                        select.value = itemId;
                    }
                    return option;
                };
                const syncAttractionInputFromSelect = (row) => {
                    const { input, select } = getAttractionElements(row);
                    if (!input || !select) return;
                    const selectedOption = select.selectedOptions?.[0] || null;
                    const selectedValue = String(select.value || '').trim();
                    if (selectedValue === '' || !selectedOption) {
                        input.value = '';
                        return;
                    }
                    input.value = String(selectedOption.textContent || '').trim();
                };
                const readAttractionSuggestionItems = (row) => {
                    try {
                        const parsed = JSON.parse(String(row?.dataset?.attractionSuggestionItems || '[]'));
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (_) {
                        return [];
                    }
                };
                const hideAttractionDropdown = (row) => {
                    const { dropdown } = getAttractionElements(row);
                    if (!dropdown) return;
                    dropdown.classList.add('hidden');
                    dropdown.innerHTML = '';
                    delete row.dataset.attractionActiveIndex;
                };
                const setAttractionDropdownActiveItem = (row, index) => {
                    const { dropdown } = getAttractionElements(row);
                    if (!dropdown) return;
                    const options = [...dropdown.querySelectorAll('[data-attraction-option]')];
                    if (!options.length) {
                        delete row.dataset.attractionActiveIndex;
                        return;
                    }
                    let safeIndex = Number.isFinite(index) ? index : -1;
                    if (safeIndex < 0) safeIndex = options.length - 1;
                    if (safeIndex >= options.length) safeIndex = 0;
                    row.dataset.attractionActiveIndex = String(safeIndex);
                    options.forEach((option, optionIndex) => {
                        const isActive = optionIndex === safeIndex;
                        option.classList.toggle('bg-blue-50', isActive);
                        option.classList.toggle('dark:bg-blue-900/30', isActive);
                        option.classList.toggle('text-blue-700', isActive);
                        option.classList.toggle('dark:text-blue-200', isActive);
                    });
                };
                const fetchAttractionSuggestions = async (keyword, region = '', limit = 12) => {
                    const trimmed = String(keyword || '').trim();
                    const trimmedRegion = String(region || '').trim();
                    const destinationKeyword = String(itineraryDestinationInput?.value || '').trim();
                    const params = new URLSearchParams({
                        q: trimmed,
                        destination: destinationKeyword,
                        region: trimmedRegion,
                        limit: String(limit),
                    });
                    const response = await fetch(`${attractionSuggestionEndpoint}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    });
                    if (!response.ok) {
                        throw new Error('Failed to fetch attraction suggestions');
                    }
                    const payload = await response.json();
                    return Array.isArray(payload?.data) ? payload.data : [];
                };
                const createAttractionFromKeyword = async (keyword) => {
                    const trimmed = String(keyword || '').trim();
                    if (trimmed === '') return null;
                    const response = await fetch(attractionCreateEndpoint, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            name: trimmed,
                            ideal_visit_minutes: 120,
                        }),
                    });
                    if (!response.ok) {
                        let message = 'Failed to create attraction';
                        try {
                            const errorPayload = await response.json();
                            const nameErrors = Array.isArray(errorPayload?.errors?.name) ? errorPayload.errors.name : [];
                            const firstNameError = String(nameErrors[0] || '').trim();
                            const fallbackMessage = String(errorPayload?.message || '').trim();
                            message = firstNameError || fallbackMessage || message;
                        } catch (_) {}
                        throw new Error(message);
                    }
                    const payload = await response.json();
                    return payload?.data || null;
                };
                const renderAttractionDropdown = (row, suggestions, keyword) => {
                    const { dropdown, select } = getAttractionElements(row);
                    if (!dropdown || !select) return;
                    const escapeHtml = (value) =>
                        String(value || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    const items = Array.isArray(suggestions) ? suggestions : [];
                    const normalizedKeyword = normalizeAttractionKeyword(keyword);
                    const hasExact = items.some((item) => normalizeAttractionKeyword(item?.name || item?.label || '') === normalizedKeyword);
                    let html = '';
                    items.forEach((item, index) => {
                        const label = String(item?.label || item?.name || '').trim();
                        if (!label) return;
                        html += `
                            <button type="button" data-attraction-option="existing" data-attraction-index="${index}" data-attraction-id="${String(item.id || '')}"
                                class="flex w-full items-start rounded-md px-2 py-1.5 text-left text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 dark:text-gray-200 dark:hover:bg-blue-900/30 dark:hover:text-blue-200">
                                <span class="truncate">${label}</span>
                            </button>
                        `;
                    });
                    if (normalizedKeyword !== '' && !hasExact) {
                        const safeKeyword = escapeHtml(String(keyword || '').trim());
                        html += `
                            <button type="button" data-attraction-option="create" data-attraction-keyword="${safeKeyword}"
                                class="mt-1 flex w-full items-start rounded-md border border-dashed border-emerald-300 px-2 py-1.5 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">
                                <span class="truncate">+ Add new attraction "${safeKeyword}"</span>
                            </button>
                        `;
                    }
                    dropdown.innerHTML = html;
                    if (html === '') {
                        hideAttractionDropdown(row);
                        return;
                    }
                    dropdown.classList.remove('hidden');
                    row.dataset.attractionSuggestionItems = JSON.stringify(items);
                    setAttractionDropdownActiveItem(row, 0);
                };
                const applyAttractionSelection = (row, item) => {
                    const { select, input } = getAttractionElements(row);
                    if (!select || !item) return;
                    const selectedOption = upsertAttractionOption(select, item, true);
                    const itemId = String(item.id ?? '').trim();
                    if (itemId !== '') {
                        select.value = itemId;
                        if (String(select.value || '').trim() !== itemId && selectedOption) {
                            selectedOption.selected = true;
                        }
                    }
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    if (input) {
                        input.value = String(selectedOption?.textContent || item?.label || item?.name || '').trim();
                    }
                    syncAttractionInputFromSelect(row);
                    setAttractionNotice(row, '', 'info');
                    hideAttractionDropdown(row);
                    syncRegionFromSelectedItem(row, true);
                    recalc();
                };
                const attractionAutocompleteBoundRows = new WeakSet();
                const bindAttractionAutocomplete = (row) => {
                    if (!row || attractionAutocompleteBoundRows.has(row)) return;
                    attractionAutocompleteBoundRows.add(row);
                    const { input, select, dropdown } = getAttractionElements(row);
                    if (!input || !select || !dropdown) return;
                    let debounceTimer = null;
                    let fetchToken = 0;
                    let creating = false;
                    syncAttractionInputFromSelect(row);
                    setAttractionNotice(row, '', 'info');

                    const runSearch = async (keyword) => {
                        const token = ++fetchToken;
                        const selectedRegion = String(row.querySelector('.item-region')?.value || '').trim();
                        try {
                            const suggestions = await fetchAttractionSuggestions(keyword, selectedRegion, 12);
                            if (token !== fetchToken) return;
                            renderAttractionDropdown(row, suggestions, keyword);
                        } catch (_) {
                            hideAttractionDropdown(row);
                        }
                    };

                    input.addEventListener('focus', () => {
                        setAttractionNotice(row, '', 'info');
                        runSearch(input.value || '');
                    });
                    input.addEventListener('input', () => {
                        select.value = '';
                        setAttractionNotice(row, '', 'info');
                        if (debounceTimer) clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => runSearch(input.value || ''), 250);
                    });
                    input.addEventListener('keydown', async (event) => {
                        const options = [...dropdown.querySelectorAll('[data-attraction-option]')];
                        if (!options.length) return;
                        const currentIndex = parseInt(String(row.dataset.attractionActiveIndex || '-1'), 10);
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            setAttractionDropdownActiveItem(row, currentIndex + 1);
                            return;
                        }
                        if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            setAttractionDropdownActiveItem(row, currentIndex - 1);
                            return;
                        }
                        if (event.key === 'Escape') {
                            hideAttractionDropdown(row);
                            return;
                        }
                        if (event.key !== 'Enter') return;
                        event.preventDefault();
                        const safeIndex = Number.isFinite(currentIndex) && currentIndex >= 0 ? currentIndex : 0;
                        const activeOption = options[safeIndex];
                        if (!activeOption) return;
                        if (activeOption.dataset.attractionOption === 'existing') {
                            const items = readAttractionSuggestionItems(row);
                            const itemIndex = parseInt(String(activeOption.dataset.attractionIndex || '-1'), 10);
                            const chosen = Number.isFinite(itemIndex) && itemIndex >= 0 ? items[itemIndex] : null;
                            if (chosen) applyAttractionSelection(row, chosen);
                            return;
                        }
                        if (activeOption.dataset.attractionOption === 'create' && !creating) {
                            creating = true;
                            try {
                                const created = await createAttractionFromKeyword(activeOption.dataset.attractionKeyword || input.value || '');
                                if (created) {
                                    applyAttractionSelection(row, created);
                                    setAttractionNotice(row, 'New attraction saved successfully.', 'success');
                                }
                            } catch (error) {
                                setAttractionNotice(row, String(error?.message || 'Invalid manual format.'), 'error');
                            } finally {
                                creating = false;
                            }
                        }
                    });

                    const handleAttractionDropdownPick = async (target) => {
                        if (!target) return;
                        if (target.dataset.attractionOption === 'existing') {
                            const items = readAttractionSuggestionItems(row);
                            const itemIndex = parseInt(String(target.dataset.attractionIndex || '-1'), 10);
                            const chosen = Number.isFinite(itemIndex) && itemIndex >= 0 ? items[itemIndex] : null;
                            if (chosen) applyAttractionSelection(row, chosen);
                            return;
                        }
                        if (target.dataset.attractionOption === 'create' && !creating) {
                            creating = true;
                            try {
                                const created = await createAttractionFromKeyword(target.dataset.attractionKeyword || input.value || '');
                                if (created) {
                                    applyAttractionSelection(row, created);
                                    setAttractionNotice(row, 'New attraction saved successfully.', 'success');
                                }
                            } catch (error) {
                                setAttractionNotice(row, String(error?.message || 'Invalid manual format.'), 'error');
                            } finally {
                                creating = false;
                            }
                        }
                    };
                    dropdown.addEventListener('pointerdown', async (event) => {
                        const target = event.target instanceof HTMLElement
                            ? event.target.closest('[data-attraction-option]')
                            : null;
                        if (!target) return;
                        event.preventDefault();
                        event.stopPropagation();
                        await handleAttractionDropdownPick(target);
                    });
                    input.addEventListener('blur', () => {
                        setTimeout(() => {
                            const currentInputValue = normalizeAttractionKeyword(input.value || '');
                            const selectedOption = select.selectedOptions?.[0] || null;
                            const selectedText = normalizeAttractionKeyword(selectedOption?.textContent || '');
                            if (String(select.value || '').trim() !== '') {
                                syncAttractionInputFromSelect(row);
                                setAttractionNotice(row, '', 'info');
                            } else if (currentInputValue === '') {
                                select.value = '';
                                syncAttractionInputFromSelect(row);
                                setAttractionNotice(row, '', 'info');
                            }
                            hideAttractionDropdown(row);
                        }, 120);
                    });

                    select.addEventListener('change', () => {
                        syncAttractionInputFromSelect(row);
                    });
                };
                const getActivityElements = (row) => ({
                    wrap: row?.querySelector('.item-activity-wrap') || null,
                    input: row?.querySelector('.item-activity-search') || null,
                    dropdown: row?.querySelector('.item-activity-dropdown') || null,
                    notice: row?.querySelector('.item-activity-notice') || null,
                    select: row?.querySelector('.item-activity') || null,
                });
                const setActivityNotice = (row, message = '', tone = 'info') => {
                    const { notice, input } = getActivityElements(row);
                    if (!notice) return;
                    if (String(message || '').trim() === '') {
                        notice.textContent = 'Example: Aktivity Name, Region, Vendor';
                        notice.classList.remove('text-rose-600', 'dark:text-rose-300', 'text-emerald-700', 'dark:text-emerald-300');
                        notice.classList.add('text-gray-500', 'dark:text-gray-400');
                        if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    notice.textContent = String(message);
                    notice.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-rose-600', 'dark:text-rose-300', 'text-emerald-700', 'dark:text-emerald-300');
                    if (tone === 'error') {
                        notice.classList.add('text-rose-600', 'dark:text-rose-300');
                        if (input) input.classList.add('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    if (tone === 'success') {
                        notice.classList.add('text-emerald-700', 'dark:text-emerald-300');
                        if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    notice.classList.add('text-gray-500', 'dark:text-gray-400');
                    if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                };
                const normalizeActivityKeyword = (value) =>
                    String(value || '')
                        .toLowerCase()
                        .trim()
                        .replace(/\s+/g, ' ');
                const upsertActivityOption = (select, activityItem, preserveSelection = true) => {
                    if (!select || !activityItem) return null;
                    const activityId = String(activityItem.id ?? '').trim();
                    if (activityId === '') return null;
                    let option = [...select.options].find((candidate) => String(candidate.value) === activityId) || null;
                    if (!option) {
                        option = document.createElement('option');
                        option.value = activityId;
                        select.appendChild(option);
                    }
                    option.textContent = String(activityItem.label || activityItem.name || '').trim();
                    option.dataset.duration = String(Math.max(1, parseInt(String(activityItem.duration_minutes || '60'), 10) || 60));
                    option.dataset.destination = String(activityItem.destination || '');
                    option.dataset.location = String(activityItem.location || '');
                    option.dataset.city = String(activityItem.city || '');
                    option.dataset.province = String(activityItem.province || '');
                    option.dataset.latitude = String(activityItem.latitude ?? '');
                    option.dataset.longitude = String(activityItem.longitude ?? '');
                    if (preserveSelection) {
                        select.value = activityId;
                    }
                    return option;
                };
                const syncActivityInputFromSelect = (row) => {
                    const { input, select } = getActivityElements(row);
                    if (!input || !select) return;
                    const selectedOption = select.selectedOptions?.[0] || null;
                    const selectedValue = String(select.value || '').trim();
                    if (selectedValue === '' || !selectedOption) {
                        input.value = '';
                        return;
                    }
                    input.value = String(selectedOption.textContent || '').trim();
                };
                const readActivitySuggestionItems = (row) => {
                    try {
                        const parsed = JSON.parse(String(row?.dataset?.activitySuggestionItems || '[]'));
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (_) {
                        return [];
                    }
                };
                const hideActivityDropdown = (row) => {
                    const { dropdown } = getActivityElements(row);
                    if (!dropdown) return;
                    dropdown.classList.add('hidden');
                    dropdown.innerHTML = '';
                    delete row.dataset.activityActiveIndex;
                };
                const setActivityDropdownActiveItem = (row, index) => {
                    const { dropdown } = getActivityElements(row);
                    if (!dropdown) return;
                    const options = [...dropdown.querySelectorAll('[data-activity-option]')];
                    if (!options.length) {
                        delete row.dataset.activityActiveIndex;
                        return;
                    }
                    let safeIndex = Number.isFinite(index) ? index : -1;
                    if (safeIndex < 0) safeIndex = options.length - 1;
                    if (safeIndex >= options.length) safeIndex = 0;
                    row.dataset.activityActiveIndex = String(safeIndex);
                    options.forEach((option, optionIndex) => {
                        const isActive = optionIndex === safeIndex;
                        option.classList.toggle('bg-blue-50', isActive);
                        option.classList.toggle('dark:bg-blue-900/30', isActive);
                        option.classList.toggle('text-blue-700', isActive);
                        option.classList.toggle('dark:text-blue-200', isActive);
                    });
                };
                const fetchActivitySuggestions = async (keyword, region = '', limit = 12) => {
                    const trimmed = String(keyword || '').trim();
                    const trimmedRegion = String(region || '').trim();
                    const destinationKeyword = String(itineraryDestinationInput?.value || '').trim();
                    const params = new URLSearchParams({
                        q: trimmed,
                        destination: destinationKeyword,
                        region: trimmedRegion,
                        limit: String(limit),
                    });
                    const response = await fetch(`${activitySuggestionEndpoint}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    });
                    if (!response.ok) {
                        throw new Error('Failed to fetch activity suggestions');
                    }
                    const payload = await response.json();
                    return Array.isArray(payload?.data) ? payload.data : [];
                };
                const createActivityFromKeyword = async (keyword) => {
                    const trimmed = String(keyword || '').trim();
                    if (trimmed === '') return null;
                    const response = await fetch(activityCreateEndpoint, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            name: trimmed,
                            duration_minutes: 60,
                        }),
                    });
                    if (!response.ok) {
                        let message = 'Failed to create activity';
                        try {
                            const errorPayload = await response.json();
                            const nameErrors = Array.isArray(errorPayload?.errors?.name) ? errorPayload.errors.name : [];
                            const firstNameError = String(nameErrors[0] || '').trim();
                            const fallbackMessage = String(errorPayload?.message || '').trim();
                            message = firstNameError || fallbackMessage || message;
                        } catch (_) {}
                        throw new Error(message);
                    }
                    const payload = await response.json();
                    return payload?.data || null;
                };
                const renderActivityDropdown = (row, suggestions, keyword) => {
                    const { dropdown, select } = getActivityElements(row);
                    if (!dropdown || !select) return;
                    const escapeHtml = (value) =>
                        String(value || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    const items = Array.isArray(suggestions) ? suggestions : [];
                    const normalizedKeyword = normalizeActivityKeyword(keyword);
                    const hasExact = items.some((item) => normalizeActivityKeyword(item?.name || item?.label || '') === normalizedKeyword);
                    let html = '';
                    items.forEach((item, index) => {
                        const label = String(item?.label || item?.name || '').trim();
                        if (!label) return;
                        html += `
                            <button type="button" data-activity-option="existing" data-activity-index="${index}" data-activity-id="${String(item.id || '')}"
                                class="flex w-full items-start rounded-md px-2 py-1.5 text-left text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 dark:text-gray-200 dark:hover:bg-blue-900/30 dark:hover:text-blue-200">
                                <span class="truncate">${label}</span>
                            </button>
                        `;
                    });
                    if (normalizedKeyword !== '' && !hasExact) {
                        const safeKeyword = escapeHtml(String(keyword || '').trim());
                        html += `
                            <button type="button" data-activity-option="create" data-activity-keyword="${safeKeyword}"
                                class="mt-1 flex w-full items-start rounded-md border border-dashed border-emerald-300 px-2 py-1.5 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">
                                <span class="truncate">+ Add new activity "${safeKeyword}"</span>
                            </button>
                        `;
                    }
                    dropdown.innerHTML = html;
                    if (html === '') {
                        hideActivityDropdown(row);
                        return;
                    }
                    dropdown.classList.remove('hidden');
                    row.dataset.activitySuggestionItems = JSON.stringify(items);
                    setActivityDropdownActiveItem(row, 0);
                };
                const applyActivitySelection = (row, activityItem) => {
                    const { select, input } = getActivityElements(row);
                    if (!select || !activityItem) return;
                    const selectedOption = upsertActivityOption(select, activityItem, true);
                    const activityId = String(activityItem.id ?? '').trim();
                    if (activityId !== '') {
                        select.value = activityId;
                        if (String(select.value || '').trim() !== activityId && selectedOption) {
                            selectedOption.selected = true;
                        }
                    }
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    if (input) {
                        input.value = String(selectedOption?.textContent || activityItem?.label || activityItem?.name || '').trim();
                    }
                    syncActivityInputFromSelect(row);
                    setActivityNotice(row, '', 'info');
                    hideActivityDropdown(row);
                    syncRegionFromSelectedItem(row, true);
                    recalc();
                };
                const activityAutocompleteBoundRows = new WeakSet();
                const bindActivityAutocomplete = (row) => {
                    if (!row || activityAutocompleteBoundRows.has(row)) return;
                    activityAutocompleteBoundRows.add(row);
                    const { input, select, dropdown } = getActivityElements(row);
                    if (!input || !select || !dropdown) return;
                    let debounceTimer = null;
                    let fetchToken = 0;
                    let creating = false;
                    syncActivityInputFromSelect(row);
                    setActivityNotice(row, '', 'info');

                    const runSearch = async (keyword) => {
                        const token = ++fetchToken;
                        const selectedRegion = String(row.querySelector('.item-region')?.value || '').trim();
                        try {
                            const suggestions = await fetchActivitySuggestions(keyword, selectedRegion, 12);
                            if (token !== fetchToken) return;
                            renderActivityDropdown(row, suggestions, keyword);
                        } catch (_) {
                            hideActivityDropdown(row);
                        }
                    };

                    input.addEventListener('focus', () => {
                        setActivityNotice(row, '', 'info');
                        runSearch(input.value || '');
                    });
                    input.addEventListener('input', () => {
                        select.value = '';
                        setActivityNotice(row, '', 'info');
                        if (debounceTimer) clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => runSearch(input.value || ''), 250);
                    });
                    input.addEventListener('keydown', async (event) => {
                        const options = [...dropdown.querySelectorAll('[data-activity-option]')];
                        if (!options.length) return;
                        const currentIndex = parseInt(String(row.dataset.activityActiveIndex || '-1'), 10);
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            setActivityDropdownActiveItem(row, currentIndex + 1);
                            return;
                        }
                        if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            setActivityDropdownActiveItem(row, currentIndex - 1);
                            return;
                        }
                        if (event.key === 'Escape') {
                            hideActivityDropdown(row);
                            return;
                        }
                        if (event.key !== 'Enter') return;
                        event.preventDefault();
                        const safeIndex = Number.isFinite(currentIndex) && currentIndex >= 0 ? currentIndex : 0;
                        const activeOption = options[safeIndex];
                        if (!activeOption) return;
                        if (activeOption.dataset.activityOption === 'existing') {
                            const items = readActivitySuggestionItems(row);
                            const itemIndex = parseInt(String(activeOption.dataset.activityIndex || '-1'), 10);
                            const chosen = Number.isFinite(itemIndex) && itemIndex >= 0 ? items[itemIndex] : null;
                            if (chosen) applyActivitySelection(row, chosen);
                            return;
                        }
                        if (activeOption.dataset.activityOption === 'create' && !creating) {
                            creating = true;
                            try {
                                const created = await createActivityFromKeyword(activeOption.dataset.activityKeyword || input.value || '');
                                if (created) {
                                    applyActivitySelection(row, created);
                                    setActivityNotice(row, 'New activity saved successfully.', 'success');
                                }
                            } catch (error) {
                                setActivityNotice(row, String(error?.message || 'Invalid manual format.'), 'error');
                            } finally {
                                creating = false;
                            }
                        }
                    });

                    const handleActivityDropdownPick = async (target) => {
                        if (!target) return;
                        if (target.dataset.activityOption === 'existing') {
                            const items = readActivitySuggestionItems(row);
                            const itemIndex = parseInt(String(target.dataset.activityIndex || '-1'), 10);
                            const chosen = Number.isFinite(itemIndex) && itemIndex >= 0 ? items[itemIndex] : null;
                            if (chosen) applyActivitySelection(row, chosen);
                            return;
                        }
                        if (target.dataset.activityOption === 'create' && !creating) {
                            creating = true;
                            try {
                                const created = await createActivityFromKeyword(target.dataset.activityKeyword || input.value || '');
                                if (created) {
                                    applyActivitySelection(row, created);
                                    setActivityNotice(row, 'New activity saved successfully.', 'success');
                                }
                            } catch (error) {
                                setActivityNotice(row, String(error?.message || 'Invalid manual format.'), 'error');
                            } finally {
                                creating = false;
                            }
                        }
                    };
                    dropdown.addEventListener('pointerdown', async (event) => {
                        const target = event.target instanceof HTMLElement
                            ? event.target.closest('[data-activity-option]')
                            : null;
                        if (!target) return;
                        event.preventDefault();
                        event.stopPropagation();
                        await handleActivityDropdownPick(target);
                    });
                    input.addEventListener('blur', () => {
                        setTimeout(() => {
                            const currentInputValue = normalizeActivityKeyword(input.value || '');
                            const selectedOption = select.selectedOptions?.[0] || null;
                            const selectedText = normalizeActivityKeyword(selectedOption?.textContent || '');
                            if (String(select.value || '').trim() !== '') {
                                syncActivityInputFromSelect(row);
                                setActivityNotice(row, '', 'info');
                            } else if (currentInputValue === '') {
                                select.value = '';
                                syncActivityInputFromSelect(row);
                                setActivityNotice(row, '', 'info');
                            }
                            hideActivityDropdown(row);
                        }, 120);
                    });

                    select.addEventListener('change', () => {
                        syncActivityInputFromSelect(row);
                    });
                };
                const getFnbElements = (row) => ({
                    wrap: row?.querySelector('.item-fnb-wrap') || null,
                    input: row?.querySelector('.item-fnb-search') || null,
                    dropdown: row?.querySelector('.item-fnb-dropdown') || null,
                    notice: row?.querySelector('.item-fnb-notice') || null,
                    select: row?.querySelector('.item-fnb') || null,
                });
                const setFnbNotice = (row, message = '', tone = 'info') => {
                    const { notice, input } = getFnbElements(row);
                    if (!notice) return;
                    if (String(message || '').trim() === '') {
                        notice.textContent = 'Example: F&B Name, Region, Vendor';
                        notice.classList.remove('text-rose-600', 'dark:text-rose-300', 'text-emerald-700', 'dark:text-emerald-300');
                        notice.classList.add('text-gray-500', 'dark:text-gray-400');
                        if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    notice.textContent = String(message);
                    notice.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-rose-600', 'dark:text-rose-300', 'text-emerald-700', 'dark:text-emerald-300');
                    if (tone === 'error') {
                        notice.classList.add('text-rose-600', 'dark:text-rose-300');
                        if (input) input.classList.add('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    if (tone === 'success') {
                        notice.classList.add('text-emerald-700', 'dark:text-emerald-300');
                        if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                        return;
                    }
                    notice.classList.add('text-gray-500', 'dark:text-gray-400');
                    if (input) input.classList.remove('border-rose-500', 'focus:border-rose-500', 'focus:ring-rose-500');
                };
                const normalizeFnbKeyword = (value) =>
                    String(value || '')
                        .toLowerCase()
                        .trim()
                        .replace(/\s+/g, ' ');
                const upsertFnbOption = (select, fnbItem, preserveSelection = true) => {
                    if (!select || !fnbItem) return null;
                    const fnbId = String(fnbItem.id ?? '').trim();
                    if (fnbId === '') return null;
                    let option = [...select.options].find((candidate) => String(candidate.value) === fnbId) || null;
                    if (!option) {
                        option = document.createElement('option');
                        option.value = fnbId;
                        select.appendChild(option);
                    }
                    option.textContent = String(fnbItem.label || fnbItem.name || '').trim();
                    option.dataset.duration = String(Math.max(1, parseInt(String(fnbItem.duration_minutes || '60'), 10) || 60));
                    option.dataset.destination = String(fnbItem.destination || '');
                    option.dataset.location = String(fnbItem.location || '');
                    option.dataset.city = String(fnbItem.city || '');
                    option.dataset.province = String(fnbItem.province || '');
                    option.dataset.latitude = String(fnbItem.latitude ?? '');
                    option.dataset.longitude = String(fnbItem.longitude ?? '');
                    if (preserveSelection) {
                        select.value = fnbId;
                    }
                    return option;
                };
                const syncFnbInputFromSelect = (row) => {
                    const { input, select } = getFnbElements(row);
                    if (!input || !select) return;
                    const selectedOption = select.selectedOptions?.[0] || null;
                    const selectedValue = String(select.value || '').trim();
                    if (selectedValue === '' || !selectedOption) {
                        input.value = '';
                        return;
                    }
                    input.value = String(selectedOption.textContent || '').trim();
                };
                const readFnbSuggestionItems = (row) => {
                    try {
                        const parsed = JSON.parse(String(row?.dataset?.fnbSuggestionItems || '[]'));
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (_) {
                        return [];
                    }
                };
                const hideFnbDropdown = (row) => {
                    const { dropdown } = getFnbElements(row);
                    if (!dropdown) return;
                    dropdown.classList.add('hidden');
                    dropdown.innerHTML = '';
                    delete row.dataset.fnbActiveIndex;
                };
                const setFnbDropdownActiveItem = (row, index) => {
                    const { dropdown } = getFnbElements(row);
                    if (!dropdown) return;
                    const options = [...dropdown.querySelectorAll('[data-fnb-option]')];
                    if (!options.length) {
                        delete row.dataset.fnbActiveIndex;
                        return;
                    }
                    let safeIndex = Number.isFinite(index) ? index : -1;
                    if (safeIndex < 0) safeIndex = options.length - 1;
                    if (safeIndex >= options.length) safeIndex = 0;
                    row.dataset.fnbActiveIndex = String(safeIndex);
                    options.forEach((option, optionIndex) => {
                        const isActive = optionIndex === safeIndex;
                        option.classList.toggle('bg-blue-50', isActive);
                        option.classList.toggle('dark:bg-blue-900/30', isActive);
                        option.classList.toggle('text-blue-700', isActive);
                        option.classList.toggle('dark:text-blue-200', isActive);
                    });
                };
                const fetchFnbSuggestions = async (keyword, region = '', limit = 12) => {
                    const trimmed = String(keyword || '').trim();
                    const trimmedRegion = String(region || '').trim();
                    const destinationKeyword = String(itineraryDestinationInput?.value || '').trim();
                    const params = new URLSearchParams({
                        q: trimmed,
                        destination: destinationKeyword,
                        region: trimmedRegion,
                        limit: String(limit),
                    });
                    const response = await fetch(`${fnbSuggestionEndpoint}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    });
                    if (!response.ok) {
                        throw new Error('Failed to fetch F&B suggestions');
                    }
                    const payload = await response.json();
                    return Array.isArray(payload?.data) ? payload.data : [];
                };
                const createFnbFromKeyword = async (keyword) => {
                    const trimmed = String(keyword || '').trim();
                    if (trimmed === '') return null;
                    const response = await fetch(fnbCreateEndpoint, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            name: trimmed,
                            duration_minutes: 60,
                        }),
                    });
                    if (!response.ok) {
                        let message = 'Failed to create F&B';
                        try {
                            const errorPayload = await response.json();
                            const nameErrors = Array.isArray(errorPayload?.errors?.name) ? errorPayload.errors.name : [];
                            const firstNameError = String(nameErrors[0] || '').trim();
                            const fallbackMessage = String(errorPayload?.message || '').trim();
                            message = firstNameError || fallbackMessage || message;
                        } catch (_) {}
                        throw new Error(message);
                    }
                    const payload = await response.json();
                    return payload?.data || null;
                };
                const renderFnbDropdown = (row, suggestions, keyword) => {
                    const { dropdown, select } = getFnbElements(row);
                    if (!dropdown || !select) return;
                    const escapeHtml = (value) =>
                        String(value || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    const items = Array.isArray(suggestions) ? suggestions : [];
                    const normalizedKeyword = normalizeFnbKeyword(keyword);
                    const hasExact = items.some((item) => normalizeFnbKeyword(item?.name || item?.label || '') === normalizedKeyword);
                    let html = '';
                    items.forEach((item, index) => {
                        const label = String(item?.label || item?.name || '').trim();
                        if (!label) return;
                        html += `
                            <button type="button" data-fnb-option="existing" data-fnb-index="${index}" data-fnb-id="${String(item.id || '')}"
                                class="flex w-full items-start rounded-md px-2 py-1.5 text-left text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 dark:text-gray-200 dark:hover:bg-blue-900/30 dark:hover:text-blue-200">
                                <span class="truncate">${label}</span>
                            </button>
                        `;
                    });
                    if (normalizedKeyword !== '' && !hasExact) {
                        const safeKeyword = escapeHtml(String(keyword || '').trim());
                        html += `
                            <button type="button" data-fnb-option="create" data-fnb-keyword="${safeKeyword}"
                                class="mt-1 flex w-full items-start rounded-md border border-dashed border-emerald-300 px-2 py-1.5 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">
                                <span class="truncate">+ Add new F&B "${safeKeyword}"</span>
                            </button>
                        `;
                    }
                    dropdown.innerHTML = html;
                    if (html === '') {
                        hideFnbDropdown(row);
                        return;
                    }
                    dropdown.classList.remove('hidden');
                    row.dataset.fnbSuggestionItems = JSON.stringify(items);
                    setFnbDropdownActiveItem(row, 0);
                };
                const applyFnbSelection = (row, fnbItem) => {
                    const { select, input } = getFnbElements(row);
                    if (!select || !fnbItem) return;
                    const selectedOption = upsertFnbOption(select, fnbItem, true);
                    const fnbId = String(fnbItem.id ?? '').trim();
                    if (fnbId !== '') {
                        select.value = fnbId;
                        if (String(select.value || '').trim() !== fnbId && selectedOption) {
                            selectedOption.selected = true;
                        }
                    }
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    if (input) {
                        input.value = String(selectedOption?.textContent || fnbItem?.label || fnbItem?.name || '').trim();
                    }
                    syncFnbInputFromSelect(row);
                    setFnbNotice(row, '', 'info');
                    hideFnbDropdown(row);
                    syncRegionFromSelectedItem(row, true);
                    recalc();
                };
                const fnbAutocompleteBoundRows = new WeakSet();
                const bindFnbAutocomplete = (row) => {
                    if (!row || fnbAutocompleteBoundRows.has(row)) return;
                    fnbAutocompleteBoundRows.add(row);
                    const { input, select, dropdown } = getFnbElements(row);
                    if (!input || !select || !dropdown) return;
                    let debounceTimer = null;
                    let fetchToken = 0;
                    let creating = false;
                    syncFnbInputFromSelect(row);
                    setFnbNotice(row, '', 'info');

                    const runSearch = async (keyword) => {
                        const token = ++fetchToken;
                        const selectedRegion = String(row.querySelector('.item-region')?.value || '').trim();
                        try {
                            const suggestions = await fetchFnbSuggestions(keyword, selectedRegion, 12);
                            if (token !== fetchToken) return;
                            renderFnbDropdown(row, suggestions, keyword);
                        } catch (_) {
                            hideFnbDropdown(row);
                        }
                    };

                    input.addEventListener('focus', () => {
                        setFnbNotice(row, '', 'info');
                        runSearch(input.value || '');
                    });
                    input.addEventListener('input', () => {
                        select.value = '';
                        setFnbNotice(row, '', 'info');
                        if (debounceTimer) clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => runSearch(input.value || ''), 250);
                    });
                    input.addEventListener('keydown', async (event) => {
                        const options = [...dropdown.querySelectorAll('[data-fnb-option]')];
                        if (!options.length) return;
                        const currentIndex = parseInt(String(row.dataset.fnbActiveIndex || '-1'), 10);
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            setFnbDropdownActiveItem(row, currentIndex + 1);
                            return;
                        }
                        if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            setFnbDropdownActiveItem(row, currentIndex - 1);
                            return;
                        }
                        if (event.key === 'Escape') {
                            hideFnbDropdown(row);
                            return;
                        }
                        if (event.key !== 'Enter') return;
                        event.preventDefault();
                        const safeIndex = Number.isFinite(currentIndex) && currentIndex >= 0 ? currentIndex : 0;
                        const activeOption = options[safeIndex];
                        if (!activeOption) return;
                        if (activeOption.dataset.fnbOption === 'existing') {
                            const items = readFnbSuggestionItems(row);
                            const itemIndex = parseInt(String(activeOption.dataset.fnbIndex || '-1'), 10);
                            const chosen = Number.isFinite(itemIndex) && itemIndex >= 0 ? items[itemIndex] : null;
                            if (chosen) applyFnbSelection(row, chosen);
                            return;
                        }
                        if (activeOption.dataset.fnbOption === 'create' && !creating) {
                            creating = true;
                            try {
                                const created = await createFnbFromKeyword(activeOption.dataset.fnbKeyword || input.value || '');
                                if (created) {
                                    applyFnbSelection(row, created);
                                    setFnbNotice(row, 'New F&B saved successfully.', 'success');
                                }
                            } catch (error) {
                                setFnbNotice(row, String(error?.message || 'Invalid manual format.'), 'error');
                            } finally {
                                creating = false;
                            }
                        }
                    });

                    const handleFnbDropdownPick = async (target) => {
                        if (!target) return;
                        if (target.dataset.fnbOption === 'existing') {
                            const items = readFnbSuggestionItems(row);
                            const itemIndex = parseInt(String(target.dataset.fnbIndex || '-1'), 10);
                            const chosen = Number.isFinite(itemIndex) && itemIndex >= 0 ? items[itemIndex] : null;
                            if (chosen) applyFnbSelection(row, chosen);
                            return;
                        }
                        if (target.dataset.fnbOption === 'create' && !creating) {
                            creating = true;
                            try {
                                const created = await createFnbFromKeyword(target.dataset.fnbKeyword || input.value || '');
                                if (created) {
                                    applyFnbSelection(row, created);
                                    setFnbNotice(row, 'New F&B saved successfully.', 'success');
                                }
                            } catch (error) {
                                setFnbNotice(row, String(error?.message || 'Invalid manual format.'), 'error');
                            } finally {
                                creating = false;
                            }
                        }
                    };
                    dropdown.addEventListener('pointerdown', async (event) => {
                        const target = event.target instanceof HTMLElement
                            ? event.target.closest('[data-fnb-option]')
                            : null;
                        if (!target) return;
                        event.preventDefault();
                        event.stopPropagation();
                        await handleFnbDropdownPick(target);
                    });
                    input.addEventListener('blur', () => {
                        setTimeout(() => {
                            const currentInputValue = normalizeFnbKeyword(input.value || '');
                            if (String(select.value || '').trim() !== '') {
                                syncFnbInputFromSelect(row);
                                setFnbNotice(row, '', 'info');
                            } else if (currentInputValue === '') {
                                select.value = '';
                                syncFnbInputFromSelect(row);
                                setFnbNotice(row, '', 'info');
                            }
                            hideFnbDropdown(row);
                        }, 120);
                    });
                    select.addEventListener('change', () => {
                        syncFnbInputFromSelect(row);
                    });
                };
                const toggleType = (r, t, reset = true) => {
                    const type = t === 'activity' || t === 'transfer' || t === 'fnb' ? t : 'attraction';
                    r.dataset.itemType = type;
                    r.querySelector('.item-type').value = type;
                    const a = r.querySelector('.item-attraction');
                    const aWrap = r.querySelector('.item-attraction-wrap');
                    const aSearch = r.querySelector('.item-attraction-search');
                    const aDropdown = r.querySelector('.item-attraction-dropdown');
                    const b = r.querySelector('.item-activity');
                    const bWrap = r.querySelector('.item-activity-wrap');
                    const bSearch = r.querySelector('.item-activity-search');
                    const bDropdown = r.querySelector('.item-activity-dropdown');
                    const tr = r.querySelector('.item-transfer');
                    const f = r.querySelector('.item-fnb');
                    const fWrap = r.querySelector('.item-fnb-wrap');
                    const fSearch = r.querySelector('.item-fnb-search');
                    const fDropdown = r.querySelector('.item-fnb-dropdown');
                    if (type === 'activity') {
                        aWrap?.classList.add('hidden');
                        aDropdown?.classList.add('hidden');
                        bWrap?.classList.remove('hidden');
                        tr.classList.add('hidden');
                        fWrap?.classList.add('hidden');
                        fDropdown?.classList.add('hidden');
                        f.classList.add('hidden');
                        if (reset) {
                            a.value = '';
                            if (aSearch) aSearch.value = '';
                            tr.value = '';
                            f.value = '';
                            if (fSearch) fSearch.value = '';
                        }
                        setAttractionNotice(r, '', 'info');
                        setFnbNotice(r, '', 'info');
                        syncActivityInputFromSelect(r);
                    } else if (type === 'transfer') {
                        aWrap?.classList.add('hidden');
                        aDropdown?.classList.add('hidden');
                        bWrap?.classList.add('hidden');
                        bDropdown?.classList.add('hidden');
                        tr.classList.remove('hidden');
                        fWrap?.classList.add('hidden');
                        fDropdown?.classList.add('hidden');
                        f.classList.add('hidden');
                        if (reset) {
                            a.value = '';
                            if (aSearch) aSearch.value = '';
                            b.value = '';
                            if (bSearch) bSearch.value = '';
                            f.value = '';
                            if (fSearch) fSearch.value = '';
                        }
                        setAttractionNotice(r, '', 'info');
                        setFnbNotice(r, '', 'info');
                    } else if (type === 'fnb') {
                        aWrap?.classList.add('hidden');
                        aDropdown?.classList.add('hidden');
                        bWrap?.classList.add('hidden');
                        bDropdown?.classList.add('hidden');
                        tr.classList.add('hidden');
                        fWrap?.classList.remove('hidden');
                        f.classList.add('hidden');
                        if (reset) {
                            a.value = '';
                            if (aSearch) aSearch.value = '';
                            b.value = '';
                            if (bSearch) bSearch.value = '';
                            tr.value = '';
                            if (fSearch) fSearch.value = '';
                        }
                        setAttractionNotice(r, '', 'info');
                        syncFnbInputFromSelect(r);
                    } else {
                        aWrap?.classList.remove('hidden');
                        bWrap?.classList.add('hidden');
                        bDropdown?.classList.add('hidden');
                        tr.classList.add('hidden');
                        fWrap?.classList.add('hidden');
                        fDropdown?.classList.add('hidden');
                        f.classList.add('hidden');
                        if (reset) {
                            b.value = '';
                            if (bSearch) bSearch.value = '';
                            tr.value = '';
                            f.value = '';
                            if (fSearch) fSearch.value = '';
                        }
                        syncAttractionInputFromSelect(r);
                        setFnbNotice(r, '', 'info');
                    }
                };
                const markRegionManualState = (row, isManual) => {
                    if (!row) return;
                    row.dataset.regionManual = isManual ? '1' : '0';
                };
                const getSelectedItemCity = (row) => {
                    const select = activeSelect(row);
                    if (!select) return '';
                    const option = select.selectedOptions?.[0];
                    return String(option?.dataset?.city || '').trim();
                };
                const getSelectedItemArea = (row) => {
                    const select = activeSelect(row);
                    const option = select?.selectedOptions?.[0];
                    return {
                        city: String(option?.dataset?.city || '').trim().toLowerCase(),
                        province: String(option?.dataset?.province || '').trim().toLowerCase(),
                    };
                };
                const syncRegionFromSelectedItem = (row, force = false) => {
                    if (!row) return;
                    const regionSelect = row.querySelector('.item-region');
                    if (!regionSelect) return;
                    const city = getSelectedItemCity(row);
                    if (!city) return;
                    const hasOption = [...regionSelect.options].some((opt) => String(opt.value) === city);
                    if (!hasOption) return;
                    const isManual = row.dataset.regionManual === '1';
                    const currentRegion = String(regionSelect.value || '').trim();
                    if (!force && isManual && currentRegion !== '') return;
                    if (currentRegion !== city) {
                        regionSelect.value = city;
                    }
                };
                let itineraryDataLayer = null;
                let mapBusy = false;
                let renderPendingAfterMove = false;
                let hardResetInProgress = false;
                let mapRenderSeq = 0;
                let activeRouteFetchController = null;
                let activeAutoTravelFetchController = null;
                let mapSelectedDay = clampWizardDay(wizardActiveDay);
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
                    if (type === 'activity' || type === 'transfer' || type === 'fnb' || type === 'hotel' || type === 'airport' || type === 'attraction') {
                        return type;
                    }
                    return 'attraction';
                };
                const markerTypeClass = (type) => {
                    const normalized = normalizeMapPointType(type);
                    if (normalized === 'activity') return 'itinerary-map-marker--activity';
                    if (normalized === 'transfer') return 'itinerary-map-marker--transfer';
                    if (normalized === 'fnb') return 'itinerary-map-marker--fnb';
                    if (normalized === 'hotel') return 'itinerary-map-marker--hotel';
                    if (normalized === 'airport') return 'itinerary-map-marker--airport';
                    return 'itinerary-map-marker--attraction';
                };
                const markerTypeIcon = (type) => {
                    const normalized = normalizeMapPointType(type);
                    if (normalized === 'activity') return 'fa-solid fa-person-hiking';
                    if (normalized === 'transfer') return 'fa-solid fa-ship';
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
                const parseTransferRouteCoords = (option) => {
                    if (!option) return null;
                    const raw = String(option.dataset.routeGeojson || '').trim();
                    const decodeHtmlEntities = (value) => {
                        const source = String(value || '');
                        if (!source || !/[&][a-z#0-9]+;/i.test(source)) return source;
                        const textarea = document.createElement('textarea');
                        textarea.innerHTML = source;
                        return textarea.value || source;
                    };
                    const rawDecoded = decodeHtmlEntities(raw);
                    let coordinates = [];
                    const extractLineCoordinates = (value) => {
                        if (!value) return [];
                        if (Array.isArray(value)) {
                            if (value.length && Array.isArray(value[0])) return value;
                            return [];
                        }
                        if (typeof value === 'string') {
                            const trimmed = value.trim();
                            if (!trimmed) return [];
                            try {
                                return extractLineCoordinates(JSON.parse(trimmed));
                            } catch (_) {
                                return [];
                            }
                        }
                        if (typeof value !== 'object') return [];
                        if (value.type === 'LineString' && Array.isArray(value.coordinates)) {
                            return value.coordinates;
                        }
                        if (value.type === 'LineString' && typeof value.coordinates === 'string') {
                            try {
                                const parsedCoordinates = JSON.parse(value.coordinates);
                                return Array.isArray(parsedCoordinates) ? parsedCoordinates : [];
                            } catch (_) {
                                return [];
                            }
                        }
                        if (value.type === 'Feature' && value.geometry) {
                            return extractLineCoordinates(value.geometry);
                        }
                        if (value.type === 'FeatureCollection' && Array.isArray(value.features)) {
                            for (const feature of value.features) {
                                const found = extractLineCoordinates(feature);
                                if (Array.isArray(found) && found.length >= 2) return found;
                            }
                        }
                        return [];
                    };
                    if (rawDecoded !== '' && rawDecoded !== 'null') {
                        try {
                            coordinates = extractLineCoordinates(JSON.parse(rawDecoded));
                        } catch (_) {
                            coordinates = extractLineCoordinates(rawDecoded);
                        }
                    }
                    const routePoints = [];
                    coordinates.forEach((coord) => {
                        if (!Array.isArray(coord) || coord.length < 2) return;
                        const normalized = normalizeLatLngPair(Number(coord[1]), Number(coord[0]));
                        if (!normalized) return;
                        routePoints.push(normalized);
                    });
                    if (routePoints.length < 2) return null;
                    return routePoints;
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
                const orientSegmentCoords = (segmentCoords, fromPoint, toPoint) => {
                    if (!Array.isArray(segmentCoords) || segmentCoords.length < 2 || !fromPoint || !toPoint) {
                        return Array.isArray(segmentCoords) ? segmentCoords : [];
                    }
                    const first = segmentCoords[0];
                    const last = segmentCoords[segmentCoords.length - 1];
                    if (!first || !last) return segmentCoords;
                    const distanceSq = (a, b) => {
                        const dLat = Number(a.lat) - Number(b.lat);
                        const dLng = Number(a.lng) - Number(b.lng);
                        return (dLat * dLat) + (dLng * dLng);
                    };
                    const normalScore = distanceSq(first, fromPoint) + distanceSq(last, toPoint);
                    const reversedScore = distanceSq(last, fromPoint) + distanceSq(first, toPoint);
                    if (reversedScore + 1e-12 < normalScore) {
                        return segmentCoords.slice().reverse();
                    }
                    return segmentCoords;
                };
                const routeDurationCache = new Map();
                const roundUpTravelMinutesToStep = (minutes, step = 30) => {
                    const safeStep = Number.isFinite(step) && step > 0 ? step : 30;
                    const safeMinutes = Number.isFinite(minutes) ? Math.max(0, minutes) : 0;
                    if (safeMinutes <= 0) return 0;
                    return Math.ceil(safeMinutes / safeStep) * safeStep;
                };
                const buildRouteDurationCacheKey = (latLngPoints) => {
                    if (!Array.isArray(latLngPoints) || latLngPoints.length < 2) return '';
                    const first = latLngPoints[0];
                    const second = latLngPoints[1];
                    if (!first || !second) return '';
                    return [
                        Number(first.lat).toFixed(6),
                        Number(first.lng).toFixed(6),
                        Number(second.lat).toFixed(6),
                        Number(second.lng).toFixed(6),
                    ].join('|');
                };
                const fetchRoadRouteData = async (latLngPoints, signal) => {
                    if (!Array.isArray(latLngPoints) || latLngPoints.length < 2) return null;
                    const cacheKey = buildRouteDurationCacheKey(latLngPoints);
                    if (cacheKey && routeDurationCache.has(cacheKey)) {
                        const cached = routeDurationCache.get(cacheKey);
                        if (cached) {
                            return {
                                routePoints: Array.isArray(cached.routePoints) ? cached.routePoints.slice() : null,
                                durationMinutes: Number.isFinite(cached.durationMinutes) ? cached.durationMinutes : null,
                            };
                        }
                    }
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
                    const primaryRoute = payload?.routes?.[0];
                    const coordinates = primaryRoute?.geometry?.coordinates;
                    if (!Array.isArray(coordinates) || coordinates.length < 2) return null;
                    const routePoints = [];
                    coordinates.forEach((coord) => {
                        if (!Array.isArray(coord) || coord.length < 2) return;
                        const latLng = toLeafletLatLng(coord[1], coord[0]);
                        if (latLng) routePoints.push(latLng);
                    });
                    if (routePoints.length < 2) return null;
                    const durationSeconds = Number(primaryRoute?.duration ?? Number.NaN);
                    const durationMinutes = Number.isFinite(durationSeconds)
                        ? roundUpTravelMinutesToStep(durationSeconds / 60, 30)
                        : null;
                    const routeData = {
                        routePoints,
                        durationMinutes,
                    };
                    if (cacheKey) {
                        routeDurationCache.set(cacheKey, routeData);
                    }
                    return routeData;
                };
                const fetchRoadRouteGeometry = async (latLngPoints, signal) => {
                    const routeData = await fetchRoadRouteData(latLngPoints, signal);
                    return Array.isArray(routeData?.routePoints) ? routeData.routePoints : null;
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
                            mapOrder: 0,
                            type: startPoint.type || normalizeMapPointType(startType),
                            lat: startPoint.lat,
                            lng: startPoint.lng,
                            name: startPoint.name,
                            travelMinutes: Math.max(0, parseInt(section.querySelector('.day-start-travel')?.value || '0', 10) || 0),
                            travelInputEl: section.querySelector('.day-start-travel') || null,
                        });
                    } else if (startType === 'previous_day_end' && startPoint) {
                        points.push({
                            day,
                            role: 'start',
                            mapOrder: 0,
                            type: startPoint.type || 'hotel',
                            lat: startPoint.lat,
                            lng: startPoint.lng,
                            name: startPoint.name,
                            travelMinutes: Math.max(0, parseInt(section.querySelector('.day-start-travel')?.value || '0', 10) || 0),
                            travelInputEl: section.querySelector('.day-start-travel') || null,
                        });
                    }
                    const rows = [...section.querySelectorAll('.schedule-row')];
                    rows.forEach((row) => {
                        if (!selected(row)) return;
                        const selection = getRowSelection(row);
                        const option = selection.option;
                        if (selection.type === 'transfer' && option) {
                            let departure = normalizeLatLngPair(
                                parseFloat(option.dataset.departureLatitude || ''),
                                parseFloat(option.dataset.departureLongitude || ''),
                            );
                            let arrival = normalizeLatLngPair(
                                parseFloat(option.dataset.arrivalLatitude || ''),
                                parseFloat(option.dataset.arrivalLongitude || ''),
                            );
                            const order = Number(row.querySelector('.item-order')?.value || '0');
                            const safeOrder = Number.isFinite(order) && order > 0 ? order : 9999;
                            const transferId = String(option.value || '').trim() || String(safeOrder);
                            const transferPairKey = `transfer-${transferId}-day-${day}-order-${safeOrder}`;
                            const routeToNextCoords = parseTransferRouteCoords(option);
                            if (Array.isArray(routeToNextCoords) && routeToNextCoords.length >= 2) {
                                if (!departure) departure = routeToNextCoords[0];
                                if (!arrival) arrival = routeToNextCoords[routeToNextCoords.length - 1];
                            }
                            if (departure) {
                                points.push({
                                    day,
                                    role: 'schedule',
                                    order: safeOrder,
                                    mapOrder: (safeOrder * 10),
                                    type: 'transfer',
                                    lat: departure.lat,
                                    lng: departure.lng,
                                    name: `${String(option.textContent || '').trim()} (Departure)`,
                                    travelMinutes: 0,
                                    travelInputEl: null,
                                    routeToNextCoords,
                                    transferRole: 'departure',
                                    transferPairKey,
                                });
                            }
                            if (arrival) {
                                points.push({
                                    day,
                                    role: 'schedule',
                                    order: safeOrder + 0.01,
                                    mapOrder: (safeOrder * 10) + 1,
                                    type: 'transfer',
                                    lat: arrival.lat,
                                    lng: arrival.lng,
                                    name: `${String(option.textContent || '').trim()} (Arrival)`,
                                    travelMinutes: Math.max(0, parseInt(row.querySelector('.item-travel')?.value || '0', 10) || 0),
                                    travelInputEl: row.querySelector('.item-travel') || null,
                                    transferRole: 'arrival',
                                    transferPairKey,
                                });
                            }
                            return;
                        }
                        const point = parseOptionPoint(option, selection.type);
                        if (!point) return;
                        const order = Number(row.querySelector('.item-order')?.value || '0');
                        const safeOrder = Number.isFinite(order) && order > 0 ? order : 9999;
                        points.push({
                            day,
                            role: 'schedule',
                            order: safeOrder,
                            mapOrder: safeOrder * 10,
                            type: selection.type,
                            lat: point.lat,
                            lng: point.lng,
                            name: point.name,
                            travelMinutes: Math.max(0, parseInt(row.querySelector('.item-travel')?.value || '0', 10) || 0),
                            travelInputEl: row.querySelector('.item-travel') || null,
                        });
                    });
                    points.sort((a, b) => {
                        const aOrder = Number(a.mapOrder ?? (a.order || 0));
                        const bOrder = Number(b.mapOrder ?? (b.order || 0));
                        if (aOrder !== bOrder) return aOrder - bOrder;
                        const aRank = a.role === 'start' ? 0 : (a.role === 'schedule' ? 1 : 2);
                        const bRank = b.role === 'start' ? 0 : (b.role === 'schedule' ? 1 : 2);
                        if (aRank !== bRank) return aRank - bRank;
                        return Number(a.order || 0) - Number(b.order || 0);
                    });
                    const endType = normalizePointType(section.querySelector('.day-end-point-type')?.value || '');
                    const endOpt = section.querySelector('.day-end-point-item')?.selectedOptions?.[0] || null;
                    const endPoint = parseOptionPoint(endOpt, endType);
                    if ((endType === 'airport' || endType === 'hotel') && endPoint) {
                        points.push({
                            day,
                            role: 'end',
                            mapOrder: 9999999,
                            type: endPoint.type || normalizeMapPointType(endType),
                            lat: endPoint.lat,
                            lng: endPoint.lng,
                            name: endPoint.name,
                            travelMinutes: 0,
                            travelInputEl: null,
                        });
                    }
                    return points;
                };
                const syncTravelConnectorInputsFromHidden = (section) => {
                    if (!section) return;
                    const rows = [...section.querySelectorAll('.day-items .schedule-row')]
                        .filter((row) =>
                            !row.classList.contains('schedule-row-template') &&
                            !row.classList.contains('hidden')
                        );
                    rows.forEach((row) => {
                        const hiddenTravel = row.querySelector('.item-travel');
                        if (!hiddenTravel) return;
                        const connectorInput = row.nextElementSibling?.classList?.contains('travel-connector')
                            ? row.nextElementSibling.querySelector('.travel-connector-input')
                            : null;
                        if (!connectorInput || document.activeElement === connectorInput) return;
                        connectorInput.value = hiddenTravel.value || '';
                    });
                };
                const autoFillDayTravelMinutesFromMap = async (section, signal) => {
                    if (!section) return false;
                    const day = Number(section.dataset.day || '0');
                    if (!Number.isFinite(day) || day <= 0) return false;
                    const dayRoutePoints = collectDayRoutePoints(day).filter(isFiniteLatLng);
                    if (dayRoutePoints.length < 2) return false;
                    let hasChanges = false;
                    for (let i = 0; i < dayRoutePoints.length - 1; i++) {
                        const from = dayRoutePoints[i];
                        const to = dayRoutePoints[i + 1];
                        const targetInput = from?.travelInputEl;
                        if (!(targetInput instanceof HTMLInputElement)) continue;
                        if (document.activeElement === targetInput) continue;
                        const fromLatLng = toLeafletLatLng(from.lat, from.lng);
                        const toLatLng = toLeafletLatLng(to.lat, to.lng);
                        if (!fromLatLng || !toLatLng) continue;
                        try {
                            const routeData = await fetchRoadRouteData([fromLatLng, toLatLng], signal);
                            const minutes = Number(routeData?.durationMinutes ?? Number.NaN);
                            if (!Number.isFinite(minutes)) continue;
                            const normalizedValue = String(Math.max(0, Math.round(minutes)));
                            if (String(targetInput.value || '').trim() !== normalizedValue) {
                                targetInput.value = normalizedValue;
                                hasChanges = true;
                            }
                        } catch (fetchError) {
                            if (fetchError?.name === 'AbortError') {
                                throw fetchError;
                            }
                            console.warn('Auto travel-time route fetch failed.', fetchError);
                        }
                    }
                    if (hasChanges) {
                        syncTravelConnectorInputsFromHidden(section);
                    }
                    return hasChanges;
                };
                const autoFillAllTravelMinutesFromMap = async () => {
                    const sections = [...daySections.querySelectorAll('.day-section')]
                        .sort((a, b) => Number(a.dataset.day || 0) - Number(b.dataset.day || 0));
                    if (!sections.length) return false;
                    try {
                        activeAutoTravelFetchController?.abort();
                    } catch (_) {}
                    activeAutoTravelFetchController = typeof AbortController !== 'undefined'
                        ? new AbortController()
                        : null;
                    const signal = activeAutoTravelFetchController?.signal;
                    let changed = false;
                    for (const section of sections) {
                        const sectionChanged = await autoFillDayTravelMinutesFromMap(section, signal);
                        changed = changed || sectionChanged;
                    }
                    return changed;
                };
                const refreshMapDayOptions = () => {
                    const totalDays = clampDurationDays(durationInput.value || MIN_DURATION_DAYS);
                    mapSelectedDay = Math.max(1, Math.min(totalDays, clampWizardDay(wizardActiveDay)));
                    if (mapDayTabsEl) {
                        mapDayTabsEl.innerHTML = '';
                    }
                };
                const renderMapLegend = (dayList) => {
                    if (!mapLegendEl) return;
                    if (!Array.isArray(dayList) || dayList.length === 0) {
                        mapLegendEl.innerHTML = `<span class="text-[11px] text-gray-500 dark:text-gray-400">${@json(__('No day route selected.'))}</span>`;
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
                        const totalDays = clampDurationDays(durationInput.value || MIN_DURATION_DAYS);
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
                            safePoints.forEach((point) => {
                                const latLng = toLeafletLatLng(point.lat, point.lng);
                                if (!latLng) return;
                                bounds.push([latLng.lat, latLng.lng]);
                                const markerType = normalizeMapPointType(point.type || (point.role === 'end' || point.role === 'start' ? 'hotel' : 'attraction'));
                                const marker = L.marker(latLng, {
                                    icon: markerByTypeWithOrder(markerType, markerIndex),
                                }).addTo(itineraryDataLayer);
                                marker.bindPopup(`#${markerIndex} | Day ${day} | ${markerType.toUpperCase()} | ${point.name}`);
                                markerIndex += 1;
                            });
                            for (let i = 0; i < safePoints.length - 1; i++) {
                                const from = safePoints[i];
                                const to = safePoints[i + 1];
                                if (!isFiniteLatLng(from) || !isFiniteLatLng(to)) continue;
                                const fromLatLng = toLeafletLatLng(from.lat, from.lng);
                                const toLatLng = toLeafletLatLng(to.lat, to.lng);
                                if (!fromLatLng || !toLatLng) continue;
                                let segmentCoords = [fromLatLng, toLatLng];
                                const isTransferSegment =
                                    normalizeMapPointType(from.type) === 'transfer' &&
                                    normalizeMapPointType(to.type) === 'transfer';
                                const isMatchingTransferPair =
                                    isTransferSegment &&
                                    String(from.transferRole || '') === 'departure' &&
                                    String(to.transferRole || '') === 'arrival' &&
                                    String(from.transferPairKey || '') !== '' &&
                                    String(from.transferPairKey || '') === String(to.transferPairKey || '');
                                if (isMatchingTransferPair && Array.isArray(from.routeToNextCoords) && from.routeToNextCoords.length >= 2) {
                                    const customCoords = from.routeToNextCoords
                                        .map((coord) => toLeafletLatLng(coord.lat, coord.lng))
                                        .filter((coord) => coord && Number.isFinite(coord.lat) && Number.isFinite(coord.lng));
                                    if (customCoords.length >= 2) {
                                        segmentCoords = orientSegmentCoords(customCoords, fromLatLng, toLatLng);
                                    }
                                } else if (isMatchingTransferPair) {
                                    continue;
                                } else {
                                    try {
                                        const roadRoute = await fetchRoadRouteGeometry([fromLatLng, toLatLng], routeSignal);
                                        if (renderSeq !== mapRenderSeq) return;
                                        if (Array.isArray(roadRoute) && roadRoute.length >= 2) {
                                            segmentCoords = orientSegmentCoords(roadRoute, fromLatLng, toLatLng);
                                        }
                                    } catch (fetchError) {
                                        if (renderSeq !== mapRenderSeq) return;
                                        if (fetchError?.name !== 'AbortError') {
                                            console.warn('Road route fetch failed, fallback to straight segment.', fetchError);
                                        }
                                    }
                                }
                                const route = L.polyline(segmentCoords, {
                                    color: routePalette[(Number(day) - 1) % routePalette.length],
                                    weight: 4,
                                    opacity: 0.95,
                                    interactive: false,
                                    bubblingMouseEvents: false,
                                }).addTo(itineraryDataLayer);
                                itineraryRouteLayers.push(route);
                                const minutes = Math.max(0, Number(from.travelMinutes || 0));
                                if (minutes <= 0) continue;
                                const normFrom = normalizeLatLngPair(from.lat, from.lng);
                                const normTo = normalizeLatLngPair(to.lat, to.lng);
                                if (!normFrom || !normTo) continue;
                                const midLat = (normFrom.lat + normTo.lat) / 2;
                                const midLng = (normFrom.lng + normTo.lng) / 2;
                                const midLatLng = toLeafletLatLng(midLat, midLng);
                                if (!midLatLng) continue;
                                const badgeLatLng = closestPointOnRoute(segmentCoords, midLatLng) || midLatLng;
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
                const rebuildTravelConnectors = (sec) => {
                    const container = sec.querySelector('.day-items');
                    if (!container) return;
                    container.querySelectorAll('.travel-connector').forEach((el) => el.remove());
                    const rows = [...container.querySelectorAll('.schedule-row')]
                        .filter((row) =>
                            !row.classList.contains('schedule-row-template') &&
                            !row.classList.contains('hidden')
                        );
                    rows.forEach((row, index) => {
                        const hiddenTravel = row.querySelector('.item-travel');
                        if (!hiddenTravel) return;
                        const isLast = index === rows.length - 1;
                const connector = document.createElement('div');
                connector.className = 'travel-connector mt-2 w-full md:w-1/2';
                connector.innerHTML = `
                <div class="input-with-left-affix">
                    <span class="input-left-affix">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true" focusable="false">
                            <path d="M5.5 11.5L7.3 6.9C7.6 6.1 8.3 5.5 9.2 5.5h5.6c.9 0 1.6.6 1.9 1.4l1.8 4.6c1 .2 1.8 1.1 1.8 2.2v2.3c0 .8-.7 1.5-1.5 1.5h-.5a2.3 2.3 0 01-4.6 0h-4.4a2.3 2.3 0 01-4.6 0h-.5c-.8 0-1.5-.7-1.5-1.5v-2.3c0-1.1.8-2 1.8-2.2zm3.1-4.2L7.2 11h9.6l-1.4-3.7a.8.8 0 00-.7-.5H9.3c-.3 0-.6.2-.7.5zM8.2 18.9c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9zm7.6 0c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9z"/>
                        </svg>
                    </span>
                    <input type="number" min="0" step="5" class="travel-connector-input dark:border-gray-600 app-input" placeholder="${isLast ? 'Estimated travel time to end point (minutes)' : 'Estimated travel time to the next item (minutes)'}">
                </div>
            `;
                        const input = connector.querySelector('.travel-connector-input');
                        input.value = hiddenTravel.value || '';
                        input.addEventListener('input', () => {
                            const parsed = parseInt(input.value || '', 10);
                            hiddenTravel.value = Number.isFinite(parsed) ? String(Math.max(0, parsed)) :
                                '';
                            recalcNoConnectorRebuildSkipAuto();
                        });
                        row.insertAdjacentElement('afterend', connector);
                    });
                };
                const syncDayStartTravelPlaceholder = (section, activeRows = null) => {
                    if (!section) return;
                    const input = section.querySelector('.day-start-travel');
                    if (!input) return;
                    const nextLabel = String(input.dataset.nextPlaceholder || 'Estimated travel time to the next item (minutes)');
                    const endPointLabel = String(input.dataset.endpointPlaceholder || 'Estimated travel time to end point (minutes)');
                    const rows = Array.isArray(activeRows)
                        ? activeRows
                        : [...section.querySelectorAll('.schedule-row')]
                            .filter((row) =>
                                !row.classList.contains('schedule-row-template') &&
                                !row.classList.contains('hidden')
                            );
                    input.placeholder = rows.length > 0 ? nextLabel : endPointLabel;
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
                const syncRowTimeText = (row) => {
                    if (!row) return;
                    const startInput = row.querySelector('.item-start');
                    const endInput = row.querySelector('.item-end');
                    const startText = row.querySelector('.item-start-text');
                    const endText = row.querySelector('.item-end-text');
                    if (startText) startText.textContent = (startInput?.value || '').trim() || '--:-- --';
                    if (endText) endText.textContent = (endInput?.value || '').trim() || '--:-- --';
                };
                const resetRowAsTemplate = (row) => {
                    if (!row) return;
                    row.dataset.itemType = 'attraction';
                    row.dataset.rowTemplate = '1';
                    row.classList.add('hidden', 'schedule-row-template');
                    row.classList.remove(
                        'ring-2',
                        'ring-amber-300',
                        'border-amber-400',
                        'bg-amber-50/40',
                        'dark:border-amber-500/60',
                        'dark:bg-amber-900/10'
                    );
                    const typeSelect = row.querySelector('.item-type');
                    const regionSelect = row.querySelector('.item-region');
                    const attractionSelect = row.querySelector('.item-attraction');
                    const attractionSearchInput = row.querySelector('.item-attraction-search');
                    const attractionDropdown = row.querySelector('.item-attraction-dropdown');
                    const activitySelect = row.querySelector('.item-activity');
                    const activitySearchInput = row.querySelector('.item-activity-search');
                    const activityDropdown = row.querySelector('.item-activity-dropdown');
                    const transferSelect = row.querySelector('.item-transfer');
                    const fnbSelect = row.querySelector('.item-fnb');
                    const paxInput = row.querySelector('.item-pax');
                    const startInput = row.querySelector('.item-start');
                    const endInput = row.querySelector('.item-end');
                    const travelInput = row.querySelector('.item-travel');
                    const orderInput = row.querySelector('.item-order');
                    const mainCheckbox = row.querySelector('.item-main-experience');
                    const seqBadge = row.querySelector('.item-seq-badge');

                    if (typeSelect) typeSelect.value = 'attraction';
                    if (regionSelect) regionSelect.value = '';
                    if (attractionSelect) attractionSelect.value = '';
                    if (attractionSearchInput) attractionSearchInput.value = '';
                    if (attractionDropdown) {
                        attractionDropdown.classList.add('hidden');
                        attractionDropdown.innerHTML = '';
                    }
                    setAttractionNotice(row, '', 'info');
                    if (activitySelect) activitySelect.value = '';
                    if (activitySearchInput) activitySearchInput.value = '';
                    if (activityDropdown) {
                        activityDropdown.classList.add('hidden');
                        activityDropdown.innerHTML = '';
                    }
                    setActivityNotice(row, '', 'info');
                    if (transferSelect) transferSelect.value = '';
                    if (fnbSelect) fnbSelect.value = '';
                    if (paxInput) paxInput.value = '1';
                    if (startInput) startInput.value = '';
                    if (endInput) endInput.value = '';
                    if (travelInput) travelInput.value = '';
                    if (orderInput) orderInput.value = '';
                    if (mainCheckbox) mainCheckbox.checked = false;
                    if (seqBadge) seqBadge.textContent = '-';
                    markRegionManualState(row, false);
                    toggleType(row, 'attraction', false);
                    updateScheduleRowTheme(row);
                    syncRowTimeText(row);
                };
                const recalcDay = async (sec) => {
                    const rows = [...sec.querySelectorAll('.schedule-row')];
                    const activeRows = rows.filter((row) =>
                        !row.classList.contains('schedule-row-template') &&
                        !row.classList.contains('hidden')
                    );
                    const chosen = rows.filter(selected);
                    syncDayStartTravelPlaceholder(sec, activeRows);
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
                    const dayEndTimeEndpointText = sec.querySelector('.day-end-time-endpoint-text');
                    if (startAtLabel) startAtLabel.textContent = startPointLabel;
                    if (endsAtLabel) endsAtLabel.textContent = endPointLabel;
                    let cur = start;
                    rows.forEach((r) => {
                        const seq = r.querySelector('.item-seq-badge');
                        if (!chosen.includes(r)) {
                            r.querySelector('.item-start').value = '';
                            r.querySelector('.item-end').value = '';
                            r.querySelector('.item-order').value = '';
                            syncRowTimeText(r);
                            if (seq) seq.textContent = '-';
                        }
                    });
                    chosen.forEach((r, i) => {
                        r.querySelector('.item-order').value = String(i + 1);
                        const seq = r.querySelector('.item-seq-badge');
                        if (seq) seq.textContent = String(i + 1);
                    });
                    const dayRoutePoints = collectDayRoutePoints(day);
                    const startRouteIndex = dayRoutePoints.findIndex((point) => point.role === 'start');
                    const endRouteIndex = dayRoutePoints.findIndex((point) => point.role === 'end');
                    const itemDisplayBase = startRouteIndex >= 0 ? 2 : 1;
                    chosen.forEach((r, i) => {
                        const seq = r.querySelector('.item-seq-badge');
                        if (seq) seq.textContent = String(itemDisplayBase + i);
                    });
                    const startPointSeq = sec.querySelector('.day-start-point-seq');
                    const endPointSeq = sec.querySelector('.day-end-point-seq');
                    if (startPointSeq) {
                        startPointSeq.textContent = startRouteIndex >= 0 ? String(startRouteIndex + 1) : '-';
                    }
                    if (endPointSeq) {
                        endPointSeq.textContent = endRouteIndex >= 0 ? String(endRouteIndex + 1) : '-';
                    }
                    if (start === null) {
                        if (dayEndTimeInput) dayEndTimeInput.value = '';
                        if (dayEndTimeEndpointText) dayEndTimeEndpointText.textContent = '-';
                        return;
                    }
                    if (!chosen.length) {
                        const onlyPointEndTime = fromMin(start + (Number.isFinite(startTravelMinutes) ? startTravelMinutes : 0));
                        if (dayEndTimeInput) dayEndTimeInput.value = onlyPointEndTime || '';
                        if (dayEndTimeEndpointText) dayEndTimeEndpointText.textContent = onlyPointEndTime || '-';
                        return;
                    }
                    cur = start + (Number.isFinite(startTravelMinutes) ? startTravelMinutes : 0);
                    chosen.forEach((r) => {
                        const opt = activeSelect(r)?.selectedOptions?.[0];
                        const dur = Math.max(1, parseInt(opt?.dataset?.duration || '120', 10));
                        r.querySelector('.item-start').value = fromMin(cur);
                        r.querySelector('.item-end').value = fromMin(cur + dur);
                        syncRowTimeText(r);
                        const travel = Math.max(0, parseInt(r.querySelector('.item-travel').value || '0',
                            10));
                        cur += dur + travel;
                    });
                    const endTimeText = fromMin(cur);
                    if (dayEndTimeInput) dayEndTimeInput.value = endTimeText;
                    if (dayEndTimeEndpointText) dayEndTimeEndpointText.textContent = endTimeText || '-';
                };
                const recalcAll = async () => {
                    for (const sec of [...daySections.querySelectorAll('.day-section')].sort((a, b) => Number(a
                            .dataset.day) - Number(b.dataset.day))) await recalcDay(sec);
                };
                const reindex = () => {
                    let ai = 0,
                        bi = 0,
                        ti = 0,
                        fi = 0;
                    [...daySections.querySelectorAll('.day-section')].sort((a, b) => Number(a.dataset.day) - Number(b
                        .dataset.day)).forEach((sec) => {
                        let order = 0;
                        const day = Number(sec.dataset.day || '1');
                        sec.querySelectorAll('.schedule-row').forEach((r) => {
                            const a = r.querySelector('.item-attraction'),
                                b = r.querySelector('.item-activity'),
                                tr = r.querySelector('.item-transfer'),
                                f = r.querySelector('.item-fnb'),
                                p = r.querySelector('.item-pax'),
                                d = r.querySelector('.item-day'),
                                s = r.querySelector('.item-start'),
                                e = r.querySelector('.item-end'),
                                t = r.querySelector('.item-travel'),
                                o = r.querySelector('.item-order');
                            [a, b, tr, f, p, d, s, e, t, o].forEach((el) => el?.removeAttribute('name'));
                            d.value = String(day);
                            if (!selected(r)) return;
                            order += 1;
                            o.value = String(order);
                            if (rowType(r) === 'activity') {
                                if (b) b.name = `itinerary_activity_items[${bi}][activity_id]`;
                                d.name = `itinerary_activity_items[${bi}][day_number]`;
                                p.name = `itinerary_activity_items[${bi}][pax]`;
                                s.name = `itinerary_activity_items[${bi}][start_time]`;
                                e.name = `itinerary_activity_items[${bi}][end_time]`;
                                t.name = `itinerary_activity_items[${bi}][travel_minutes_to_next]`;
                                o.name = `itinerary_activity_items[${bi}][visit_order]`;
                                bi++;
                            } else if (rowType(r) === 'transfer') {
                                if (tr) tr.name = `itinerary_island_transfer_items[${ti}][island_transfer_id]`;
                                d.name = `itinerary_island_transfer_items[${ti}][day_number]`;
                                p.name = `itinerary_island_transfer_items[${ti}][pax]`;
                                s.name = `itinerary_island_transfer_items[${ti}][start_time]`;
                                e.name = `itinerary_island_transfer_items[${ti}][end_time]`;
                                t.name = `itinerary_island_transfer_items[${ti}][travel_minutes_to_next]`;
                                o.name = `itinerary_island_transfer_items[${ti}][visit_order]`;
                                ti++;
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
                const MAX_TRANSPORT_UNITS_PER_DAY = 10;
                const getTransportRows = (section) => [...section.querySelectorAll('.day-transport-row')];
                const toggleTransportButtonsState = (section) => {
                    const rows = getTransportRows(section);
                    const removeButtons = section.querySelectorAll('[data-remove-transport="1"]');
                    removeButtons.forEach((button) => {
                        button.classList.toggle('hidden', rows.length <= 1);
                    });
                    const addButton = section.querySelector('[data-add-transport="1"]');
                    if (addButton) {
                        const reachedMax = rows.length >= MAX_TRANSPORT_UNITS_PER_DAY;
                        addButton.disabled = reachedMax;
                        addButton.classList.toggle('opacity-60', reachedMax);
                        addButton.classList.toggle('cursor-not-allowed', reachedMax);
                    }
                };
                const reindexTransportRows = () => {
                    const sections = [...daySections.querySelectorAll('.day-section')].sort((a, b) => Number(a.dataset.day) - Number(b.dataset.day));
                    let transportIndex = 0;
                    sections.forEach((section) => {
                        const day = Number(section.dataset.day || 0);
                        getTransportRows(section).forEach((row) => {
                            const transportUnit = row.querySelector('.day-transport-unit');
                            const transportDay = row.querySelector('.day-transport-day');
                            if (transportUnit) {
                                transportUnit.name = `daily_transport_units[${transportIndex}][transport_unit_id]`;
                            }
                            if (transportDay) {
                                transportDay.name = `daily_transport_units[${transportIndex}][day_number]`;
                                transportDay.value = String(day);
                            }
                            transportIndex += 1;
                        });
                        toggleTransportButtonsState(section);
                    });
                };
                const addTransportRow = (section) => {
                    if (!section) return;
                    const rows = getTransportRows(section);
                    if (rows.length >= MAX_TRANSPORT_UNITS_PER_DAY) return;
                    const firstRow = rows[0];
                    if (!firstRow) return;
                    const clone = firstRow.cloneNode(true);
                    const transportUnit = clone.querySelector('.day-transport-unit');
                    const transportDay = clone.querySelector('.day-transport-day');
                    if (transportUnit) {
                        transportUnit.value = '';
                    }
                    if (transportDay) {
                        transportDay.value = section.dataset.day || '';
                    }
                    const container = section.querySelector('.day-transport-units');
                    container?.appendChild(clone);
                    reindexTransportRows();
                    recalcNoConnectorRebuild();
                };
                const bindTransportEvents = (section) => {
                    if (!section || section.dataset.transportBound === '1') return;
                    section.dataset.transportBound = '1';
                    const addButton = section.querySelector('[data-add-transport="1"]');
                    addButton?.addEventListener('click', () => addTransportRow(section));
                    section.addEventListener('click', (event) => {
                        const button = event.target instanceof HTMLElement
                            ? event.target.closest('[data-remove-transport="1"]')
                            : null;
                        if (!button) return;
                        const row = button.closest('.day-transport-row');
                        if (!row) return;
                        const rows = getTransportRows(section);
                        if (rows.length <= 1) return;
                        row.remove();
                        reindexTransportRows();
                        recalcNoConnectorRebuild();
                    });
                    section.addEventListener('change', (event) => {
                        const input = event.target instanceof HTMLElement
                            ? event.target.closest('.day-transport-unit')
                            : null;
                        if (!input) return;
                        recalcNoConnectorRebuild();
                    });
                    toggleTransportButtonsState(section);
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
                            '.day-start-point-label').innerHTML = `<span class="mr-2 day-start-point-seq inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span><span>Day ${day} Start Point</span>`);
                        section.querySelector('.day-end-point-label') && (section.querySelector(
                            '.day-end-point-label').innerHTML = `<span class="mr-2 day-end-point-seq inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span><span>Day ${day} End Point</span>`);
                        const startType = section.querySelector('.day-start-point-type');
                        const startItem = section.querySelector('.day-start-point-item');
                        const startRoomSelect = section.querySelector('.day-start-room-select');
                        const startBookingSelect = section.querySelector('.day-start-booking-mode');
                        const startRoom = section.querySelector('.day-start-room-count');
                        const endType = section.querySelector('.day-end-point-type');
                        const endItem = section.querySelector('.day-end-point-item');
                        const endRoomSelect = section.querySelector('.day-end-room-select');
                        const endBookingSelect = section.querySelector('.day-end-booking-mode');
                        const endRoom = section.querySelector('.day-end-room-count');
                        const dayStartTimeInput = section.querySelector('.day-start-time');
                        const startTravelInput = section.querySelector('.day-start-travel');
                        const mainExperienceTypeInput = section.querySelector('.day-main-experience-type');
                        const mainExperienceItemInput = section.querySelector('.day-main-experience-item');

                        if (startType) startType.name = `daily_start_point_types[${day}]`;
                        if (startItem) startItem.name = `daily_start_point_items[${day}]`;
                        if (startRoomSelect) startRoomSelect.name = `daily_start_point_room_ids[${day}]`;
                        if (startBookingSelect) startBookingSelect.name =
                            `daily_start_hotel_booking_modes[${day}]`;
                        if (startRoom) startRoom.name = `daily_start_point_room_counts[${day}]`;
                        if (endType) endType.name = `daily_end_point_types[${day}]`;
                        if (endItem) endItem.name = `daily_end_point_items[${day}]`;
                        if (endRoomSelect) endRoomSelect.name = `daily_end_point_room_ids[${day}]`;
                        if (endBookingSelect) endBookingSelect.name = `daily_end_hotel_booking_modes[${day}]`;
                        if (endRoom) endRoom.name = `daily_end_point_room_counts[${day}]`;
                        if (dayStartTimeInput) dayStartTimeInput.name = `day_start_times[${day}]`;
                        if (startTravelInput) startTravelInput.name = `day_start_travel_minutes[${day}]`;
                        if (mainExperienceTypeInput) mainExperienceTypeInput.name =
                            `daily_main_experience_types[${day}]`;
                        if (mainExperienceItemInput) mainExperienceItemInput.name =
                            `daily_main_experience_items[${day}]`;

                        if (startType) {
                            const previousOption = startType.querySelector('option[value="previous_day_end"]');
                            if (day === 1) {
                                previousOption?.remove();
                                if (startType.value === 'previous_day_end') {
                                    startType.value = '';
                                }
                            } else {
                                if (!previousOption) {
                                    startType.insertAdjacentHTML('afterbegin',
                                        '<option value="previous_day_end">' + @json(__('Previous Day Endpoint (Auto)')) + '</option>'
                                        );
                                }
                            }
                        }

                        if (endType) {
                            const airportOption = endType.querySelector('option[value="airport"]');
                            if (!airportOption) {
                                endType.insertAdjacentHTML('beforeend', '<option value="airport">' + @json(__('Airport')) + '</option>');
                            }
                        }
                    });
                    reindexTransportRows();
                };
                const updateDayPointTheme = (section) => {
                    if (!section) return;
                    const startCard = section.querySelector('.day-start-point-card');
                    const endCard = section.querySelector('.day-end-point-card');
                    if (startCard) {
                        startCard.classList.remove('theme-airport', 'theme-hotel');
                    }
                    if (endCard) {
                        endCard.classList.remove('theme-airport', 'theme-hotel');
                    }
                };
                const copyDayVisualClass = (section, baselineSection, selector) => {
                    if (!section || !baselineSection) return;
                    const target = section.querySelector(selector);
                    const baseline = baselineSection.querySelector(selector);
                    if (!target || !baseline) return;
                    target.className = baseline.className;
                };
                const standardizeDaySectionVisual = (section, baselineSection = null) => {
                    if (!section) return;
                    const baseline = baselineSection || daySections.querySelector('.day-section[data-day="1"]') || daySections
                        .querySelector('.day-section');
                    if (!baseline || baseline === section) return;
                    section.className = baseline.className;
                    [
                        '.day-card-header',
                        '.day-card-header-pill',
                        '.day-start-point-card',
                        '.day-end-point-card',
                        '.add-item',
                        '[data-add-transport="1"]',
                        '.day-start-time',
                        '.day-end-time',
                        '.day-transport-unit',
                        '.day-start-point-type',
                        '.day-start-point-item',
                        '.day-start-room-select',
                        '.day-start-booking-mode',
                        '.day-end-point-type',
                        '.day-end-point-item',
                        '.day-end-room-select',
                        '.day-end-booking-mode',
                        '.day-start-travel',
                    ].forEach((selector) => copyDayVisualClass(section, baseline, selector));
                };
                const standardizeAllDaySectionVisuals = () => {
                    const baseline = daySections.querySelector('.day-section[data-day="1"]') || daySections.querySelector(
                        '.day-section');
                    if (!baseline) return;
                    daySections.querySelectorAll('.day-section').forEach((section) => {
                        standardizeDaySectionVisual(section, baseline);
                    });
                };
                const resetClonedWysiwyg = (section) => {
                    if (!section) return;
                    section.querySelectorAll('.wysiwyg').forEach((wrapper) => wrapper.remove());
                    section.querySelectorAll('textarea[data-wysiwyg-initialized], textarea[data-wysiwyg-hidden]')
                        .forEach((textarea) => {
                            textarea.removeAttribute('data-wysiwyg-initialized');
                            textarea.removeAttribute('data-wysiwyg-hidden');
                            textarea.style.position = '';
                            textarea.style.left = '';
                            textarea.style.top = '';
                            textarea.style.width = '';
                            textarea.style.height = '';
                            textarea.style.opacity = '';
                            textarea.style.pointerEvents = '';
                        });
                };
                const updateScheduleRowTheme = (row) => {
                    if (!row) return;
                    const type = rowType(row);
                    row.classList.remove('item-theme-attraction', 'item-theme-activity', 'item-theme-transfer', 'item-theme-fnb');
                    if (type === 'activity') {
                        row.classList.add('item-theme-activity');
                    } else if (type === 'transfer') {
                        row.classList.add('item-theme-transfer');
                    } else if (type === 'fnb') {
                        row.classList.add('item-theme-fnb');
                    } else {
                        row.classList.add('item-theme-attraction');
                    }
                };
                const pointOptionCache = new WeakMap();
                const clonePointOptions = (sourceSelect, targetSelect) => {
                    if (!sourceSelect || !targetSelect) return;
                    let sourceOptions = pointOptionCache.get(sourceSelect);
                    if (!sourceOptions || sourceOptions.length === 0) {
                        sourceOptions = Array.from(sourceSelect.options).map((option) => {
                            const clone = option.cloneNode(true);
                            clone.hidden = false;
                            clone.disabled = false;
                            return clone;
                        });
                    }
                    const targetOptions = sourceOptions.map((option) => {
                        const clone = option.cloneNode(true);
                        clone.hidden = false;
                        clone.disabled = false;
                        return clone;
                    });
                    targetSelect.innerHTML = '';
                    targetOptions.forEach((option) => targetSelect.appendChild(option.cloneNode(true)));
                    pointOptionCache.set(targetSelect, targetOptions);
                };
                const destinationInput = document.getElementById('itinerary-destination');
                const normalizeDestination = (value) => String(value || '').toLowerCase().trim();
                const normalizeDestinationText = (value) => normalizeDestination(value).replace(/\s+/g, ' ');
                const tokenizeDestinationText = (value) =>
                    normalizeDestinationText(value)
                        .split(/[^a-z0-9]+/i)
                        .map((token) => token.trim())
                        .filter((token) => token !== '');
                const destinationFieldMatches = (value, keyword) => {
                    const normalizedValue = normalizeDestinationText(value);
                    const normalizedKeyword = normalizeDestinationText(keyword);
                    if (!normalizedKeyword) return true;
                    if (!normalizedValue) return false;
                    if (normalizedValue === normalizedKeyword) return true;

                    const valueTokens = tokenizeDestinationText(normalizedValue);
                    const keywordTokens = tokenizeDestinationText(normalizedKeyword);
                    if (keywordTokens.length === 0) return true;

                    return keywordTokens.every((keywordToken) => valueTokens.includes(keywordToken));
                };
                const matchesDestinationOption = (option) => {
                    const keyword = normalizeDestination(destinationInput?.value || '');
                    if (!keyword) return true;
                    const city = option.dataset.city || '';
                    const province = option.dataset.province || '';
                    const location = option.dataset.location || '';
                    const destination = option.dataset.destination || '';
                    return destinationFieldMatches(city, keyword)
                        || destinationFieldMatches(province, keyword)
                        || destinationFieldMatches(location, keyword)
                        || destinationFieldMatches(destination, keyword);
                };
                const findFieldLabel = (field) => {
                    if (!field) return null;
                    const wrappedLabel = field.closest('label');
                    if (wrappedLabel) return wrappedLabel;
                    const fieldId = String(field.id || '').trim();
                    if (fieldId !== '') {
                        const byFor = document.querySelector(`label[for="${fieldId}"]`);
                        if (byFor) return byFor;
                    }
                    let current = field.parentElement;
                    for (let depth = 0; depth < 4 && current; depth += 1) {
                        const directLabel = current.querySelector(':scope > label');
                        if (directLabel) return directLabel;
                        current = current.parentElement;
                    }
                    return null;
                };
                const refreshRequiredAsterisks = () => {
                    const root = document.querySelector('.itinerary-form-page');
                    if (!root) return;
                    const activeLabels = new Set();
                    root.querySelectorAll('input[required], select[required], textarea[required]').forEach((field) => {
                        if (field.disabled) return;
                        const label = findFieldLabel(field);
                        if (!label) return;
                        let asterisk = label.querySelector(':scope > .required-asterisk');
                        if (!asterisk) {
                            asterisk = document.createElement('span');
                            asterisk.className = 'required-asterisk';
                            asterisk.textContent = '*';
                            label.appendChild(asterisk);
                        }
                        activeLabels.add(label);
                    });
                    root.querySelectorAll('label > .required-asterisk').forEach((asterisk) => {
                        const label = asterisk.parentElement;
                        if (!activeLabels.has(label)) {
                            asterisk.remove();
                        }
                    });
                };
                const syncPointItemVisibility = () => {
                    daySections.querySelectorAll('.day-section').forEach((section) => {
                        const startType = section.querySelector('.day-start-point-type');
                        const startItem = section.querySelector('.day-start-point-item');
                        const startRoomWrap = section.querySelector('.day-start-room-wrap');
                        const startRoomSelect = section.querySelector('.day-start-room-select');
                        const startBookingWrap = section.querySelector('.day-start-booking-wrap');
                        const startBookingSelect = section.querySelector('.day-start-booking-mode');
                        const startRoomInput = section.querySelector('.day-start-room-count');
                        const endType = section.querySelector('.day-end-point-type');
                        const endItem = section.querySelector('.day-end-point-item');
                        const endRoomWrap = section.querySelector('.day-end-room-wrap');
                        const endRoomSelect = section.querySelector('.day-end-room-select');
                        const endBookingWrap = section.querySelector('.day-end-booking-wrap');
                        const endBookingSelect = section.querySelector('.day-end-booking-mode');
                        const endRoomInput = section.querySelector('.day-end-room-count');

                            const applyFilter = (typeSelect, itemSelect, isHotelSelfBooking = false) => {
                                if (!typeSelect || !itemSelect) return;
                                const selectedType = normalizePointType(typeSelect.value || '');
                                const requiresItem = selectedType === 'airport' || (isHotelPointType(selectedType) && !isHotelSelfBooking);
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
                                itemSelect.required = false;
                                itemSelect.value = '';
                                return;
                            }

                            itemSelect.disabled = false;
                            itemSelect.required = true;
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
                            }

                            if (selectedValue !== '' &&
                                !Array.from(itemSelect.options).some((option) => option.value === selectedValue)) {
                                itemSelect.value = '';
                            }
                        };

                        const startHotel = isHotelPointType(startType?.value || '');
                        const startHotelSelfBooking = startHotel && isSelfBookedHotelMode(startBookingSelect?.value || 'arranged');
                        applyFilter(startType, startItem, startHotelSelfBooking);
                        if (startRoomWrap) startRoomWrap.classList.toggle('hidden', !startHotel);
                        if (startBookingWrap) startBookingWrap.classList.toggle('hidden', !startHotel);
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
                            startRoomSelect.disabled = !startHotel || startHotelSelfBooking;
                            startRoomSelect.required = startHotel && !startHotelSelfBooking;
                            if (!startHotel || startHotelSelfBooking) {
                                startRoomSelect.value = '';
                            } else if (String(startRoomSelect.value || '') === '' || startRoomSelect
                                .selectedOptions?.[0]?.hidden) {
                                startRoomSelect.value = '';
                            }
                        }
                        if (startRoomInput) {
                            startRoomInput.disabled = !startHotel || startHotelSelfBooking;
                            if (!startHotel || startHotelSelfBooking) startRoomInput.value = '1';
                        }
                        if (startBookingSelect) {
                            startBookingSelect.disabled = !startHotel;
                            startBookingSelect.required = startHotel;
                            if (!startHotel) {
                                startBookingSelect.value = 'arranged';
                            } else if (isSelfBookedHotelMode(startBookingSelect.value || '')) {
                                startBookingSelect.value = 'self';
                            } else if (!['arranged', 'self'].includes(String(startBookingSelect.value || ''))) {
                                startBookingSelect.value = 'arranged';
                            }
                        }

                        const endHotel = isHotelPointType(endType?.value || '');
                        const endHotelSelfBooking = endHotel && isSelfBookedHotelMode(endBookingSelect?.value || 'arranged');
                        applyFilter(endType, endItem, endHotelSelfBooking);
                        if (endRoomWrap) endRoomWrap.classList.toggle('hidden', !endHotel);
                        if (endBookingWrap) endBookingWrap.classList.toggle('hidden', !endHotel);
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
                            endRoomSelect.disabled = !endHotel || endHotelSelfBooking;
                            endRoomSelect.required = endHotel && !endHotelSelfBooking;
                            if (!endHotel || endHotelSelfBooking) {
                                endRoomSelect.value = '';
                            } else if (String(endRoomSelect.value || '') === '' || endRoomSelect.selectedOptions
                                ?.[0]?.hidden) {
                                endRoomSelect.value = '';
                            }
                        }
                        if (endRoomInput) {
                            endRoomInput.disabled = !endHotel || endHotelSelfBooking;
                            if (!endHotel || endHotelSelfBooking) endRoomInput.value = '1';
                        }
                        if (endBookingSelect) {
                            endBookingSelect.disabled = !endHotel;
                            endBookingSelect.required = endHotel;
                            if (!endHotel) {
                                endBookingSelect.value = 'arranged';
                            } else if (isSelfBookedHotelMode(endBookingSelect.value || '')) {
                                endBookingSelect.value = 'self';
                            } else if (!['arranged', 'self'].includes(String(endBookingSelect.value || ''))) {
                                endBookingSelect.value = 'arranged';
                            }
                        }
                    });
                    refreshRequiredAsterisks();
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
                        const isEligible = selected(row) && rowType(row) !== 'transfer';
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
                    if (type === 'transfer') {
                        const checkbox = selectedMainRow.querySelector('.item-main-experience');
                        if (checkbox) checkbox.checked = false;
                        typeSelect.value = '';
                        itemSelect.value = '';
                        selectedMainRow.classList.remove('ring-2', 'ring-amber-300', 'border-amber-400',
                            'bg-amber-50/40', 'dark:border-amber-500/60', 'dark:bg-amber-900/10');
                        return;
                    }
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
                    const totalDays = clampDurationDays(durationInput.value || MIN_DURATION_DAYS);
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
                const recalcNoConnectorRebuild = async ({
                    skipAutoTravel = false
                } = {}) => {
                    syncDayPointOptionRules();
                    syncPointItemVisibility();
                    if (!skipAutoTravel) {
                        try {
                            await autoFillAllTravelMinutesFromMap();
                        } catch (error) {
                            if (error?.name !== 'AbortError') {
                                console.warn('Auto travel-time recalculation failed.', error);
                            }
                        }
                    }
                    await recalcAll();
                    reindex();
                    syncHotelStaysHidden();
                    updateDayEndpointBadges();
                    daySections.querySelectorAll('.day-section').forEach(updateDayPointTheme);
                    renderDayWizardTabs();
                    requestRenderItineraryMap();
                };
                const recalcNoConnectorRebuildSkipAuto = () => recalcNoConnectorRebuild({
                    skipAutoTravel: true
                });
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
                        fallbackOnBody: true,
                        fallbackTolerance: 8,
                        fallbackClass: 'schedule-row-fallback',
                        draggable: '.schedule-row:not(.schedule-row-template)',
                        handle: '.drag-handle',
                        ghostClass: 'schedule-row-ghost',
                        chosenClass: 'schedule-row-chosen',
                        onClone: (event) => {
                            const clone = event?.clone;
                            const item = event?.item;
                            if (!clone || !item) return;
                            const rect = item.getBoundingClientRect();
                            clone.style.width = `${Math.round(rect.width)}px`;
                            clone.style.height = `${Math.round(rect.height)}px`;
                        },
                        onEnd: () => recalc(),
                    });
                    container.dataset.sortableInit = '1';
                };
                const bindRow = (r) => {
                    bindAttractionAutocomplete(r);
                    bindActivityAutocomplete(r);
                    bindFnbAutocomplete(r);
                    updateScheduleRowTheme(r);
                    r.querySelector('.item-type')?.addEventListener('change', (e) => {
                        toggleType(r, e.target.value, true);
                        markRegionManualState(r, false);
                        syncRegionFromSelectedItem(r, true);
                        updateScheduleRowTheme(r);
                        recalc();
                    });
                    r.querySelector('.item-region')?.addEventListener('change', (event) => {
                        const value = String(event.target.value || '').trim();
                        markRegionManualState(r, value !== '');
                        const attractionSelect = r.querySelector('.item-attraction');
                        const attractionSearchInput = r.querySelector('.item-attraction-search');
                        const attractionDropdown = r.querySelector('.item-attraction-dropdown');
                        const activitySelect = r.querySelector('.item-activity');
                        const activitySearchInput = r.querySelector('.item-activity-search');
                        const activityDropdown = r.querySelector('.item-activity-dropdown');
                        const transferSelect = r.querySelector('.item-transfer');
                        const fnbSelect = r.querySelector('.item-fnb');
                        const fnbSearchInput = r.querySelector('.item-fnb-search');
                        const fnbDropdown = r.querySelector('.item-fnb-dropdown');
                        if (attractionSelect) attractionSelect.value = '';
                        if (attractionSearchInput) attractionSearchInput.value = '';
                        if (attractionDropdown) {
                            attractionDropdown.classList.add('hidden');
                            attractionDropdown.innerHTML = '';
                        }
                        setAttractionNotice(r, '', 'info');
                        if (activitySelect) activitySelect.value = '';
                        if (activitySearchInput) activitySearchInput.value = '';
                        if (activityDropdown) {
                            activityDropdown.classList.add('hidden');
                            activityDropdown.innerHTML = '';
                        }
                        setActivityNotice(r, '', 'info');
                        if (transferSelect) transferSelect.value = '';
                        if (fnbSelect) fnbSelect.value = '';
                        if (fnbSearchInput) fnbSearchInput.value = '';
                        if (fnbDropdown) {
                            fnbDropdown.classList.add('hidden');
                            fnbDropdown.innerHTML = '';
                        }
                        setFnbNotice(r, '', 'info');
                        recalcNoConnectorRebuild();
                    });
                    r.querySelector('.item-attraction')?.addEventListener('change', () => {
                        syncAttractionInputFromSelect(r);
                        syncRegionFromSelectedItem(r);
                        recalc();
                    });
                    r.querySelector('.item-activity')?.addEventListener('change', () => {
                        syncRegionFromSelectedItem(r);
                        recalc();
                    });
                    r.querySelector('.item-transfer')?.addEventListener('change', () => {
                        syncRegionFromSelectedItem(r);
                        recalc();
                    });
                    r.querySelector('.item-fnb')?.addEventListener('change', () => {
                        syncFnbInputFromSelect(r);
                        syncRegionFromSelectedItem(r);
                        recalc();
                    });
                    r.querySelector('.item-main-experience')?.addEventListener('change', () => {
                        const section = r.closest('.day-section');
                        syncMainExperienceSelection(section, r);
                        recalcNoConnectorRebuild();
                    });
                    r.querySelector('.remove-row')?.addEventListener('click', () => {
                        const section = r.closest('.day-section');
                        if (!section) return;
                        const activeRows = [...section.querySelectorAll('.schedule-row')]
                            .filter((row) => !row.classList.contains('schedule-row-template'));
                        if (activeRows.length <= 1) {
                            resetRowAsTemplate(r);
                        } else {
                            r.remove();
                        }
                        recalc();
                    });
                    toggleType(r, rowType(r), false);
                    if (String(r.querySelector('.item-region')?.value || '').trim() === '') {
                        markRegionManualState(r, false);
                        syncRegionFromSelectedItem(r);
                    } else {
                        markRegionManualState(r, true);
                    }
                    updateScheduleRowTheme(r);
                    syncRowTimeText(r);
                };
                const cloneRow = (sec, type) => {
                    const src = sec.querySelector('.schedule-row');
                    if (!src) return;
                    const r = src.cloneNode(true);
                    r.classList.remove('hidden', 'schedule-row-template');
                    delete r.dataset.rowTemplate;
                    r.querySelector('.item-region').value = '';
                    markRegionManualState(r, false);
                    r.querySelector('.item-attraction').value = '';
                    const attractionSearchInput = r.querySelector('.item-attraction-search');
                    if (attractionSearchInput) attractionSearchInput.value = '';
                    const attractionDropdown = r.querySelector('.item-attraction-dropdown');
                    if (attractionDropdown) {
                        attractionDropdown.classList.add('hidden');
                        attractionDropdown.innerHTML = '';
                    }
                    setAttractionNotice(r, '', 'info');
                    r.querySelector('.item-activity').value = '';
                    const activitySearchInput = r.querySelector('.item-activity-search');
                    if (activitySearchInput) activitySearchInput.value = '';
                    const activityDropdown = r.querySelector('.item-activity-dropdown');
                    if (activityDropdown) {
                        activityDropdown.classList.add('hidden');
                        activityDropdown.innerHTML = '';
                    }
                    setActivityNotice(r, '', 'info');
                    const transferSelect = r.querySelector('.item-transfer');
                    if (transferSelect) transferSelect.value = '';
                    const fnbSelect = r.querySelector('.item-fnb');
                    if (fnbSelect) fnbSelect.value = '';
                    const fnbSearchInput = r.querySelector('.item-fnb-search');
                    if (fnbSearchInput) fnbSearchInput.value = '';
                    const fnbDropdown = r.querySelector('.item-fnb-dropdown');
                    if (fnbDropdown) {
                        fnbDropdown.classList.add('hidden');
                        fnbDropdown.innerHTML = '';
                    }
                    setFnbNotice(r, '', 'info');
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
                const getNextItemType = (section) => {
                    const rows = [...(section?.querySelectorAll('.schedule-row') || [])];
                    const lastSelected = [...rows].reverse().find((row) => selected(row));
                    const currentType = rowType(lastSelected || rows[rows.length - 1]);
                    return ['attraction', 'activity', 'transfer', 'fnb'].includes(currentType) ? currentType : 'attraction';
                };
                const syncDurationNights = ({
                    commitDays = false
                } = {}) => {
                    if (!durationNightsInput) return;
                    const rawDays = parseInt(String(durationInput.value ?? '').trim(), 10);
                    if (!Number.isFinite(rawDays) && !commitDays) {
                        return;
                    }
                    const days = clampDurationDays(Number.isFinite(rawDays) ? rawDays : MIN_DURATION_DAYS);
                    if (commitDays) {
                        durationInput.value = String(days);
                    }
                    const nights = deriveDurationNightsFromDays(days);
                    durationNightsInput.value = String(nights);
                };
                daySections.querySelectorAll('.day-section').forEach((sec) => {
                    sec.querySelectorAll('.schedule-row').forEach(bindRow);
                    sec.querySelector('.add-item')?.addEventListener('click', () => cloneRow(sec, getNextItemType(sec)));
                    bindTransportEvents(sec);
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
                    sec.querySelector('.day-start-booking-mode')?.addEventListener('change', () => {
                        syncPointItemVisibility();
                        recalcNoConnectorRebuild();
                    });
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
                    sec.querySelector('.day-end-booking-mode')?.addEventListener('change', () => {
                        syncPointItemVisibility();
                        recalcNoConnectorRebuild();
                    });
                    sec.querySelector('.day-end-room-count')?.addEventListener('input', recalcNoConnectorRebuild);
                    sec.querySelector('.day-start-travel')?.addEventListener('input', recalcNoConnectorRebuildSkipAuto);
                    sec.querySelector('.day-start-time')?.addEventListener('change', recalc);
                    initSortable(sec);
                    updateDayPointTheme(sec);
                });
                document.addEventListener('click', (event) => {
                    const target = event.target instanceof HTMLElement ? event.target : null;
                    daySections.querySelectorAll('.schedule-row').forEach((row) => {
                        const { wrap: attractionWrap } = getAttractionElements(row);
                        if (attractionWrap && !(target && (target === attractionWrap || attractionWrap.contains(target)))) {
                            hideAttractionDropdown(row);
                        }
                        const { wrap } = getActivityElements(row);
                        if (!wrap) return;
                        if (target && (target === wrap || wrap.contains(target))) return;
                        hideActivityDropdown(row);
                    });
                });
                const closePlannerAutocompleteDropdowns = () => {
                    daySections.querySelectorAll('.schedule-row').forEach((row) => {
                        hideAttractionDropdown(row);
                        hideActivityDropdown(row);
                        hideFnbDropdown(row);
                    });
                };
                itineraryDestinationInput?.addEventListener('input', closePlannerAutocompleteDropdowns);
                itineraryDestinationInput?.addEventListener('change', closePlannerAutocompleteDropdowns);
                standardizeAllDaySectionVisuals();
                wizardStepChips.forEach((chip) => {
                    chip.addEventListener('click', () => {
                        const targetStep = clampWizardStep(chip.dataset.wizardStepChip || WIZARD_STEP_MIN);
                        if (targetStep > wizardStep && wizardStep === 1 && !validateWizardStepOne()) {
                            return;
                        }
                        setWizardStep(targetStep);
                    });
                });
                wizardPrevButton?.addEventListener('click', () => {
                    setWizardStep(wizardStep - 1);
                });
                wizardNextButton?.addEventListener('click', () => {
                    if (wizardStep === 1 && !validateWizardStepOne()) {
                        return;
                    }
                    setWizardStep(wizardStep + 1);
                });
                dayWizardPrevButton?.addEventListener('click', () => {
                    setWizardDay(wizardActiveDay - 1);
                });
                dayWizardNextButton?.addEventListener('click', () => {
                    setWizardDay(wizardActiveDay + 1);
                });
                dayWizardTabs?.addEventListener('click', (event) => {
                    const target = event.target instanceof HTMLElement ? event.target.closest('[data-day-wizard-tab]') : null;
                    if (!target) return;
                    const selectedDay = Number.parseInt(String(target.dataset.dayWizardTab || ''), 10);
                    setWizardDay(selectedDay);
                });
                inquirySelect?.addEventListener('change', updateWizardReview);
                itineraryTitleInput?.addEventListener('input', updateWizardReview);
                itineraryOrderNumberInput?.addEventListener('input', updateWizardReview);
                itineraryDestinationInput?.addEventListener('input', updateWizardReview);
                itineraryDescriptionInput?.addEventListener('input', updateWizardReview);
                itineraryIncludeInput?.addEventListener('input', updateWizardReview);
                itineraryExcludeInput?.addEventListener('input', updateWizardReview);
                durationNightsInput?.addEventListener('input', updateWizardReview);
                daySections.addEventListener('change', updateWizardReview);
                daySections.addEventListener('input', updateWizardReview);
                durationInput.addEventListener('change', () => {
                    const rawDays = parseInt(String(durationInput.value ?? '').trim(), 10);
                    let d = clampDurationDays(Number.isFinite(rawDays) ? rawDays : MIN_DURATION_DAYS);
                    durationInput.value = String(d);
                    syncDurationNights({
                        commitDays: true
                    });
                    let secs = [...daySections.querySelectorAll('.day-section')];
                    for (let i = 1; i <= d; i++) {
                        if (!daySections.querySelector(`.day-section[data-day="${i}"]`) && secs.length) {
                            const c = secs[0].cloneNode(true);
                            resetClonedWysiwyg(c);
                            const sourceStartPointSelect = secs[0].querySelector('.day-start-point-item');
                            const sourceEndPointSelect = secs[0].querySelector('.day-end-point-item');
                            const cloneStartPointSelect = c.querySelector('.day-start-point-item');
                            const cloneEndPointSelect = c.querySelector('.day-end-point-item');
                            clonePointOptions(sourceStartPointSelect, cloneStartPointSelect);
                            clonePointOptions(sourceEndPointSelect, cloneEndPointSelect);
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
                            const cloneDayEndTimeText = c.querySelector('.day-end-time-endpoint-text');
                            if (cloneDayEndTimeText) cloneDayEndTimeText.textContent = '-';
                            const startsAtLabel = c.querySelector('.day-starts-at-label');
                            if (startsAtLabel) startsAtLabel.textContent = 'Not set';
                            const endsAtLabel = c.querySelector('.day-ends-at-label');
                            if (endsAtLabel) endsAtLabel.textContent = 'Not set';
                            const dayStartPointType = c.querySelector('.day-start-point-type');
                            if (dayStartPointType) {
                                dayStartPointType.name = `daily_start_point_types[${i}]`;
                                dayStartPointType.value = '';
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
                            const dayStartBookingSelect = c.querySelector('.day-start-booking-mode');
                            const dayStartBookingWrap = c.querySelector('.day-start-booking-wrap');
                            if (dayStartRoomInput) {
                                dayStartRoomInput.name = `daily_start_point_room_counts[${i}]`;
                                dayStartRoomInput.value = '1';
                                dayStartRoomInput.disabled = true;
                            }
                            if (dayStartBookingSelect) {
                                dayStartBookingSelect.name = `daily_start_hotel_booking_modes[${i}]`;
                                dayStartBookingSelect.value = 'arranged';
                                dayStartBookingSelect.disabled = true;
                            }
                            dayStartRoomWrap?.classList.add('hidden');
                            dayStartBookingWrap?.classList.add('hidden');
                            const dayEndPointType = c.querySelector('.day-end-point-type');
                            if (dayEndPointType) {
                                dayEndPointType.name = `daily_end_point_types[${i}]`;
                                dayEndPointType.value = '';
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
                                dayEndRoomSelect.disabled = true;
                            }
                            const dayEndBookingSelect = c.querySelector('.day-end-booking-mode');
                            if (dayEndBookingSelect) {
                                dayEndBookingSelect.name = `daily_end_hotel_booking_modes[${i}]`;
                                dayEndBookingSelect.value = 'arranged';
                                dayEndBookingSelect.disabled = true;
                            }
                            const dayEndRoomInput = c.querySelector('.day-end-room-count');
                            const dayEndRoomWrap = c.querySelector('.day-end-room-wrap');
                            const dayEndBookingWrap = c.querySelector('.day-end-booking-wrap');
                            if (dayEndRoomInput) {
                                dayEndRoomInput.name = `daily_end_point_room_counts[${i}]`;
                                dayEndRoomInput.value = '1';
                                dayEndRoomInput.disabled = true;
                            }
                            dayEndRoomWrap?.classList.add('hidden');
                            dayEndBookingWrap?.classList.add('hidden');
                            const dayStartTravelInput = c.querySelector('.day-start-travel');
                            if (dayStartTravelInput) {
                                dayStartTravelInput.name = `day_start_travel_minutes[${i}]`;
                                dayStartTravelInput.value = '';
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
                            const dayTransportRows = [...c.querySelectorAll('.day-transport-row')];
                            dayTransportRows.slice(1).forEach((row) => row.remove());
                            const firstTransportRow = c.querySelector('.day-transport-row');
                            if (firstTransportRow) {
                                const firstTransportUnit = firstTransportRow.querySelector('.day-transport-unit');
                                const firstTransportDay = firstTransportRow.querySelector('.day-transport-day');
                                if (firstTransportUnit) {
                                    firstTransportUnit.value = '';
                                }
                                if (firstTransportDay) {
                                    firstTransportDay.value = String(i);
                                }
                            }
                            // Keep "Day Start Point -> first item" connector.
                            // Only remove connectors inside schedule rows so they can be rebuilt safely.
                            c.querySelectorAll('.day-items .travel-connector').forEach((el) => el.remove());
                            const rows = [...c.querySelectorAll('.schedule-row')];
                            rows.slice(1).forEach((r) => r.remove());
                            const r = c.querySelector('.schedule-row');
                            if (r) {
                                r.querySelector('.item-day').value = String(i);
                                resetRowAsTemplate(r);
                            }
                            const dayItems = c.querySelector('.day-items');
                            if (dayItems) delete dayItems.dataset.sortableInit;
                            // Reset per-section binding flags copied from cloned day section.
                            delete c.dataset.transportBound;
                            daySections.appendChild(c);
                            c.querySelectorAll('.schedule-row').forEach(bindRow);
                            c.querySelector('.add-item')?.addEventListener('click', () => cloneRow(c, getNextItemType(c)));
                            bindTransportEvents(c);
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
                            c.querySelector('.day-start-booking-mode')?.addEventListener('change',
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
                                recalcNoConnectorRebuildSkipAuto);
                            c.querySelector('.day-start-time')?.addEventListener('change', recalc);
                            initSortable(c);
                            standardizeDaySectionVisual(c);
                        }
                    } [...daySections.querySelectorAll('.day-section')].forEach((s) => {
                        if (Number(s.dataset.day) > d) s.remove();
                    });
                    standardizeAllDaySectionVisuals();
                    reindexTransportRows();
                    recalc();
                    syncWizardAfterDurationChange();
                });
                durationInput.addEventListener('blur', () => {
                    syncDurationNights({
                        commitDays: true
                    });
                });
                durationInput.addEventListener('input', () => {
                    syncDurationNights({
                        commitDays: true
                    });
                });
                durationNightsInput?.addEventListener('change', syncDurationNights);
                durationNightsInput?.addEventListener('input', syncDurationNights);
                syncDurationNights({
                    commitDays: true
                });
                refreshRequiredAsterisks();
                form?.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    try {
                        await autoFillAllTravelMinutesFromMap();
                    } catch (error) {
                        if (error?.name !== 'AbortError') {
                            console.warn('Auto travel-time recalculation failed before submit.', error);
                        }
                    }
                    await recalcAll();
                    reindex();
                    syncHotelStaysHidden();
                    clearEndPointValidationState();
                    form.submit();
                });
                recalc();
                const initialWizardStep = (() => {
                    const firstError = wizardRoot?.querySelector('[data-wizard-step] .text-rose-600');
                    if (!firstError) return WIZARD_STEP_MIN;
                    const parentStep = firstError.closest('[data-wizard-step]');
                    return parentStep ? clampWizardStep(parentStep.dataset.wizardStep || WIZARD_STEP_MIN) : WIZARD_STEP_MIN;
                })();
                setWizardStep(initialWizardStep);
                syncWizardAfterDurationChange();
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
                const matchesRegion = (option, regionKeyword) => {
                    if (!regionKeyword) return true;
                    const city = normalize(option.dataset.city);
                    const province = normalize(option.dataset.province);
                    const location = normalize(option.dataset.location);
                    return city.includes(regionKeyword)
                        || province.includes(regionKeyword)
                        || location.includes(regionKeyword);
                };

                const applyFilterToSelect = (select) => {
                    if (!select) return;
                    const keyword = normalize(destinationInput.value);
                    const row = select.closest('.schedule-row');
                    const regionKeyword = normalize(row?.querySelector('.item-region')?.value || '');
                    const selectedValue = select.value;
                    Array.from(select.options).forEach((option, idx) => {
                        if (idx === 0) {
                            option.hidden = false;
                            return;
                        }
                        const selected = option.value === selectedValue;
                        option.hidden = !(matchesDestination(option, keyword) && matchesRegion(option,
                            regionKeyword)) && !selected;
                    });
                };

                const applyDestinationFilter = () => {
                    document.querySelectorAll(
                            '.item-attraction, .item-activity, .item-transfer, .item-fnb, .day-start-point-item, .day-end-point-item, .day-transport-unit'
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
                    if (event.target.matches('.day-start-point-type, .day-end-point-type, .item-region, .item-type')) {
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

