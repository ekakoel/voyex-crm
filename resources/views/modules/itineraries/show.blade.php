@extends('layouts.master')

@section('page_title', ui_phrase('Itinerary Detail'))
@section('page_subtitle', ui_phrase('Review complete itinerary information.'))
@section('page_actions')
    @php
        $hasRenderableItineraryItems = $itinerary->touristAttractions->isNotEmpty()
            || $itinerary->itineraryActivities->isNotEmpty()
            || $itinerary->itineraryIslandTransfers->isNotEmpty()
            || $itinerary->itineraryFoodBeverages->isNotEmpty()
            || $itinerary->itineraryTransportUnits->isNotEmpty();
        $canGenerateQuotation = Route::has('quotations.create')
            && auth()->user()?->can('module.quotations.access')
            && ! $itinerary->trashed()
            && $hasRenderableItineraryItems;
    @endphp
    @if (! $itinerary->trashed())
        <x-ui.confirm-action
            :action="route('itineraries.duplicate', $itinerary)"
            method="POST"
            :modal-name="'itinerary-duplicate-' . $itinerary->id"
            :title="ui_phrase('Duplicate Itinerary')"
            :message="ui_phrase('confirm duplicate')"
            :impact-title="__('confirm.what_will_happen')"
            :impact-items="[
                __('confirm.duplicate_itinerary_info_1'),
                __('confirm.duplicate_itinerary_info_2'),
                __('confirm.duplicate_itinerary_info_3'),
            ]"
            :notice-message="__('confirm.notification_after_action')"
            notice-tone="info"
            :confirm-label="ui_phrase('Duplicate')"
            :trigger-label="ui_phrase('Duplicate')"
            trigger-icon="fa-solid fa-copy"
            trigger-class="btn-secondary"
            confirm-class="btn-primary-sm"
        />
    @endif
    @if ($canGenerateQuotation)
        <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}" class="btn-primary">
            <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
            <span>{{ ui_phrase('Generate Quotation') }}</span>
        </a>
    @endif
    @if (auth()->user()?->hasAnyRole(['Reservation', 'Manager', 'Director']))
        <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'stream']) }}" target="_blank" rel="noopener" class="btn-secondary">
            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
            <span>{{ ui_phrase('Generate PDF') }}</span>
        </a>
    @endif
    @can('update', $itinerary)
        <a href="{{ route('itineraries.edit', $itinerary) }}" class="btn-secondary">
            <i class="fa-solid fa-pen" aria-hidden="true"></i>
            <span>{{ ui_phrase('Edit') }}</span>
        </a>
    @endcan
    <a href="{{ route('itineraries.index') }}" class="btn-ghost" data-page-back-action>
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        <span>{{ ui_phrase('Back') }}</span>
    </a>
@endsection

@section('content')
    <div class="space-y-5 itinerary-show-page">
        @php
            $hotelSummaries = collect($hotelSummaries ?? []);
        @endphp

        <div class="module-grid-8-4 min-w-0">
            <div class="module-grid-main">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-5">
                    <div class="app-card p-5 xl:col-span-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ ui_phrase('Itinerary Detail') }}</h2>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $itinerary->duration_days }}D{{ $itinerary->duration_nights > 0 ? '/' . $itinerary->duration_nights . 'N' : '' }}</span>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                            <div class="rounded-lg border border-slate-200/80 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/40">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">{{ ui_phrase('Title') }}</p>
                                <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $itinerary->title ?: '-' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/40">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">{{ ui_phrase('Destination') }}</p>
                                <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $itinerary->destination ?: '-' }}</p>
                            </div>
                        </div>
                        @if ($itinerary->description)
                            <div class="mt-4 rounded-xl border border-slate-200/80 bg-white px-3 py-3 dark:border-slate-700 dark:bg-slate-900/40">
                                <x-rich-text :content="$itinerary->description" class="text-sm text-gray-700 dark:text-gray-200" />
                            </div>
                        @endif
                    </div>
                    <div class="app-card p-5 xl:col-span-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ ui_phrase('Hotels') }}</h2>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ $hotelSummaries->count() }} {{ ui_phrase('items') }}</span>
                        </div>
                        @if ($hotelSummaries->isNotEmpty())
                            <div class="mt-3 space-y-2">
                                @foreach($hotelSummaries as $hotelSummary)
                                    <div class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm shadow-sm dark:border-slate-700">
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $hotelSummary['hotel_name'] ?: ui_phrase('Hotel') }}</p>
                                        @if (!empty($hotelSummary['address_label']) && ($hotelSummary['address_label'] ?? '-') !== '-')
                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('Address') }}: {{ $hotelSummary['address_label'] }}</p>
                                        @endif
                                        @if (!empty($hotelSummary['room_label']))
                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('Room') }}: {{ $hotelSummary['room_label'] }}</p>
                                        @endif
                                        @if (!empty($hotelSummary['area_label']))
                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('Area') }}: {{ $hotelSummary['area_label'] }}</p>
                                        @endif
                                        <p class="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-300">
                                            {{ $hotelSummary['day_label'] ?? '-' }} | {{ (int) ($hotelSummary['night_count'] ?? 0) }} {{ ((int) ($hotelSummary['night_count'] ?? 0) > 1) ? ui_phrase('nights') : ui_phrase('night') }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                            {{ ui_phrase('Booking mode') }}: {{ $hotelSummary['booking_mode_label'] ?? ui_phrase('Hotel arranged by us') }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('No hotel has been configured for this itinerary yet.') }}</p>
                        @endif
                    </div>
                </div>
                <div class="app-card min-w-0 p-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ ui_phrase('Schedule by Day') }}</h2>
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
                        $resolveMealSessionLabels = static function ($mealType, $mealPeriod): array {
                            $tokens = [];
                            foreach ([$mealType, $mealPeriod] as $value) {
                                $tokens = array_merge($tokens, \App\Models\FoodBeverage::normalizeMealPeriodTokens($value));
                            }
                            $tokens = array_values(array_unique($tokens));

                            $labels = [];
                            foreach (\App\Models\FoodBeverage::mealPeriodOptions() as $key => $label) {
                                if (in_array($key, $tokens, true)) {
                                    $labels[] = ui_phrase($label);
                                }
                            }

                            return $labels;
                        };
                        $composeRegionCity = static function ($city, $province): string {
                            $segments = array_values(array_filter([
                                trim((string) ($city ?? '')),
                                trim((string) ($province ?? '')),
                            ], static fn ($value) => $value !== ''));

                            return $segments !== [] ? implode(', ', $segments) : '-';
                        };
                        $resolvePointLabel = function ($dayPoint, string $scope, string $previousDayEndLabel = null) {
                            $defaultNotSet = ui_phrase('Not set');
                            if (!$dayPoint) {
                                return $defaultNotSet;
                            }
                            if ($scope === 'start') {
                                $type = (string) ($dayPoint->start_point_type ?? '');
                                if ($type === 'previous_day_end') {
                                    return $previousDayEndLabel ?: $defaultNotSet;
                                }
                                if ($type === 'airport') {
                                    return $dayPoint->startAirport?->name ?: $defaultNotSet;
                                }
                                if ($type === 'hotel') {
                                    $startBookingMode = (string) ($dayPoint->start_hotel_booking_mode ?? '');
                                    $isSelfBookedStartHotel = strtolower(trim($startBookingMode)) === 'self';
                                    $hotelName = (string) ($dayPoint->startHotel?->name ?? $defaultNotSet);
                                    $roomName = (string) ($dayPoint->startHotelRoom?->rooms ?? '');
                                    $startArea = trim((string) ($dayPoint->start_hotel_area ?? ''));
                                    if ($isSelfBookedStartHotel && $startArea !== '') {
                                        return ui_phrase('Self-booked hotel') . ' - ' . $startArea;
                                    }
                                    if ($hotelName === $defaultNotSet) {
                                        return $defaultNotSet;
                                    }
                                    if ($roomName !== '') {
                                        return $hotelName . ' - ' . $roomName;
                                    }
                                    return $hotelName;
                                }
                                return $defaultNotSet;
                            }
                            $type = (string) ($dayPoint->end_point_type ?? '');
                            if ($type === 'airport') {
                                return $dayPoint->endAirport?->name ?: $defaultNotSet;
                            }
                            if ($type === 'hotel') {
                                $endBookingMode = (string) ($dayPoint->end_hotel_booking_mode ?? '');
                                $isSelfBookedEndHotel = strtolower(trim($endBookingMode)) === 'self';
                                $hotelName = (string) ($dayPoint->endHotel?->name ?? $defaultNotSet);
                                $roomName = (string) ($dayPoint->endHotelRoom?->rooms ?? '');
                                $endArea = trim((string) ($dayPoint->end_hotel_area ?? ''));
                                if ($isSelfBookedEndHotel && $endArea !== '') {
                                    return ui_phrase('Self-booked hotel') . ' - ' . $endArea;
                                }
                                if ($hotelName === $defaultNotSet) {
                                    return $defaultNotSet;
                                }
                                if ($roomName !== '') {
                                    return $hotelName . ' - ' . $roomName;
                                }
                                return $hotelName;
                            }
                            return $defaultNotSet;
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
                                    $startBookingMode = (string) ($dayPoint->start_hotel_booking_mode ?? '');
                                    $isSelfBookedStartHotel = strtolower(trim($startBookingMode)) === 'self';
                                    if ($isSelfBookedStartHotel) {
                                        $startArea = trim((string) ($dayPoint->start_hotel_area ?? ''));
                                        return $startArea !== '' ? $startArea : null;
                                    }
                                    $hotelAddress = (string) ($dayPoint->startHotel?->address ?? '');
                                    if (trim($hotelAddress) !== '') {
                                        return $hotelAddress;
                                    }
                                    $startArea = trim((string) ($dayPoint->start_hotel_area ?? ''));
                                    return $startArea !== '' ? $startArea : null;
                                }
                                return null;
                            }
                            $type = (string) ($dayPoint->end_point_type ?? '');
                            if ($type === 'airport') {
                                return $dayPoint->endAirport?->location;
                            }
                            if ($type === 'hotel') {
                                $endBookingMode = (string) ($dayPoint->end_hotel_booking_mode ?? '');
                                $isSelfBookedEndHotel = strtolower(trim($endBookingMode)) === 'self';
                                if ($isSelfBookedEndHotel) {
                                    $endArea = trim((string) ($dayPoint->end_hotel_area ?? ''));
                                    return $endArea !== '' ? $endArea : null;
                                }
                                $hotelAddress = (string) ($dayPoint->endHotel?->address ?? '');
                                if (trim($hotelAddress) !== '') {
                                    return $hotelAddress;
                                }
                                $endArea = trim((string) ($dayPoint->end_hotel_area ?? ''));
                                return $endArea !== '' ? $endArea : null;
                            }
                            return null;
                        };
                        $resolveAirportCoverUrl = static function ($airport): ?string {
                            if (! $airport) {
                                return null;
                            }
                            return \App\Support\ImageThumbnailGenerator::resolvePublicUrl(
                                (string) ($airport->cover ?? ''),
                                ['airports/covers', 'airports/cover'],
                                'public',
                                360,
                                240
                            );
                        };
                        $resolveHotelCoverUrl = static function ($hotel): ?string {
                            if (! $hotel) {
                                return null;
                            }
                            if (! filled($hotel->cover ?? null)) {
                                return null;
                            }
                            $coverPath = (string) ($hotel->cover ?? '');
                            $originalUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl(
                                $coverPath,
                                ['hotels/covers', 'hotels/cover'],
                                'public'
                            );
                            if (filled($originalUrl)) {
                                return $originalUrl;
                            }

                            return \App\Support\ImageThumbnailGenerator::resolvePublicUrl(
                                $coverPath,
                                ['hotels/covers', 'hotels/cover'],
                                'public',
                                360,
                                240
                            );
                        };
                    @endphp
                    @if ((int) $itinerary->duration_days > 1)
                        <div class="mb-3">
                            <div id="itinerary-schedule-day-tabs" class="app-tabs mt-2" role="tablist" aria-label="{{ ui_phrase('Schedule by Day') }}">
                                @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                                    <button
                                        type="button"
                                        id="itinerary-schedule-day-tab-{{ $day }}"
                                        data-day="{{ $day }}"
                                        class="app-tab itinerary-schedule-day-tab {{ $day === 1 ? 'is-active' : '' }}"
                                        role="tab"
                                        aria-selected="{{ $day === 1 ? 'true' : 'false' }}"
                                        aria-controls="itinerary-schedule-day-panel-{{ $day }}"
                                        tabindex="{{ $day === 1 ? '0' : '-1' }}">
                                        Day {{ $day }}
                                    </button>
                                @endfor
                            </div>
                        </div>
                    @endif
                    @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                        @php
                            $attractions = collect();
                            foreach (($dayGroups[$day] ?? collect()) as $attraction) {
                                $attractionFirstImage = is_array($attraction->gallery_images ?? null) ? ($attraction->gallery_images[0] ?? null) : null;
                                $attractions->push([
                                    'type' => 'attraction',
                                    'experience_id' => (int) $attraction->id,
                                    'name' => $attraction->name,
                                    'map_focus_key' => 'day-' . $day . '-attraction-' . (int) $attraction->id . '-order-' . (int) ($attraction->pivot->visit_order ?? 999999),
                                    'region_city' => $composeRegionCity($attraction->city ?? null, $attraction->province ?? null),
                                    'destination_label' => (string) ($attraction->destination?->name ?? ''),
                                    'location' => $attraction->location,
                                    'description' => $attraction->description,
                                    'thumbnail_url' => $attractionFirstImage ? \App\Support\ImageThumbnailGenerator::resolvePublicUrl($attractionFirstImage) : null,
                                    'pax' => null,
                                    'start_time' => $attraction->pivot->start_time,
                                    'end_time' => $attraction->pivot->end_time,
                                    'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next,
                                    'visit_order' => $attraction->pivot->visit_order ?? 999999,
                                ]);
                            }
                            $activityItemsForDay = collect();
                            foreach (($activityDayGroups[$day] ?? collect()) as $activityItem) {
                                $activity = $activityItem->activity;
                                $activityVendor = $activity?->vendor;
                                $activityFirstImage = is_array($activity?->gallery_images ?? null) ? ($activity->gallery_images[0] ?? null) : null;
                                $activityItemsForDay->push([
                                    'type' => 'activity',
                                    'experience_id' => (int) ($activityItem->activity_id ?? 0),
                                    'name' => $activity->name ?? '-',
                                    'vendor_name' => $activityVendor?->name ?? '-',
                                    'map_focus_key' => 'day-' . $day . '-activity-' . (int) ($activityItem->activity_id ?? 0) . '-order-' . (int) ($activityItem->visit_order ?? 999999),
                                    'region_city' => $composeRegionCity($activityVendor?->city ?? null, $activityVendor?->province ?? null),
                                    'destination_label' => (string) ($activityVendor?->destination?->name ?? ''),
                                    'location' => $activityVendor?->location ?? null,
                                    'description' => $activity->notes ?? null,
                                    'thumbnail_url' => $activityFirstImage ? \App\Support\ImageThumbnailGenerator::resolvePublicUrl($activityFirstImage) : null,
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
                            $transferItemsForDay = collect();
                            foreach (($islandTransferDayGroups[$day] ?? collect()) as $transferItem) {
                                $transfer = $transferItem->islandTransfer;
                                $transferVendor = $transfer?->vendor;
                                $transferFirstImage = is_array($transfer->gallery_images ?? null) ? ($transfer->gallery_images[0] ?? null) : null;
                                $transferItemsForDay->push([
                                    'type' => 'transfer',
                                    'experience_id' => (int) ($transferItem->island_transfer_id ?? 0),
                                    'name' => $transfer->name ?? '-',
                                    'vendor_name' => $transferVendor?->name ?? '-',
                                    'map_focus_key' => 'day-' . $day . '-transfer-' . (int) ($transferItem->island_transfer_id ?? 0) . '-order-' . (int) ($transferItem->visit_order ?? 999999),
                                    'region_city' => $composeRegionCity($transferVendor?->city ?? null, $transferVendor?->province ?? null),
                                    'destination_label' => (string) ($transferVendor?->destination?->name ?? ''),
                                    'location' => trim((string) (($transfer->departure_point_name ?? '-') . ' -> ' . ($transfer->arrival_point_name ?? '-'))),
                                    'description' => $transfer->notes ?? null,
                                    'thumbnail_url' => $transferFirstImage ? \App\Support\ImageThumbnailGenerator::resolvePublicUrl($transferFirstImage) : null,
                                    'pax' => $transferItem->pax,
                                    'start_time' => $transferItem->start_time,
                                    'end_time' => $transferItem->end_time,
                                    'travel_minutes_to_next' => $transferItem->travel_minutes_to_next,
                                    'visit_order' => $transferItem->visit_order ?? 999999,
                                ]);
                            }
                            $foodBeverages = collect();
                            foreach (($foodBeverageDayGroups[$day] ?? collect()) as $foodBeverageItem) {
                                $foodBeverage = $foodBeverageItem->foodBeverage;
                                $foodBeverageVendor = $foodBeverage?->vendor;
                                $foodBeverageFirstImage = is_array($foodBeverage?->gallery_images ?? null) ? ($foodBeverage->gallery_images[0] ?? null) : null;
                                $mealType = $foodBeverageItem->meal_type ?? null;
                                $mealPeriod = $foodBeverage->meal_period ?? null;
                                $foodBeverages->push([
                                    'type' => 'fnb',
                                    'experience_id' => (int) ($foodBeverageItem->food_beverage_id ?? 0),
                                    'name' => $foodBeverage->name ?? '-',
                                    'vendor_name' => $foodBeverageVendor?->name ?? '-',
                                    'map_focus_key' => 'day-' . $day . '-fnb-' . (int) ($foodBeverageItem->food_beverage_id ?? 0) . '-order-' . (int) ($foodBeverageItem->visit_order ?? 999999),
                                    'region_city' => $composeRegionCity($foodBeverageVendor?->city ?? null, $foodBeverageVendor?->province ?? null),
                                    'destination_label' => (string) ($foodBeverageVendor?->destination?->name ?? ''),
                                    'location' => $foodBeverageVendor?->location ?? null,
                                    'thumbnail_url' => $foodBeverageFirstImage ? \App\Support\ImageThumbnailGenerator::resolvePublicUrl($foodBeverageFirstImage) : null,
                                    'description' => $foodBeverage->notes ?? $foodBeverage->menu_highlights ?? null,
                                    'menu_highlights' => $foodBeverage->menu_highlights ?? null,
                                    'meal_type' => $mealType ?: $mealPeriod,
                                    'meal_period' => $mealPeriod,
                                    'meal_sessions' => $resolveMealSessionLabels($mealType, $mealPeriod),
                                    'service_type' => $foodBeverage->service_type ?? null,
                                    'publish_rate' => $foodBeverage->adult_publish_rate ?? $foodBeverage->publish_rate ?? null,
                                    'currency' => 'IDR',
                                    'pax' => $foodBeverageItem->pax,
                                    'start_time' => $foodBeverageItem->start_time,
                                    'end_time' => $foodBeverageItem->end_time,
                                    'travel_minutes_to_next' => $foodBeverageItem->travel_minutes_to_next,
                                    'visit_order' => $foodBeverageItem->visit_order ?? 999999,
                                ]);
                            }
                            $dayItems = $attractions->merge($activityItemsForDay)->merge($transferItemsForDay)->merge($foodBeverages)->sortBy('visit_order')->values();
                            $dayTransports = $transportUnitsByDay[$day] ?? collect();
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
                                : ($day > 1 ? $previousEndLabel : ui_phrase('Not set'));
                            $endPointLabel = $resolvePointLabel($dayPoint, 'end');
                            $startPointLocation = $dayPoint
                                ? $resolvePointLocation($dayPoint, 'start', $previousEndLocation)
                                : ($day > 1 ? $previousEndLocation : null);
                            $endPointLocation = $resolvePointLocation($dayPoint, 'end');
                            $startPointImageUrl = $startPointTypeRaw === 'airport'
                                ? $resolveAirportCoverUrl($dayPoint?->startAirport)
                                : ($startPointType === 'hotel' ? $resolveHotelCoverUrl($dayPoint?->startHotel) : null);
                            $endPointImageUrl = $endPointType === 'airport'
                                ? $resolveAirportCoverUrl($dayPoint?->endAirport)
                                : ($endPointType === 'hotel' ? $resolveHotelCoverUrl($dayPoint?->endHotel) : null);
                            $startPointTypeLabel = $startPointType === 'airport' ? ui_phrase('Airport') : ui_phrase('Hotel');
                            $endPointTypeLabel = $endPointType === 'airport' ? ui_phrase('Airport') : ui_phrase('Hotel');
                            $firstItem = $dayItems->first();
                            $lastItem = $dayItems->last();
                            $dayStartTime = $dayPoint && !empty($dayPoint->day_start_time)
                                ? substr((string) $dayPoint->day_start_time, 0, 5)
                                : (!empty($firstItem['start_time']) ? substr((string) $firstItem['start_time'], 0, 5) : null);
                            $breakStartTime = $dayPoint && !empty($dayPoint->break_start_time)
                                ? substr((string) $dayPoint->break_start_time, 0, 5)
                                : null;
                            $breakEndTime = $dayPoint && !empty($dayPoint->break_end_time)
                                ? substr((string) $dayPoint->break_end_time, 0, 5)
                                : null;
                            $hasBreakSlot = filled($breakStartTime) && filled($breakEndTime);
                            $breakInsertAfterIndex = null;
                            if ($hasBreakSlot && $dayItems->isNotEmpty()) {
                                $breakStartMinutes = $toMinutes($breakStartTime);
                                $breakEndMinutes = $toMinutes($breakEndTime);
                                if ($breakStartMinutes !== null && $breakEndMinutes !== null && $breakEndMinutes > $breakStartMinutes) {
                                    foreach ($dayItems as $breakIndex => $breakItem) {
                                        $itemEndMinutes = $toMinutes($breakItem['end_time'] ?? null);
                                        if ($itemEndMinutes === null || $breakStartMinutes < $itemEndMinutes) {
                                            continue;
                                        }
                                        $nextItem = $dayItems->get($breakIndex + 1);
                                        $nextStartMinutes = $nextItem ? $toMinutes($nextItem['start_time'] ?? null) : null;
                                        $breakInsertAfterIndex = $breakIndex;
                                        if ($nextStartMinutes === null || $breakStartMinutes <= $nextStartMinutes) {
                                            break;
                                        }
                                    }
                                    if ($breakInsertAfterIndex === null) {
                                        $breakInsertAfterIndex = max(0, $dayItems->count() - 1);
                                    }
                                }
                            }
                            $dayStartTravelMinutes = $dayPoint && $dayPoint->day_start_travel_minutes !== null
                                ? max(0, (int) $dayPoint->day_start_travel_minutes)
                                : null;
                            $startBookingMode = (string) ($dayPoint?->start_hotel_booking_mode ?? '');
                            if ($startPointTypeRaw === 'previous_day_end' && $startPointType === 'hotel') {
                                $startBookingMode = (string) ($previousDayPoint?->end_hotel_booking_mode ?? $startBookingMode);
                            }
                            if (!in_array($startBookingMode, ['arranged', 'self'], true)) {
                                $startBookingMode = 'arranged';
                            }
                            $startBookingModeLabel = $startBookingMode === 'self'
                                ? ui_phrase('Self-booked hotel')
                                : ui_phrase('Hotel arranged by us');
                            $endBookingModeLabel = ((string) ($dayPoint?->end_hotel_booking_mode ?? 'arranged')) === 'self'
                                ? ui_phrase('Self-booked hotel')
                                : ui_phrase('Hotel arranged by us');
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
                            $startBaseMinutes = $toMinutes($dayStartTime ?? null);
                            $dayEndTime = $lastEndBaseMinutes !== null
                                ? $fromMinutes($lastEndBaseMinutes + $lastTravelToEnd)
                                : ($startBaseMinutes !== null
                                    ? $fromMinutes($startBaseMinutes + max(0, (int) ($dayStartTravelMinutes ?? 0)))
                                    : null);
                            $startTimeSet = filled((string) ($dayPoint?->day_start_time ?? ''));
                            $startConfigured = false;
                            if ($dayPoint) {
                                if ($startPointTypeRaw === 'airport') {
                                    $startConfigured = (int) ($dayPoint->start_airport_id ?? 0) > 0;
                                } elseif ($startPointTypeRaw === 'hotel') {
                                    $startBookingModeNormalized = strtolower(trim((string) ($dayPoint->start_hotel_booking_mode ?? '')));
                                    $isSelfBookedStartHotel = $startBookingModeNormalized === 'self';
                                    $startConfigured = (int) ($dayPoint->start_hotel_id ?? 0) > 0
                                        || ($isSelfBookedStartHotel && trim((string) ($dayPoint->start_hotel_area ?? '')) !== '');
                                } elseif ($startPointTypeRaw === 'previous_day_end') {
                                    $previousEndType = (string) ($previousDayPoint->end_point_type ?? '');
                                    $previousEndBookingModeNormalized = strtolower(trim((string) ($previousDayPoint->end_hotel_booking_mode ?? '')));
                                    $previousEndIsSelfBookedHotel = $previousEndType === 'hotel' && $previousEndBookingModeNormalized === 'self';
                                    $startConfigured = $day > 1
                                        && $previousDayPoint
                                        && in_array($previousEndType, ['airport', 'hotel'], true)
                                        && (
                                            ($previousEndType === 'airport' && (int) ($previousDayPoint->end_airport_id ?? 0) > 0)
                                            || ($previousEndType === 'hotel' && (
                                                (int) ($previousDayPoint->end_hotel_id ?? 0) > 0
                                                || ($previousEndIsSelfBookedHotel && trim((string) ($previousDayPoint->end_hotel_area ?? '')) !== '')
                                            ))
                                        );
                                }
                            }
                            $endConfigured = false;
                            if ($dayPoint) {
                                if ($endPointType === 'airport') {
                                    $endConfigured = (int) ($dayPoint->end_airport_id ?? 0) > 0;
                                } elseif ($endPointType === 'hotel') {
                                    $endBookingModeNormalized = strtolower(trim((string) ($dayPoint->end_hotel_booking_mode ?? '')));
                                    $isSelfBookedEndHotel = $endBookingModeNormalized === 'self';
                                    $endConfigured = (int) ($dayPoint->end_hotel_id ?? 0) > 0
                                        || ($isSelfBookedEndHotel && trim((string) ($dayPoint->end_hotel_area ?? '')) !== '');
                                }
                            }
                            $incompleteReasons = [];
                            if (! $startTimeSet) {
                                $incompleteReasons[] = ui_phrase('Day start time is not set.');
                            }
                            if (! $startConfigured) {
                                $incompleteReasons[] = ui_phrase('Start point is not fully configured.');
                            }
                            if (! $endConfigured) {
                                $incompleteReasons[] = ui_phrase('End point is not fully configured.');
                            }
                            $isDayComplete = $incompleteReasons === [];
                        @endphp
                        <div
                            id="itinerary-schedule-day-panel-{{ $day }}"
                            data-itinerary-schedule-day-panel="{{ $day }}"
                            @if ((int) $itinerary->duration_days > 1)
                                role="tabpanel"
                                aria-labelledby="itinerary-schedule-day-tab-{{ $day }}"
                            @endif
                            @if ((int) $itinerary->duration_days > 1 && $day !== 1) hidden @endif>
                            <div class="app-day-header">
                                <p class="app-day-header-title">Day {{ $day }}</p>
                                <p class="app-day-header-meta">
                                    {{ ui_phrase('Start Tour') }}: {{ $dayStartTime ?? '--:--' }} | {{ ui_phrase('End Tour') }}: {{ $dayEndTime ?? '--:--' }}
                                </p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ ui_phrase('Break Time') }}:
                                    {{ $breakStartTime && $breakEndTime ? $breakStartTime . ' - ' . $breakEndTime : '-' }}
                                </p>
                            </div>

                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('Starts at') }}: {{ $startPointLabel ?: ui_phrase('Not set') }} | {{ ui_phrase('Ends at') }}: {{ $endPointLabel ?: ui_phrase('Not set') }}
                            </p>
                            @if (! $isDayComplete)
                                <div class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5 text-xs text-amber-800 dark:border-amber-700/60 dark:bg-amber-900/20 dark:text-amber-200">
                                    <p class="font-semibold">{{ ui_phrase('Incomplete reasons') }}:</p>
                                    @foreach ($incompleteReasons as $reason)
                                        <p>- {{ $reason }}</p>
                                    @endforeach
                                </div>
                            @endif
                            <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50/70 px-2.5 py-2 dark:border-gray-700 dark:bg-gray-900/30">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Transport Unit') }}</p>
                                @if ($dayTransports->isNotEmpty())
                                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach ($dayTransports as $transportRow)
                                            @php
                                                $transportUnit = $transportRow->transportUnit;
                                                $transportSource = $transportUnit;
                                                $transportImages = $transportSource?->images ?? [];
                                                if ((!is_array($transportImages) || $transportImages === []) && $transportUnit?->transport) {
                                                    $transportSource = $transportUnit->transport;
                                                    $transportImages = $transportSource?->images ?? [];
                                                }
                                                if (is_string($transportImages)) {
                                                    $decodedTransportImages = json_decode($transportImages, true);
                                                    $transportImages = is_array($decodedTransportImages) ? $decodedTransportImages : [];
                                                }
                                                if (! is_array($transportImages)) {
                                                    $transportImages = [];
                                                }
                                                $transportImages = array_values(array_filter($transportImages, fn ($path) => is_string($path) && trim($path) !== ''));
                                                $transportImageUrl = null;
                                                $transportImageOriginalUrl = null;
                                                foreach ($transportImages as $transportImagePath) {
                                                    $normalizedTransportImagePath = ltrim(str_replace('\\', '/', (string) $transportImagePath), '/');
                                                    if (\Illuminate\Support\Str::startsWith($normalizedTransportImagePath, 'storage/')) {
                                                        $normalizedTransportImagePath = \Illuminate\Support\Str::after($normalizedTransportImagePath, 'storage/');
                                                    }
                                                    $thumbnailPath = \App\Support\ImageThumbnailGenerator::thumbnailPathFor($normalizedTransportImagePath);
                                                    $thumbnailUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($normalizedTransportImagePath);
                                                    if (! filled($thumbnailUrl) && \Illuminate\Support\Facades\Storage::disk('public')->exists($thumbnailPath)) {
                                                        $thumbnailUrl = '/storage/' . ltrim($thumbnailPath, '/');
                                                    }
                                                    $fullUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($normalizedTransportImagePath) ?: (filled($normalizedTransportImagePath) ? '/storage/' . ltrim($normalizedTransportImagePath, '/') : null);
                                                    $transportImageUrl = $thumbnailUrl ?: $fullUrl;
                                                    $transportImageOriginalUrl = $fullUrl ?: $thumbnailUrl;
                                                    if (filled($transportImageUrl)) {
                                                        break;
                                                    }
                                                }
                                                $transportTypeLabel = trim((string) ($transportSource?->transport_type ?? 'Transport'));
                                                $transportTypeDisplay = ui_phrase($transportTypeLabel !== '' ? $transportTypeLabel : 'transport');
                                                $transportName = trim((string) ($transportSource?->name ?? $transportUnit?->name ?? '-'));
                                                $transportNameDisplay = $transportName !== '' ? ui_phrase($transportName) : '-';
                                                $transportModel = trim((string) ($transportSource?->brand_model ?? ''));
                                                $seatCapacity = $transportSource?->seat_capacity !== null ? (int) $transportSource->seat_capacity : null;
                                                $luggageCapacity = $transportSource?->luggage_capacity !== null ? (int) $transportSource->luggage_capacity : null;
                                                $airConditioned = $transportSource?->air_conditioned === null ? null : (bool) $transportSource->air_conditioned;
                                                $withDriver = $transportSource?->with_driver === null ? null : (bool) $transportSource->with_driver;
                                            @endphp
                                            <div class="rounded-md border border-gray-200 bg-white px-2 py-1.5 dark:border-gray-700 dark:bg-gray-900/50">
                                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-[7rem_minmax(0,1fr)]">
                                                    <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60">
                                                        <div class="relative aspect-[16/9] h-full overflow-hidden">
                                                            @if (filled($transportImageUrl))
                                                                <img
                                                                    src="{{ $transportImageUrl }}"
                                                                    alt="{{ ui_phrase('thumbnail alt', ['name' => ($transportNameDisplay !== '' ? $transportNameDisplay : '-')]) }}"
                                                                    class="absolute inset-0 block h-full w-full object-cover object-center"
                                                                    @if (filled($transportImageOriginalUrl))
                                                                        onerror="if(this.dataset.fallbackApplied){this.onerror=null;}else{this.dataset.fallbackApplied='1';this.src='{{ $transportImageOriginalUrl }}';}"
                                                                    @endif
                                                                >
                                                            @else
                                                                <div class="flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-500">
                                                                    <i class="fa-solid fa-car-side"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $transportTypeDisplay !== '' ? $transportTypeDisplay : ui_phrase('transport') }}</p>
                                                        <p class="mt-0.5 text-xs font-medium text-gray-800 dark:text-gray-100">{{ $transportNameDisplay !== '' ? $transportNameDisplay : '-' }}</p>
                                                        @if ($transportModel !== '')
                                                            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">{{ $transportModel }}</p>
                                                        @endif
                                                        <div class="mt-1 flex flex-wrap gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                            <span>{{ ui_phrase('seat') }}: {{ $seatCapacity !== null ? $seatCapacity : '-' }}</span>
                                                            <span>| {{ ui_phrase('luggage') }}: {{ $luggageCapacity !== null ? $luggageCapacity : '-' }}</span>
                                                            <span>| AC: {{ $airConditioned === null ? '-' : ($airConditioned ? ui_phrase('yes') : ui_phrase('no')) }}</span>
                                                            <span>| {{ ui_phrase('driver') }}: {{ $withDriver === null ? '-' : ($withDriver ? ui_phrase('yes') : ui_phrase('no')) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">-</p>
                                @endif
                            </div>
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
                                            {{ $dayStartTravelMinutes !== null ? $dayStartTravelMinutes : '-' }} {{ ui_phrase('minute') }}
                                        </span>
                                        <span class="timeline-travel-spacer"></span>
                                    </div>
                                    <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                    <div
                                        class="js-itinerary-map-focus interactive-selectable ml-2 flex-1 cursor-pointer rounded-lg border border-gray-200 px-2 py-2 hover:bg-gray-50/70 dark:border-gray-700 dark:hover:bg-gray-800/30"
                                        data-day="{{ $day }}"
                                        data-map-focus-key="day-{{ $day }}-start-point"
                                        data-interactive-selectable="true"
                                        role="button"
                                        tabindex="0"
                                        aria-label="{{ ui_phrase('Focus map') }}: {{ $startPointLabel ?: ui_phrase('Start Point') }}">
                                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[9rem_minmax(0,1fr)]">
                                                <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60">
                                                    <div class="relative aspect-[16/9] h-full overflow-hidden">
                                                    @if (filled($startPointImageUrl))
                                                        <img src="{{ $startPointImageUrl }}" alt="{{ ui_phrase('Start Point') }}" class="absolute inset-0 block h-full w-full object-cover object-center">
                                                    @else
                                                        <div class="flex h-full w-full flex-col items-center justify-center gap-1 text-gray-500 dark:text-gray-300">
                                                            <i class="{{ $startPointType === 'airport' ? 'fa-solid fa-plane' : 'fa-solid fa-bed' }} text-sm"></i>
                                                            <span class="text-[10px] font-semibold uppercase tracking-wide">{{ ui_phrase('Start Point') }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $startPointTypeLabel }}</p>
                                                <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ $startPointLabel ?: ui_phrase('Not set') }}</span>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $startPointLocation ?: '-' }}
                                                    | {{ ui_phrase('Start Tour') }}: {{ $dayStartTime ?? '--:--' }}
                                                </div>
                                            </div>
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
                                                 class="timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold text-white {{ $item['type'] === 'activity' ? 'bg-emerald-600' : ($item['type'] === 'transfer' ? 'bg-violet-600' : ($item['type'] === 'fnb' ? 'bg-amber-600' : 'bg-indigo-600')) }}">
                                                <i class="{{ $item['type'] === 'activity' ? 'fa-solid fa-person-hiking' : ($item['type'] === 'transfer' ? 'fa-solid fa-ship' : ($item['type'] === 'fnb' ? 'fa-solid fa-utensils' : 'fa-solid fa-location-dot')) }}"></i>
                                            </span>
                                            <span class="timeline-travel-spacer"></span>
                                            <span class="timeline-travel-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 fill-current" focusable="false">
                                                    <path d="M5.5 11.5L7.3 6.9C7.6 6.1 8.3 5.5 9.2 5.5h5.6c.9 0 1.6.6 1.9 1.4l1.8 4.6c1 .2 1.8 1.1 1.8 2.2v2.3c0 .8-.7 1.5-1.5 1.5h-.5a2.3 2.3 0 01-4.6 0h-4.4a2.3 2.3 0 01-4.6 0h-.5c-.8 0-1.5-.7-1.5-1.5v-2.3c0-1.1.8-2 1.8-2.2zm3.1-4.2L7.2 11h9.6l-1.4-3.7a.8.8 0 00-.7-.5H9.3c-.3 0-.6.2-.7.5zM8.2 18.9c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9zm7.6 0c.5 0 .9-.4.9-.9s-.4-.9-.9-.9-.9.4-.9.9.4.9.9.9z"/>
                                                </svg>
                                            </span>
                                            <span class="timeline-travel-spacer"></span>
                                            <span class="timeline-travel-label">
                                                {{ $travelMinutes !== null ? $travelMinutes . ' ' . ui_phrase('minute') : '- ' . ui_phrase('minute') }}
                                            </span>
                                            <span class="timeline-travel-spacer"></span>
                                        </div>
                                            <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                        @php
                                            $itemType = (string) ($item['type'] ?? '');
                                            $itemTypeLabel = $itemType === 'fnb'
                                                ? ui_phrase('food beverages')
                                                : ($itemType === 'transfer'
                                                    ? ui_phrase('item type island transfer')
                                                    : ui_phrase($itemType));
                                            $itemTypeClass = $itemType === 'activity'
                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                : ($itemType === 'transfer'
                                                    ? 'text-violet-600 dark:text-violet-400'
                                                    : ($itemType === 'fnb'
                                                        ? 'text-amber-600 dark:text-amber-400'
                                                        : 'text-indigo-600 dark:text-indigo-400'));
                                            $itemStartTime = $item['start_time'] ? substr((string) $item['start_time'], 0, 5) : '--:--';
                                            $itemEndTime = $item['end_time'] ? substr((string) $item['end_time'], 0, 5) : '--:--';
                                            $itemRegionCity = trim((string) ($item['region_city'] ?? ''));
                                            $itemVendorName = trim((string) ($item['vendor_name'] ?? ''));
                                            $itemDestination = trim((string) ($item['destination_label'] ?? ''));
                                            $itemLocationFallback = trim((string) ($item['location'] ?? ''));
                                            $itemMetaPrimary = $itemRegionCity !== '' && $itemRegionCity !== '-'
                                                ? $itemRegionCity
                                                : ($itemLocationFallback !== '' ? $itemLocationFallback : '-');
                                            $itemMetaSegments = [$itemMetaPrimary];
                                            if ($itemType === 'transfer' && $itemVendorName !== '' && $itemVendorName !== '-') {
                                                $itemMetaSegments[] = $itemVendorName;
                                            }
                                            if ($itemType !== 'transfer' && $itemDestination !== '') {
                                                $itemMetaSegments[] = $itemDestination;
                                            }
                                            $itemMetaLocation = implode(' | ', array_filter($itemMetaSegments, static fn ($value) => trim((string) $value) !== ''));
                                        @endphp
                                        <div
                                            class="js-itinerary-map-focus interactive-selectable ml-2 flex-1 cursor-pointer rounded-lg border px-2 py-2 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 {{ $isMainExperience ? 'border-amber-400 bg-amber-50/70 dark:border-amber-500 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-700' }}"
                                            data-day="{{ $day }}"
                                            data-map-focus-key="{{ (string) ($item['map_focus_key'] ?? '') }}"
                                            data-interactive-selectable="true"
                                            role="button"
                                            tabindex="0"
                                            aria-label="{{ ui_phrase('Focus map') }}: {{ (string) ($item['name'] ?? '-') }}">
                                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[9rem_minmax(0,1fr)]">
                                                <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60">
                                                    <div class="relative aspect-[16/9] h-full overflow-hidden">
                                                        @if (!empty($item['thumbnail_url']))
                                                            <img src="{{ $item['thumbnail_url'] }}" alt="{{ ui_phrase('thumbnail alt', ['name' => ($item['name'] ?: $itemTypeLabel)]) }}" class="absolute inset-0 block h-full w-full object-cover object-center">
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-500">
                                                                <i class="fa-regular fa-image"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[11px] font-semibold uppercase tracking-wide {{ $itemTypeClass }}">{{ $itemTypeLabel }}</p>
                                                    <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $item['name'] ?: '-' }}</span>
                                                        @if (in_array((string) ($item['type'] ?? ''), ['activity', 'fnb'], true) && !empty($item['vendor_name']) && $item['vendor_name'] !== '-')
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">| {{ $item['vendor_name'] }}</span>
                                                        @endif
                                                        @if (($item['type'] ?? '') === 'fnb' && !empty($item['meal_sessions']) && is_array($item['meal_sessions']))
                                                            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                                                                {{ implode('/', $item['meal_sessions']) }}
                                                            </span>
                                                        @endif
                                                        @if ($isMainExperience)
                                                            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">{{ ui_phrase('Main Experience') }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $itemMetaLocation }} | {{ $itemStartTime }} - {{ $itemEndTime }}
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($hasBreakSlot && $breakInsertAfterIndex !== null && $index === $breakInsertAfterIndex)
                                                <div class="mt-2 rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 dark:border-slate-700 dark:bg-slate-800/30">
                                                    <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                                        <i class="fa-solid fa-mug-hot"></i>
                                                        <span>{{ ui_phrase('Break Time') }}</span>
                                                    </div>
                                                    <p class="mt-1 text-xs font-medium text-gray-800 dark:text-gray-100">{{ $breakStartTime }} - {{ $breakEndTime }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </li>
                                @empty
                                    @if ($hasBreakSlot)
                                        <li class="flex items-start gap-0">
                                            <div class="timeline-node-col w-10 flex flex-col items-center">
                                                <span class="timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-500 text-[11px] font-semibold text-white">
                                                    <i class="fa-solid fa-mug-hot"></i>
                                                </span>
                                            </div>
                                            <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                            <div class="ml-2 flex-1 rounded-lg border border-gray-200 bg-slate-50/70 px-2 py-2 dark:border-gray-700 dark:bg-slate-800/30">
                                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[9rem_minmax(0,1fr)]">
                                                    <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60">
                                                        <div class="relative aspect-[16/9] h-full overflow-hidden">
                                                            <div class="flex h-full w-full items-center justify-center text-gray-500 dark:text-gray-300">
                                                                <i class="fa-solid fa-mug-hot text-sm"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ ui_phrase('Break Time') }}</p>
                                                        <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $breakStartTime }} - {{ $breakEndTime }}</span>
                                                        </div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            {{ ui_phrase('Rest time between activities') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endif
                                @endforelse
                                <li class="flex items-start gap-0">
                                    <div class="timeline-node-col w-10 flex flex-col items-center">
                                        <span
                                             class="timeline-node inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold text-white {{ $endPointType === 'airport' ? 'bg-sky-600' : 'bg-teal-600' }}">
                                            <i class="{{ $endPointType === 'airport' ? 'fa-solid fa-plane-arrival' : 'fa-solid fa-bed' }}"></i>
                                        </span>
                                    </div>
                                    <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                    <div
                                        class="js-itinerary-map-focus interactive-selectable ml-2 flex-1 cursor-pointer rounded-lg border border-gray-200 px-2 py-2 hover:bg-gray-50/70 dark:border-gray-700 dark:hover:bg-gray-800/30"
                                        data-day="{{ $day }}"
                                        data-map-focus-key="day-{{ $day }}-end-point"
                                        data-interactive-selectable="true"
                                        role="button"
                                        tabindex="0"
                                        aria-label="{{ ui_phrase('Focus map') }}: {{ $endPointLabel ?: ui_phrase('End Point') }}">
                                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[9rem_minmax(0,1fr)]">
                                            <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60">
                                                <div class="relative aspect-[16/9] h-full overflow-hidden">
                                                    @if (filled($endPointImageUrl))
                                                        <img src="{{ $endPointImageUrl }}" alt="{{ ui_phrase('End Point') }}" class="absolute inset-0 block h-full w-full object-cover object-center">
                                                    @else
                                                        <div class="flex h-full w-full flex-col items-center justify-center gap-1 text-gray-500 dark:text-gray-300">
                                                            <i class="{{ $endPointType === 'airport' ? 'fa-solid fa-plane-arrival' : 'fa-solid fa-bed' }} text-sm"></i>
                                                            <span class="text-[10px] font-semibold uppercase tracking-wide">{{ ui_phrase('End Point') }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $endPointTypeLabel }}</p>
                                                <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ $endPointLabel ?: ui_phrase('Not set') }}</span>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $endPointLocation ?: '-' }}
                                                    | {{ ui_phrase('End Tour') }}: {{ $dayEndTime ?? '--:--' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    @endfor
                    @php
                        $itineraryIncludeText = \App\Support\SafeRichText::plainText($itinerary->itinerary_include);
                        $itineraryExcludeText = \App\Support\SafeRichText::plainText($itinerary->itinerary_exclude);
                        $termConditionsText = \App\Support\SafeRichText::plainText($itinerary->term_conditions);
                    @endphp
                    @if (filled($itineraryIncludeText) || filled($itineraryExcludeText) || filled($termConditionsText))
                        <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                            @if (filled($itineraryIncludeText))
                                <div class="rounded-lg mb-6 border border-gray-200 px-2 py-1 dark:border-gray-700">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-900 dark:text-gray-100">{{ ui_phrase('Itinerary Include') }}</p>
                                    <x-rich-text :content="$itinerary->itinerary_include" class="mt-0.5 text-xs text-gray-900 dark:text-gray-100" />
                                </div>
                            @endif
                            @if (filled($itineraryExcludeText))
                                <div class="rounded-lg mb-6 border border-gray-200 px-2 py-1 dark:border-gray-700">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-900 dark:text-gray-100">{{ ui_phrase('Itinerary Exclude') }}</p>
                                    <x-rich-text :content="$itinerary->itinerary_exclude" class="mt-0.5 text-xs text-gray-900 dark:text-gray-100" />
                                </div>
                            @endif
                            @if (filled($termConditionsText))
                                <div class="rounded-lg mb-6 border border-gray-200 px-2 py-1 dark:border-gray-700 md:col-span-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-900 dark:text-gray-100">{{ ui_phrase('Term & Conditions') }}</p>
                                    <x-rich-text :content="$itinerary->term_conditions" class="mt-0.5 text-xs text-gray-900 dark:text-gray-100" />
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="app-card p-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Activity Timeline') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('detailed audit log') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
                <div class="app-card p-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Related Quotations') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('All quotations generated from this itinerary.') }}</p>
                    </div>
                    <div class="mt-3 space-y-2">
                        @php
                            $linkedQuotations = $itinerary->quotations->values();
                            $canAccessQuotationModule = auth()->user()?->can('module.quotations.access');
                        @endphp
                        @forelse ($linkedQuotations as $quotation)
                            @php
                                $quotationNumberLabel = trim((string) ($quotation->quotation_number ?? ''));
                                if ($quotationNumberLabel === '') {
                                    $quotationNumberLabel = '#'.$quotation->id;
                                }
                                $orderNumberLabel = trim((string) ($quotation->order_number ?? '-'));
                                $quotationUrl = $canAccessQuotationModule ? route('quotations.show', $quotation) : '#';
                            @endphp
                            <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-gray-800 dark:text-gray-100" title="{{ $quotationNumberLabel }}">{{ $quotationNumberLabel }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Order Number') }}: {{ $orderNumberLabel }}</p>
                                    </div>
                                    <a
                                        href="{{ $quotationUrl }}"
                                        class="inline-flex items-center rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 hover:border-indigo-300 hover:text-indigo-700 dark:border-gray-700 dark:text-gray-200 dark:hover:border-indigo-600 dark:hover:text-indigo-300 {{ $canAccessQuotationModule ? '' : 'pointer-events-none opacity-70' }}"
                                        @if (! $canAccessQuotationModule) aria-disabled="true" @endif
                                    >
                                        <i class="fa-solid fa-eye mr-1" aria-hidden="true"></i>{{ ui_phrase('View') }}
                                    </a>
                                </div>
                                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    <div>{{ ui_phrase('Status') }}: {{ ui_phrase((string) ($quotation->status ?? '-')) }}</div>
                                    <div>{{ ui_phrase('Validation') }}: {{ ui_phrase((string) ($quotation->validation_status ?? '-')) }}</div>
                                    <div>{{ ui_phrase('Final Amount') }}: <x-ui.money :amount="(float) ($quotation->final_amount ?? 0)" :currency="$currentCurrency ?? 'IDR'" /></div>
                                    <div>{{ ui_phrase('Created At') }}: <x-local-time :value="$quotation->created_at" /></div>
                                    <div class="col-span-2">{{ ui_phrase('Created by') }}: <x-masked-user-name :user="$quotation->creator" /></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('No quotation has been created from this itinerary yet.') }}</p>
                        @endforelse
                        @if ($canGenerateQuotation)
                            <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}" class="btn-primary-sm w-full justify-center">
                                {{ ui_phrase('Generate Quotation') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="app-card min-w-0 h-fit p-4 lg:self-start xl:sticky xl:top-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ ui_phrase('Itinerary Map') }}</h2>
                    <div id="itinerary-show-map" class="mt-3 h-[520px] md:h-[640px] w-full rounded-lg border border-gray-300 dark:border-gray-700"></div>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $dayPointByDayForMap = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
        $normalizeAreaKey = static fn ($value): string => strtolower(trim((string) $value));
        $itineraryDestinationKey = $normalizeAreaKey($itinerary->destination ?? '');
        $hotelAreaCoordinateLookup = [];
        try {
            $hotelsForAreaCoordinates = \App\Models\Hotel::query()
                ->with('destination:id,name')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'destination_id', 'city', 'province', 'latitude', 'longitude']);

            foreach ($hotelsForAreaCoordinates as $hotelForArea) {
                $lat = $hotelForArea->latitude;
                $lng = $hotelForArea->longitude;
                if (!is_numeric($lat) || !is_numeric($lng)) {
                    continue;
                }
                $destinationKey = $normalizeAreaKey($hotelForArea->destination?->name ?? '');
                $candidateRegions = array_values(array_filter([
                    trim((string) ($hotelForArea->city ?? '')),
                    trim((string) ($hotelForArea->province ?? '')),
                ], static fn ($value) => $value !== ''));
                foreach ($candidateRegions as $region) {
                    $regionKey = $normalizeAreaKey($region);
                    if ($regionKey === '') {
                        continue;
                    }
                    $bucketKey = $destinationKey . '|' . $regionKey;
                    if (!array_key_exists($bucketKey, $hotelAreaCoordinateLookup)) {
                        $hotelAreaCoordinateLookup[$bucketKey] = ['lat' => (float) $lat, 'lng' => (float) $lng];
                    }
                }
            }
        } catch (\Throwable $e) {
            $hotelAreaCoordinateLookup = [];
        }
        $resolveAreaCoordinates = static function (
            array $lookup,
            string $region,
            string $destinationKey,
            callable $normalize
        ): ?array {
            $regionKey = $normalize($region);
            if ($regionKey === '') {
                return null;
            }
            $preferredKey = $destinationKey . '|' . $regionKey;
            if (array_key_exists($preferredKey, $lookup)) {
                return $lookup[$preferredKey];
            }
            foreach ($lookup as $bucketKey => $coords) {
                $parts = explode('|', (string) $bucketKey, 2);
                $bucketRegionKey = $parts[1] ?? '';
                if ($bucketRegionKey === $regionKey) {
                    return $coords;
                }
            }
            return null;
        };
        $normalizeTransferRouteCoords = static function ($routeGeoJson): array {
            if (is_string($routeGeoJson)) {
                $decoded = json_decode($routeGeoJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $routeGeoJson = $decoded;
                }
            }
            if (is_string($routeGeoJson)) {
                $decoded = json_decode($routeGeoJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $routeGeoJson = $decoded;
                }
            }
            if (!is_array($routeGeoJson)) {
                return [];
            }

            $coordinates = [];
            $type = strtolower((string) ($routeGeoJson['type'] ?? ''));
            if ($type === 'linestring') {
                $candidate = $routeGeoJson['coordinates'] ?? null;
                if (is_string($candidate)) {
                    $candidate = json_decode($candidate, true);
                }
                if (is_array($candidate)) {
                    $coordinates = $candidate;
                }
            } elseif ($type === 'feature' && is_array($routeGeoJson['geometry'] ?? null)) {
                $geometry = $routeGeoJson['geometry'];
                if (strtolower((string) ($geometry['type'] ?? '')) === 'linestring') {
                    $candidate = $geometry['coordinates'] ?? null;
                    if (is_string($candidate)) {
                        $candidate = json_decode($candidate, true);
                    }
                    if (is_array($candidate)) {
                        $coordinates = $candidate;
                    }
                }
            } elseif ($type === 'featurecollection' && is_array($routeGeoJson['features'] ?? null)) {
                foreach ($routeGeoJson['features'] as $feature) {
                    if (!is_array($feature) || !is_array($feature['geometry'] ?? null)) {
                        continue;
                    }
                    $geometry = $feature['geometry'];
                    if (strtolower((string) ($geometry['type'] ?? '')) === 'linestring') {
                        $candidate = $geometry['coordinates'] ?? null;
                        if (is_string($candidate)) {
                            $candidate = json_decode($candidate, true);
                        }
                        if (!is_array($candidate)) {
                            continue;
                        }
                        $coordinates = $candidate;
                        break;
                    }
                }
            } elseif (array_is_list($routeGeoJson)) {
                $coordinates = $routeGeoJson;
            }

            $normalized = [];
            foreach ($coordinates as $coordinate) {
                if (!is_array($coordinate) || count($coordinate) < 2) {
                    continue;
                }
                $lng = $coordinate[0] ?? null;
                $lat = $coordinate[1] ?? null;
                if (!is_numeric($lat) || !is_numeric($lng)) {
                    continue;
                }
                $lat = (float) $lat;
                $lng = (float) $lng;
                if (abs($lat) > 90 || abs($lng) > 180) {
                    continue;
                }
                $normalized[] = ['lat' => $lat, 'lng' => $lng];
            }

            return count($normalized) >= 2 ? $normalized : [];
        };
        $resolveMapPoint = function ($dayPoint, string $scope, ?array $previousEnd = null) use (
            $resolveAreaCoordinates,
            $hotelAreaCoordinateLookup,
            $itineraryDestinationKey,
            $normalizeAreaKey
        ) {
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
                        'name' => (string) ($dayPoint->startAirport->name ?? ui_phrase('Start Point')),
                        'location' => (string) ($dayPoint->startAirport->location ?? '-'),
                        'lat' => $dayPoint->startAirport->latitude,
                        'lng' => $dayPoint->startAirport->longitude,
                    ];
                }
                if ($type === 'hotel' && strtolower(trim((string) ($dayPoint->start_hotel_booking_mode ?? ''))) === 'self') {
                    $startArea = trim((string) ($dayPoint->start_hotel_area ?? ''));
                    if ($startArea !== '') {
                        $areaCoords = $resolveAreaCoordinates($hotelAreaCoordinateLookup, $startArea, $itineraryDestinationKey, $normalizeAreaKey);
                        if (is_array($areaCoords) && is_numeric($areaCoords['lat'] ?? null) && is_numeric($areaCoords['lng'] ?? null)) {
                            return [
                                'type' => 'hotel',
                                'name' => ui_phrase('Self-booked hotel') . ' - ' . $startArea,
                                'location' => $startArea,
                                'lat' => (float) $areaCoords['lat'],
                                'lng' => (float) $areaCoords['lng'],
                            ];
                        }
                    }
                }
                if ($type === 'hotel' && $dayPoint->startHotel) {
                    return [
                        'type' => 'hotel',
                        'name' => (string) ($dayPoint->startHotel->name ?? ui_phrase('Start Point')),
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
                    'name' => (string) ($dayPoint->endAirport->name ?? ui_phrase('End Point')),
                    'location' => (string) ($dayPoint->endAirport->location ?? '-'),
                    'lat' => $dayPoint->endAirport->latitude,
                    'lng' => $dayPoint->endAirport->longitude,
                ];
            }
            if ($type === 'hotel' && strtolower(trim((string) ($dayPoint->end_hotel_booking_mode ?? ''))) === 'self') {
                $endArea = trim((string) ($dayPoint->end_hotel_area ?? ''));
                if ($endArea !== '') {
                    $areaCoords = $resolveAreaCoordinates($hotelAreaCoordinateLookup, $endArea, $itineraryDestinationKey, $normalizeAreaKey);
                    if (is_array($areaCoords) && is_numeric($areaCoords['lat'] ?? null) && is_numeric($areaCoords['lng'] ?? null)) {
                        return [
                            'type' => 'hotel',
                            'name' => ui_phrase('Self-booked hotel') . ' - ' . $endArea,
                            'location' => $endArea,
                            'lat' => (float) $areaCoords['lat'],
                            'lng' => (float) $areaCoords['lng'],
                        ];
                    }
                }
            }
            if ($type === 'hotel' && $dayPoint->endHotel) {
                return [
                    'type' => 'hotel',
                    'name' => (string) ($dayPoint->endHotel->name ?? ui_phrase('End Point')),
                    'location' => (string) ($dayPoint->endHotel->address ?? '-'),
                    'lat' => $dayPoint->endHotel->latitude,
                    'lng' => $dayPoint->endHotel->longitude,
                ];
            }
            return null;
        };

        $mapTimeToMinutes = static function ($value): ?int {
            $time = substr((string) $value, 0, 5);
            if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                return null;
            }

            return ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);
        };

        $mapPoints = collect();
        $previousEndCoordinates = null;
        for ($day = 1; $day <= (int) $itinerary->duration_days; $day++) {
            $dayPoint = $dayPointByDayForMap[$day] ?? null;
            $startData = $resolveMapPoint($dayPoint, 'start', $previousEndCoordinates);
            $endData = $resolveMapPoint($dayPoint, 'end');
            $dayPointCandidates = collect();

            if ($startData && is_numeric($startData['lat'] ?? null) && is_numeric($startData['lng'] ?? null)) {
                $mapPoints->push([
                    'type' => $startData['type'],
                    'name' => $startData['name'],
                    'map_focus_key' => 'day-' . $day . '-start-point',
                    'location' => $startData['location'],
                    'lat' => (float) $startData['lat'],
                    'lng' => (float) $startData['lng'],
                    'day_number' => $day,
                    'visit_order' => 0,
                    'map_order' => 0,
                    'start_time' => $dayPoint && !empty($dayPoint->day_start_time)
                        ? substr((string) $dayPoint->day_start_time, 0, 5)
                        : null,
                    'end_time' => $dayPoint && !empty($dayPoint->day_start_time)
                        ? substr((string) $dayPoint->day_start_time, 0, 5)
                        : null,
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
                    'map_focus_key' => 'day-' . $day . '-attraction-' . (int) ($attraction->id ?? 0) . '-order-' . (int) ($attraction->pivot->visit_order ?? 999999),
                    'location' => (string) ($attraction->location ?? '-'),
                    'lat' => (float) $attraction->latitude,
                    'lng' => (float) $attraction->longitude,
                    'day_number' => $day,
                    'visit_order' => (int) ($attraction->pivot->visit_order ?? 999999),
                    'map_order' => ((int) ($attraction->pivot->visit_order ?? 999999)) * 10,
                    'start_time' => !empty($attraction->pivot->start_time) ? substr((string) $attraction->pivot->start_time, 0, 5) : null,
                    'end_time' => !empty($attraction->pivot->end_time) ? substr((string) $attraction->pivot->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next !== null
                        ? max(0, (int) $attraction->pivot->travel_minutes_to_next)
                        : null,
                ]);
                $dayPointCandidates->push([
                    'map_order' => ((int) ($attraction->pivot->visit_order ?? 999999)) * 10,
                    'end_time' => !empty($attraction->pivot->end_time) ? substr((string) $attraction->pivot->end_time, 0, 5) : null,
                    'lat' => (float) $attraction->latitude,
                    'lng' => (float) $attraction->longitude,
                    'location' => (string) ($attraction->location ?? '-'),
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
                    'map_focus_key' => 'day-' . $day . '-activity-' . (int) ($activityItem->activity_id ?? 0) . '-order-' . (int) ($activityItem->visit_order ?? 999999),
                    'location' => (string) ($activityItem->activity->vendor->location ?? '-'),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'day_number' => $day,
                    'visit_order' => (int) ($activityItem->visit_order ?? 999999),
                    'map_order' => ((int) ($activityItem->visit_order ?? 999999)) * 10,
                    'start_time' => !empty($activityItem->start_time) ? substr((string) $activityItem->start_time, 0, 5) : null,
                    'end_time' => !empty($activityItem->end_time) ? substr((string) $activityItem->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => $activityItem->travel_minutes_to_next !== null
                        ? max(0, (int) $activityItem->travel_minutes_to_next)
                        : null,
                ]);
                $dayPointCandidates->push([
                    'map_order' => ((int) ($activityItem->visit_order ?? 999999)) * 10,
                    'end_time' => !empty($activityItem->end_time) ? substr((string) $activityItem->end_time, 0, 5) : null,
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'location' => (string) ($activityItem->activity->vendor->location ?? '-'),
                ]);
            }
            foreach (($islandTransferDayGroups[$day] ?? collect()) as $transferItem) {
                $transfer = $transferItem->islandTransfer;
                if (!$transfer) {
                    continue;
                }
                $departureLat = $transfer->departure_latitude ?? null;
                $departureLng = $transfer->departure_longitude ?? null;
                $arrivalLat = $transfer->arrival_latitude ?? null;
                $arrivalLng = $transfer->arrival_longitude ?? null;
                $transferRouteCoords = $normalizeTransferRouteCoords($transfer->route_geojson ?? null);
                if ((!is_numeric($departureLat) || !is_numeric($departureLng)) && count($transferRouteCoords) >= 1) {
                    $firstRoutePoint = $transferRouteCoords[0];
                    $departureLat = $firstRoutePoint['lat'] ?? null;
                    $departureLng = $firstRoutePoint['lng'] ?? null;
                }
                if ((!is_numeric($arrivalLat) || !is_numeric($arrivalLng)) && count($transferRouteCoords) >= 1) {
                    $lastRoutePoint = $transferRouteCoords[count($transferRouteCoords) - 1];
                    $arrivalLat = $lastRoutePoint['lat'] ?? null;
                    $arrivalLng = $lastRoutePoint['lng'] ?? null;
                }
                if (is_numeric($departureLat) && is_numeric($departureLng)) {
                    $mapPoints->push([
                        'type' => 'transfer',
                        'transfer_role' => 'departure',
                        'transfer_id' => (int) ($transferItem->island_transfer_id ?? 0),
                        'transfer_pair_key' => (string) ('transfer-' . (int) ($transferItem->island_transfer_id ?? 0) . '-day-' . $day . '-order-' . (int) ($transferItem->visit_order ?? 999999)),
                        'map_focus_key' => 'day-' . $day . '-transfer-' . (int) ($transferItem->island_transfer_id ?? 0) . '-order-' . (int) ($transferItem->visit_order ?? 999999),
                        'name' => (string) (($transfer->name ?? '-') . ' (Departure)'),
                        'location' => (string) ($transfer->departure_point_name ?? '-'),
                        'lat' => (float) $departureLat,
                        'lng' => (float) $departureLng,
                        'day_number' => $day,
                        'visit_order' => (int) ($transferItem->visit_order ?? 999999),
                        'map_order' => ((int) ($transferItem->visit_order ?? 999999)) * 10,
                        'start_time' => !empty($transferItem->start_time) ? substr((string) $transferItem->start_time, 0, 5) : null,
                        'end_time' => !empty($transferItem->end_time) ? substr((string) $transferItem->end_time, 0, 5) : null,
                        'travel_minutes_to_next' => 0,
                        'route_to_next_coords' => $transferRouteCoords,
                    ]);
                }
                if (is_numeric($arrivalLat) && is_numeric($arrivalLng)) {
                    $mapPoints->push([
                        'type' => 'transfer',
                        'transfer_role' => 'arrival',
                        'transfer_id' => (int) ($transferItem->island_transfer_id ?? 0),
                        'transfer_pair_key' => (string) ('transfer-' . (int) ($transferItem->island_transfer_id ?? 0) . '-day-' . $day . '-order-' . (int) ($transferItem->visit_order ?? 999999)),
                        'map_focus_key' => 'day-' . $day . '-transfer-' . (int) ($transferItem->island_transfer_id ?? 0) . '-order-' . (int) ($transferItem->visit_order ?? 999999),
                        'name' => (string) (($transfer->name ?? '-') . ' (Arrival)'),
                        'location' => (string) ($transfer->arrival_point_name ?? '-'),
                        'lat' => (float) $arrivalLat,
                        'lng' => (float) $arrivalLng,
                        'day_number' => $day,
                        'visit_order' => ((int) ($transferItem->visit_order ?? 999999)) + 1,
                        'map_order' => (((int) ($transferItem->visit_order ?? 999999)) * 10) + 1,
                        'start_time' => !empty($transferItem->start_time) ? substr((string) $transferItem->start_time, 0, 5) : null,
                        'end_time' => !empty($transferItem->end_time) ? substr((string) $transferItem->end_time, 0, 5) : null,
                        'travel_minutes_to_next' => $transferItem->travel_minutes_to_next !== null
                            ? max(0, (int) $transferItem->travel_minutes_to_next)
                            : null,
                    ]);
                    $dayPointCandidates->push([
                        'map_order' => (((int) ($transferItem->visit_order ?? 999999)) * 10) + 1,
                        'end_time' => !empty($transferItem->end_time) ? substr((string) $transferItem->end_time, 0, 5) : null,
                        'lat' => (float) $arrivalLat,
                        'lng' => (float) $arrivalLng,
                        'location' => (string) ($transfer->arrival_point_name ?? '-'),
                    ]);
                }
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
                    'map_focus_key' => 'day-' . $day . '-fnb-' . (int) ($foodBeverageItem->food_beverage_id ?? 0) . '-order-' . (int) ($foodBeverageItem->visit_order ?? 999999),
                    'location' => (string) ($foodBeverageItem->foodBeverage->vendor->location ?? '-'),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'day_number' => $day,
                    'visit_order' => (int) ($foodBeverageItem->visit_order ?? 999999),
                    'map_order' => ((int) ($foodBeverageItem->visit_order ?? 999999)) * 10,
                    'start_time' => !empty($foodBeverageItem->start_time) ? substr((string) $foodBeverageItem->start_time, 0, 5) : null,
                    'end_time' => !empty($foodBeverageItem->end_time) ? substr((string) $foodBeverageItem->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => $foodBeverageItem->travel_minutes_to_next !== null
                        ? max(0, (int) $foodBeverageItem->travel_minutes_to_next)
                        : null,
                ]);
                $dayPointCandidates->push([
                    'map_order' => ((int) ($foodBeverageItem->visit_order ?? 999999)) * 10,
                    'end_time' => !empty($foodBeverageItem->end_time) ? substr((string) $foodBeverageItem->end_time, 0, 5) : null,
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'location' => (string) ($foodBeverageItem->foodBeverage->vendor->location ?? '-'),
                ]);
            }

            $breakStartTime = $dayPoint && !empty($dayPoint->break_start_time)
                ? substr((string) $dayPoint->break_start_time, 0, 5)
                : null;
            $breakEndTime = $dayPoint && !empty($dayPoint->break_end_time)
                ? substr((string) $dayPoint->break_end_time, 0, 5)
                : null;
            $hasBreak = filled($breakStartTime) && filled($breakEndTime);

            if ($hasBreak && $dayPointCandidates->isNotEmpty()) {
                $breakStartMinutes = $mapTimeToMinutes($breakStartTime);
                $anchorCandidate = null;
                foreach ($dayPointCandidates->sortBy('map_order')->values() as $candidate) {
                    $candidateEndMinutes = $mapTimeToMinutes($candidate['end_time'] ?? null);
                    if ($breakStartMinutes !== null && $candidateEndMinutes !== null && $candidateEndMinutes <= $breakStartMinutes) {
                        $anchorCandidate = $candidate;
                        continue;
                    }
                    if ($anchorCandidate === null) {
                        $anchorCandidate = $candidate;
                    }
                    break;
                }
                if ($anchorCandidate === null) {
                    $anchorCandidate = $dayPointCandidates->sortByDesc('map_order')->first();
                }

                if (is_array($anchorCandidate) && is_numeric($anchorCandidate['lat'] ?? null) && is_numeric($anchorCandidate['lng'] ?? null)) {
                    $anchorMapOrder = (int) ($anchorCandidate['map_order'] ?? 0);
                    $mapPoints->push([
                        'type' => 'break',
                        'name' => ui_phrase('Break Time'),
                        'map_focus_key' => 'day-' . $day . '-break-time',
                        'location' => (string) ($anchorCandidate['location'] ?? '-'),
                        'lat' => ((float) $anchorCandidate['lat']) + 0.0002,
                        'lng' => ((float) $anchorCandidate['lng']) + 0.0002,
                        'day_number' => $day,
                        'visit_order' => $anchorMapOrder,
                        'map_order' => $anchorMapOrder + 0.5,
                        'break_anchor_map_order' => $anchorMapOrder,
                        'start_time' => $breakStartTime,
                        'end_time' => $breakEndTime,
                        'travel_minutes_to_next' => 0,
                    ]);
                }
            }

            if ($endData && is_numeric($endData['lat'] ?? null) && is_numeric($endData['lng'] ?? null)) {
                $mapPoints->push([
                    'type' => $endData['type'],
                    'name' => $endData['name'],
                    'map_focus_key' => 'day-' . $day . '-end-point',
                    'location' => $endData['location'],
                    'lat' => (float) $endData['lat'],
                    'lng' => (float) $endData['lng'],
                    'day_number' => $day,
                    'visit_order' => 999999,
                    'map_order' => 9999999,
                    'start_time' => null,
                    'end_time' => null,
                    'travel_minutes_to_next' => null,
                ]);
            }

            $previousEndCoordinates = $endData ?: $previousEndCoordinates;
        }
    @endphp
    <style>
        .interactive-selectable {
            transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
        }
        .interactive-selectable.is-active {
            border-color: rgb(251 191 36) !important;
            box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.45), 0 10px 22px -14px rgba(17, 24, 39, 0.55);
            background-color: rgba(251, 191, 36, 0.10);
        }
        .dark .interactive-selectable.is-active {
            border-color: rgb(245 158 11) !important;
            box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.5), 0 10px 22px -14px rgba(0, 0, 0, 0.75);
            background-color: rgba(245, 158, 11, 0.14);
        }
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
            let activeScheduleDay = null;
            let onScheduleDayChange = null;
            const scheduleDayTabs = Array.from(document.querySelectorAll('.itinerary-schedule-day-tab'));
            const scheduleDayPanels = Array.from(document.querySelectorAll('[data-itinerary-schedule-day-panel]'));
            const selectableCards = Array.from(document.querySelectorAll('[data-interactive-selectable="true"]'));
            const clearActiveSelectableCards = () => {
                selectableCards.forEach((card) => {
                    card.classList.remove('is-active');
                    card.setAttribute('aria-pressed', 'false');
                });
            };
            const setActiveSelectableCard = (activeCard) => {
                clearActiveSelectableCards();
                if (!activeCard) return;
                activeCard.classList.add('is-active');
                activeCard.setAttribute('aria-pressed', 'true');
            };
            const setActiveScheduleDay = (dayNumber) => {
                activeScheduleDay = dayNumber;
                scheduleDayTabs.forEach((tab) => {
                    const tabDay = Number(tab.dataset.day || 0);
                    const active = tabDay === dayNumber;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    tab.setAttribute('tabindex', active ? '0' : '-1');
                });
                scheduleDayPanels.forEach((panel) => {
                    const panelDay = Number(panel.dataset.itineraryScheduleDayPanel || 0);
                    panel.hidden = panelDay !== dayNumber;
                });
                clearActiveSelectableCards();
                if (typeof onScheduleDayChange === 'function') {
                    onScheduleDayChange(dayNumber);
                }
            };
            if (scheduleDayTabs.length > 0 && scheduleDayPanels.length > 0) {
                scheduleDayTabs.forEach((tab) => {
                    tab.addEventListener('click', () => {
                        const parsed = Number(tab.dataset.day || 1);
                        if (!Number.isFinite(parsed) || parsed < 1) return;
                        setActiveScheduleDay(parsed);
                    });
                });
                const firstDay = Number(scheduleDayTabs[0]?.dataset.day || 1);
                if (Number.isFinite(firstDay) && firstDay > 0) {
                    setActiveScheduleDay(firstDay);
                }
            }

            const points = @json($mapPoints->sortBy(fn ($point) => ((int) $point['day_number'] * 10000000) + (int) ($point['map_order'] ?? $point['visit_order']))->values());
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
                return ['attraction', 'activity', 'transfer', 'fnb', 'hotel', 'airport', 'break'].includes(value) ? value : 'attraction';
            };
            const iconByType = (type) => {
                const normalized = normalizeType(type);
                if (normalized === 'activity') return 'fa-solid fa-person-hiking';
                if (normalized === 'transfer') return 'fa-solid fa-ship';
                if (normalized === 'fnb') return 'fa-solid fa-utensils';
                if (normalized === 'airport') return 'fa-solid fa-plane';
                if (normalized === 'hotel') return 'fa-solid fa-bed';
                if (normalized === 'break') return 'fa-solid fa-mug-hot';
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
            const formatPopupTime = (value) => {
                const raw = String(value ?? '').trim();
                if (/^\d{2}:\d{2}$/.test(raw)) return raw;
                if (/^\d{2}:\d{2}:\d{2}$/.test(raw)) return raw.slice(0, 5);
                return '--:--';
            };
            const markerBadge = (order, type) => {
                const markerType = normalizeType(type);
                const color = markerType === 'activity'
                    ? '#059669'
                    : (markerType === 'transfer'
                        ? '#7c3aed'
                        : (markerType === 'fnb'
                        ? '#d97706'
                        : (markerType === 'airport'
                            ? '#0284c7'
                            : (markerType === 'hotel'
                                ? '#0f766e'
                                : (markerType === 'break' ? '#6b7280' : '#1d4ed8')))));
                return L.divIcon({
                    className: 'itinerary-show-map-marker-icon',
                    html: `<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:${color};color:#fff;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.25);font-size:12px;position:relative"><i class="${iconByType(markerType)}"></i><span style="position:absolute;right:-5px;bottom:-5px;min-width:14px;height:14px;border-radius:9999px;background:#111827;color:#fff;border:1px solid #fff;font-size:9px;line-height:14px;text-align:center;padding:0 3px">${order}</span></span>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });
            };
            const normalizeRouteToNextCoords = (routeCoords) => {
                if (!Array.isArray(routeCoords)) return [];
                const normalized = routeCoords
                    .map((coordinate) => {
                        if (!coordinate) return null;
                        const lat = coordinate?.lat ?? coordinate?.[1] ?? null;
                        const lng = coordinate?.lng ?? coordinate?.[0] ?? null;
                        return toLatLng(lat, lng);
                    })
                    .filter((point) => point && Number.isFinite(point.lat) && Number.isFinite(point.lng));
                return normalized.length >= 2 ? normalized : [];
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
                        map_order: Number(point?.map_order ?? point?.visit_order ?? 0),
                        type: normalizeType(point?.type),
                        travel_minutes_to_next: Number(point?.travel_minutes_to_next ?? 0),
                        route_to_next_coords: normalizeRouteToNextCoords(point?.route_to_next_coords),
                        transfer_role: String(point?.transfer_role || ''),
                        transfer_pair_key: String(point?.transfer_pair_key || ''),
                        map_focus_key: String(point?.map_focus_key || ''),
                        break_anchor_map_order: Number(point?.break_anchor_map_order ?? NaN),
                        start_time: String(point?.start_time || ''),
                        end_time: String(point?.end_time || ''),
                    };
                })
                .filter((point) => point && point.day_number > 0)
                .sort((a, b) => (a.day_number - b.day_number) || (a.map_order - b.map_order) || (a.visit_order - b.visit_order));

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

            const allDays = [...new Set(validPoints.map((point) => Number(point.day_number)))].sort((a, b) => a - b);
            let selectedDay = Number.isFinite(activeScheduleDay) && activeScheduleDay > 0
                ? activeScheduleDay
                : (allDays.length === 1 ? allDays[0] : null);
            let routeRenderToken = 0;
            let activeRouteFetchController = null;
            let mapBusy = false;
            let renderPendingAfterMove = false;
            let renderedDayKey = null;
            let renderInFlight = null;
            let renderInFlightKey = null;
            let markerGroupsByFocusKey = new Map();
            const roadRouteGeometryCache = new Map();
            const highlightedSegmentState = {
                markerElements: [],
                haloLayers: [],
            };
            const renderKeyForDay = (day) => {
                if (day === null || day === undefined || day === '') return 'all';
                const parsed = Number(day);
                return Number.isFinite(parsed) && parsed > 0 ? String(parsed) : 'all';
            };

            map.on('zoomstart movestart', () => {
                mapBusy = true;
            });
            map.on('zoomend moveend', () => {
                mapBusy = false;
                if (renderPendingAfterMove) {
                    renderPendingAfterMove = false;
                    requestSafeRender(selectedDay, 0, true, true);
                }
            });

            const fetchRoadRouteGeometry = async (latLngPoints, signal) => {
                if (!Array.isArray(latLngPoints) || latLngPoints.length < 2) return null;
                const coordinateString = latLngPoints
                    .map((point) => `${point.lng},${point.lat}`)
                    .join(';');
                if (roadRouteGeometryCache.has(coordinateString)) {
                    return roadRouteGeometryCache.get(coordinateString);
                }
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
                if (routePoints.length < 2) return null;
                roadRouteGeometryCache.set(coordinateString, routePoints);
                return routePoints;
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
            const markerCollisionBucketKey = (lat, lng, dayNumber) => {
                const precision = 5;
                return `${Number(dayNumber)}|${Number(lat).toFixed(precision)}|${Number(lng).toFixed(precision)}`;
            };
            const offsetLatLngForCollision = (baseLatLng, collisionIndex) => {
                if (!baseLatLng || collisionIndex <= 0) return baseLatLng;
                const ring = Math.floor((collisionIndex - 1) / 8) + 1;
                const slot = (collisionIndex - 1) % 8;
                const angle = (Math.PI * 2 * slot) / 8;
                const meterStep = 12;
                const radiusMeters = ring * meterStep;
                const earthMeterPerDegreeLat = 111320;
                const safeCos = Math.max(0.25, Math.cos((baseLatLng.lat * Math.PI) / 180));
                const earthMeterPerDegreeLng = earthMeterPerDegreeLat * safeCos;
                const deltaLat = (radiusMeters * Math.sin(angle)) / earthMeterPerDegreeLat;
                const deltaLng = (radiusMeters * Math.cos(angle)) / earthMeterPerDegreeLng;
                return toLatLng(baseLatLng.lat + deltaLat, baseLatLng.lng + deltaLng) || baseLatLng;
            };

            const renderMarkers = async (day = null, preserveViewport = false) => {
                const renderKey = renderKeyForDay(day);
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
                    renderedDayKey = renderKey;
                    return true;
                }

                const bounds = [];
                const dayCounter = {};
                const dayMarkers = {};
                const dayLabel = 'Day';
                const minuteLabel = @json(ui_phrase('minute'));
                markerGroupsByFocusKey = new Map();
                const markerCollisionBuckets = new Map();
                const dayPlacedPoints = {};
                activePoints.forEach((point) => {
                    dayCounter[point.day_number] = (dayCounter[point.day_number] || 0) + 1;
                    const index = dayCounter[point.day_number];
                    const sourceLatLng = toLatLng(point.lat, point.lng);
                    if (!sourceLatLng) return;
                    bounds.push([sourceLatLng.lat, sourceLatLng.lng]);
                    let displayLatLng = sourceLatLng;
                    const isBreakPoint = normalizeType(point.type) === 'break';
                    if (isBreakPoint) {
                        const dayKey = String(point.day_number);
                        const placedForDay = Array.isArray(dayPlacedPoints[dayKey]) ? dayPlacedPoints[dayKey] : [];
                        let anchorPlaced = placedForDay
                            .filter((entry) => Number.isFinite(entry?.map_order) && normalizeType(entry?.type) !== 'break')
                            .find((entry) => entry.map_order === Number(point.break_anchor_map_order));
                        if (!anchorPlaced && placedForDay.length) {
                            anchorPlaced = placedForDay
                                .filter((entry) => normalizeType(entry?.type) !== 'break')
                                .slice()
                                .reverse()[0] || null;
                        }
                        if (anchorPlaced?.displayLatLng) {
                            const anchorPixel = map.latLngToLayerPoint(anchorPlaced.displayLatLng);
                            const breakPixel = L.point(anchorPixel.x + 24, anchorPixel.y);
                            displayLatLng = map.layerPointToLatLng(breakPixel);
                        }
                    } else {
                        const bucketKey = markerCollisionBucketKey(sourceLatLng.lat, sourceLatLng.lng, point.day_number);
                        const collisionIndex = Number(markerCollisionBuckets.get(bucketKey) || 0);
                        markerCollisionBuckets.set(bucketKey, collisionIndex + 1);
                        displayLatLng = offsetLatLngForCollision(sourceLatLng, collisionIndex);
                    }
                    const marker = L.marker(displayLatLng, { icon: markerBadge(index, point.type) }).addTo(mapDataLayer);
                    const startTimeText = formatPopupTime(point.start_time);
                    const endTimeText = formatPopupTime(point.end_time);
                    const isBoundaryPoint = String(point.map_focus_key || '').endsWith('-start-point')
                        || String(point.map_focus_key || '').endsWith('-end-point');
                    const boundaryTimeText = startTimeText !== '--:--'
                        ? startTimeText
                        : endTimeText;
                    marker.bindPopup(
                        isBreakPoint
                            ? `#${index} | ${dayLabel} ${point.day_number} | ${escapeHtml(point.name || 'Break Time')}<br>${escapeHtml(point.location || '-')}<br>Break: ${escapeHtml(startTimeText)} - ${escapeHtml(endTimeText)}`
                            : (isBoundaryPoint
                            ? `#${index} | ${dayLabel} ${point.day_number} | ${escapeHtml(point.name || '-')}<br>${escapeHtml(point.location || '-')}<br>Time: ${escapeHtml(boundaryTimeText)}`
                            : `#${index} | ${dayLabel} ${point.day_number} | ${escapeHtml(point.name || '-')}<br>${escapeHtml(point.location || '-')}<br>Start: ${escapeHtml(startTimeText)} | End: ${escapeHtml(endTimeText)}`)
                    );
                    const focusKey = String(point.map_focus_key || '').trim();
                    if (focusKey !== '') {
                        const existingMarkers = markerGroupsByFocusKey.get(focusKey) || [];
                        existingMarkers.push(marker);
                        markerGroupsByFocusKey.set(focusKey, existingMarkers);
                    }
                    const dayKey = String(point.day_number);
                    if (!dayMarkers[dayKey]) dayMarkers[dayKey] = [];
                    dayMarkers[dayKey].push(marker);
                    if (!dayPlacedPoints[dayKey]) dayPlacedPoints[dayKey] = [];
                    dayPlacedPoints[dayKey].push({
                        map_order: Number(point.map_order ?? 0),
                        type: point.type,
                        displayLatLng,
                    });
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
                        .sort((a, b) => (a.map_order - b.map_order) || (a.visit_order - b.visit_order));
                    const dayLatLngPoints = dayPointEntries
                        .map((point) => toLatLng(point.lat, point.lng))
                        .filter((point) => point && Number.isFinite(point.lat) && Number.isFinite(point.lng));
                    if (dayLatLngPoints.length < 2) continue;

                    const color = routePalette[(dayNumber - 1) % routePalette.length];
                    const segmentRoutes = [];
                    const mergedRoute = [];
                    let canRenderDayRoute = true;
                    for (let index = 0; index < dayPointEntries.length - 1; index += 1) {
                        const fromPoint = dayPointEntries[index];
                        const toPoint = dayPointEntries[index + 1];
                        const from = dayLatLngPoints[index];
                        const to = dayLatLngPoints[index + 1];
                        const isTransferSegment =
                            normalizeType(fromPoint?.type) === 'transfer' &&
                            normalizeType(toPoint?.type) === 'transfer';
                        const isMatchingTransferPair =
                            isTransferSegment &&
                            fromPoint?.transfer_role === 'departure' &&
                            toPoint?.transfer_role === 'arrival' &&
                            fromPoint?.transfer_pair_key !== '' &&
                            fromPoint?.transfer_pair_key === toPoint?.transfer_pair_key;
                        let segment = isMatchingTransferPair &&
                            Array.isArray(fromPoint?.route_to_next_coords) &&
                            fromPoint.route_to_next_coords.length >= 2
                            ? fromPoint.route_to_next_coords
                            : null;
                        if (segment) {
                            segment = orientSegmentCoords(segment, from, to);
                        }
                        if (isMatchingTransferPair && !segment) {
                            continue;
                        }
                        if (!segment) {
                            try {
                                segment = await fetchRoadRouteGeometry([from, to], routeSignal);
                                if (currentToken !== routeRenderToken) return false;
                            } catch (_) {
                                segment = null;
                            }
                            if (segment) {
                                segment = orientSegmentCoords(segment, from, to);
                            }
                        }
                        if (!Array.isArray(segment) || segment.length < 2) {
                            canRenderDayRoute = false;
                            break;
                        }
                        segmentRoutes.push(segment);
                        if (mergedRoute.length === 0) {
                            mergedRoute.push(...segment);
                        } else {
                            mergedRoute.push(...segment.slice(1));
                        }
                    }
                    if (!canRenderDayRoute || mergedRoute.length < 2) {
                        continue;
                    }

                    L.polyline(mergedRoute, {
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
                        const segmentRoute = segmentRoutes[index] ?? null;
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
                            .setContent(`${minutes} ${minuteLabel}`)
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
                if (!preserveViewport) {
                    if (safeBounds.length === 0) {
                        map.setView([-2.5, 118], 4);
                    } else if (safeBounds.length === 1) {
                        map.setView(safeBounds[0], 13);
                    } else {
                        map.fitBounds(safeBounds, { padding: [24, 24] });
                    }
                }
                renderedDayKey = renderKey;
                return true;
            };
            const canRenderMapNow = () => {
                if (!mapElement.isConnected) return false;
                const rect = mapElement.getBoundingClientRect();
                return Number.isFinite(rect.width) && Number.isFinite(rect.height) && rect.width > 8 && rect.height > 8;
            };
            const requestSafeRender = async (day = null, retry = 0, preserveViewport = false, forceRender = false) => {
                const requestedRenderKey = renderKeyForDay(day);
                if (!canRenderMapNow()) {
                    if (retry >= 8) return;
                    window.setTimeout(() => {
                        requestSafeRender(day, retry + 1, preserveViewport, forceRender);
                    }, 120);
                    return;
                }
                map.invalidateSize(false);
                if (!forceRender && renderedDayKey === requestedRenderKey && markerGroupsByFocusKey.size > 0) {
                    return true;
                }
                if (renderInFlight && renderInFlightKey === requestedRenderKey) {
                    return renderInFlight;
                }
                if (mapBusy && forceRender) {
                    try {
                        map.stop();
                    } catch (_) {}
                    mapBusy = false;
                }
                if (mapBusy) {
                    renderPendingAfterMove = true;
                    return false;
                }
                renderInFlightKey = requestedRenderKey;
                renderInFlight = renderMarkers(day, preserveViewport).finally(() => {
                    renderInFlight = null;
                    renderInFlightKey = null;
                });
                return renderInFlight;
            };
            const ensureMapRenderedForDay = async (day = selectedDay) => {
                const requestedRenderKey = renderKeyForDay(day);
                if (renderedDayKey === requestedRenderKey && markerGroupsByFocusKey.size > 0) {
                    map.invalidateSize(false);
                    return true;
                }
                return requestSafeRender(day);
            };
            const focusMapByItemKey = async (focusKey) => {
                const normalizedKey = String(focusKey || '').trim();
                if (normalizedKey === '') return false;
                const markerGroup = markerGroupsByFocusKey.get(normalizedKey) || [];
                if (markerGroup.length === 0) return false;
                if (markerGroup.length === 1) {
                    const marker = markerGroup[0];
                    const latLng = marker?.getLatLng?.();
                    if (!latLng) return false;
                    clearSegmentHighlight();
                    const markerElement = marker.getElement?.();
                    if (markerElement) {
                        markerElement.classList.add('itinerary-show-map-marker-active');
                        highlightedSegmentState.markerElements = [markerElement];
                    }
                    map.flyTo(latLng, Math.max(map.getZoom(), 13), { duration: 0.45 });
                    window.setTimeout(() => marker.openPopup?.(), 220);
                    if (typeof mapElement.scrollIntoView === 'function') {
                        mapElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    return true;
                }

                const first = markerGroup[0];
                const last = markerGroup[markerGroup.length - 1];
                highlightMarkerPair(first, last);
                const latLngs = markerGroup
                    .map((marker) => marker?.getLatLng?.())
                    .filter((latLng) => latLng && Number.isFinite(latLng.lat) && Number.isFinite(latLng.lng));
                if (latLngs.length === 1) {
                    map.flyTo(latLngs[0], Math.max(map.getZoom(), 13), { duration: 0.45 });
                    window.setTimeout(() => first.openPopup?.(), 220);
                    return true;
                }
                if (latLngs.length >= 2) {
                    const groupBounds = L.latLngBounds(latLngs);
                    map.flyToBounds(groupBounds, { padding: [36, 36], duration: 0.45 });
                    window.setTimeout(() => first.openPopup?.(), 240);
                    if (typeof mapElement.scrollIntoView === 'function') {
                        mapElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    return true;
                }
                return false;
            };
            onScheduleDayChange = async (dayNumber) => {
                if (!Number.isFinite(dayNumber) || dayNumber < 1) return;
                selectedDay = dayNumber;
                await requestSafeRender(selectedDay, 0, false, true);
            };
            const mapFocusCards = Array.from(document.querySelectorAll('.js-itinerary-map-focus'));
            const handleMapFocusCard = async (cardElement) => {
                if (!cardElement) return;
                const focusKey = String(cardElement.dataset.mapFocusKey || '').trim();
                if (focusKey === '') return;
                setActiveSelectableCard(cardElement);
                const dayNumber = Number(cardElement.dataset.day || 0);
                if (Number.isFinite(dayNumber) && dayNumber > 0 && dayNumber !== Number(activeScheduleDay || 0)) {
                    setActiveScheduleDay(dayNumber);
                    await requestSafeRender(dayNumber, 0, false, true);
                    focusMapByItemKey(focusKey);
                    return;
                }
                await ensureMapRenderedForDay(selectedDay);
                focusMapByItemKey(focusKey);
            };
            mapFocusCards.forEach((cardElement) => {
                cardElement.addEventListener('click', () => {
                    handleMapFocusCard(cardElement);
                });
                cardElement.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    event.preventDefault();
                    handleMapFocusCard(cardElement);
                });
            });

            if (!allDays.length) {
                map.invalidateSize(false);
                map.setView([-2.5, 118], 4);
                    isInitialized = true;
                    return true;
            }
            window.setTimeout(() => {
                requestSafeRender(selectedDay);
            }, 0);
            map.whenReady(() => {
                requestSafeRender(selectedDay);
            });
            let resizeRenderTimer = null;
            window.addEventListener('resize', () => {
                window.clearTimeout(resizeRenderTimer);
                resizeRenderTimer = window.setTimeout(() => {
                    requestSafeRender(selectedDay, 0, true);
                }, 120);
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
