@extends('layouts.master')

@section('page_title', __('ui.modules.itineraries.show_page_title'))
@section('page_subtitle', __('ui.modules.itineraries.show_page_subtitle'))

@section('content')
    <div class="space-y-5 itinerary-show-page">
        <div class="app-card p-4 mb-6">
            @section('page_actions')@if (Route::has('quotations.create') && auth()->user()->can('module.quotations.access') && ! $itinerary->quotation)
                        <a href="{{ route('quotations.create', ['itinerary_id' => $itinerary->id]) }}" class="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/20">{{ __('ui.common.generate_quotation') }}</a>
                    @endif
                    <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'stream']) }}" target="_blank" rel="noopener" class="rounded-lg border border-sky-300 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50 dark:border-sky-700 dark:text-sky-300 dark:hover:bg-sky-900/20">{{ __('ui.common.preview_pdf') }}</a>
                    <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'download']) }}" target="_blank" rel="noopener" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">{{ __('ui.common.download_pdf') }}</a>
                    @can('update', $itinerary)
                        @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') && ! $itinerary->isFinal())
                            <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary">{{ __('ui.common.edit') }}</a>
                        @endif
                    @endcan
                    <a href="{{ route('itineraries.index') }}"  class="btn-primary">{{ __('ui.common.back') }}</a>@endsection
            <div class="my-3 grid grid-cols-1 gap-2 text-sm md:grid-cols-2">
                <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.title') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $itinerary->title }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.duration') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $itinerary->duration_days }}D{{ $itinerary->duration_nights > 0 ? "/".$itinerary->duration_nights."N": "" }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.destination') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $itinerary->destination ?: '-' }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.inquiry') }}:</span>
                    <span class="text-gray-800 dark:text-gray-100">
                        @if ($itinerary->inquiry)
                            {{ $itinerary->inquiry?->inquiry_number ?? '-' }}{{ $itinerary->inquiry?->customer?->name ? ' | '.$itinerary->inquiry?->customer?->name : '' }}
                        @else
                            {{ __('ui.modules.itineraries.independent') }}
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
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ __('ui.modules.hotels.page_title') }}</h2>
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
                            $bookingModeLabel = ((string) ($stayPoint?->end_hotel_booking_mode ?? 'arranged')) === 'self'
                                ? __('ui.modules.itineraries.self_booked_hotel')
                                : __('ui.modules.itineraries.hotel_arranged_by_us');
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
                                    {{ __('ui.modules.hotels.room') }}: {{ $roomName }}{{ $roomType !== '' ? ' ('.$roomType.')' : '' }}
                                </p>
                            @endif
                            <p class="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-300">
                                {{ __('ui.modules.itineraries.day_label', ['day' => $hotel->pivot->day_number ?? 1]) }} | {{ __('ui.modules.itineraries.night_count', ['count' => $hotel->pivot->night_count ?? 1]) }}
                            </p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ __('ui.modules.itineraries.booking_mode') }}: {{ $bookingModeLabel }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid min-w-0 grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="app-card min-w-0 p-4 lg:col-span-7">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ __('ui.common.schedule_by_day') }}</h2>
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
                                $parts = preg_split('/[\s,;\/|]+/', strtolower(trim((string) $value))) ?: [];
                                foreach ($parts as $part) {
                                    $part = trim((string) $part);
                                    if ($part !== '') {
                                        $tokens[] = $part;
                                    }
                                }
                            }
                            $tokens = array_values(array_unique($tokens));

                            $labels = [];
                            foreach (['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'] as $key => $label) {
                                if (in_array($key, $tokens, true)) {
                                    $labels[] = $label;
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
                            $defaultNotSet = __('ui.modules.itineraries.not_set');
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
                                    $hotelName = (string) ($dayPoint->startHotel?->name ?? $defaultNotSet);
                                    $roomName = (string) ($dayPoint->startHotelRoom?->rooms ?? '');
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
                                $hotelName = (string) ($dayPoint->endHotel?->name ?? $defaultNotSet);
                                $roomName = (string) ($dayPoint->endHotelRoom?->rooms ?? '');
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.display_by_day') }}</p>
                            <div id="itinerary-schedule-day-tabs" class="mt-2 flex flex-wrap gap-2" role="tablist" aria-label="{{ __('ui.common.schedule_by_day') }}">
                                @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                                    <button
                                        type="button"
                                        id="itinerary-schedule-day-tab-{{ $day }}"
                                        data-day="{{ $day }}"
                                        class="itinerary-schedule-day-tab {{ $day === 1 ? 'btn-primary-sm' : 'btn-outline-sm' }}"
                                        role="tab"
                                        aria-selected="{{ $day === 1 ? 'true' : 'false' }}"
                                        aria-controls="itinerary-schedule-day-panel-{{ $day }}"
                                        tabindex="{{ $day === 1 ? '0' : '-1' }}">
                                        {{ __('ui.modules.itineraries.day_label', ['day' => $day]) }}
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
                                    'publish_rate' => $foodBeverage->publish_rate ?? null,
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
                                : ($day > 1 ? $previousEndLabel : __('ui.modules.itineraries.not_set'));
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
                            $startPointTypeLabel = $startPointType === 'airport' ? 'Airport' : 'Hotel';
                            $endPointTypeLabel = $endPointType === 'airport' ? 'Airport' : 'Hotel';
                            $firstItem = $dayItems->first();
                            $lastItem = $dayItems->last();
                            $dayStartTime = $dayPoint && !empty($dayPoint->day_start_time)
                                ? substr((string) $dayPoint->day_start_time, 0, 5)
                                : (!empty($firstItem['start_time']) ? substr((string) $firstItem['start_time'], 0, 5) : null);
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
                                ? __('ui.modules.itineraries.self_booked_hotel')
                                : __('ui.modules.itineraries.hotel_arranged_by_us');
                            $endBookingModeLabel = ((string) ($dayPoint?->end_hotel_booking_mode ?? 'arranged')) === 'self'
                                ? __('ui.modules.itineraries.self_booked_hotel')
                                : __('ui.modules.itineraries.hotel_arranged_by_us');
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
                                <p class="app-day-header-title">{{ __('ui.modules.itineraries.day_label', ['day' => $day]) }}</p>
                                <p class="app-day-header-meta">
                                    {{ __('ui.modules.itineraries.start_tour') }}: {{ $dayStartTime ?? '--:--' }} | {{ __('ui.modules.itineraries.end_tour') }}: {{ $dayEndTime ?? '--:--' }}
                                </p>
                            </div>

                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                {{ __('ui.modules.itineraries.starts_at') }}: {{ $startPointLabel ?: __('ui.modules.itineraries.not_set') }} | {{ __('ui.modules.itineraries.ends_at') }}: {{ $endPointLabel ?: __('ui.modules.itineraries.not_set') }}
                            </p>
                            <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50/70 px-2.5 py-2 dark:border-gray-700 dark:bg-gray-900/30">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('ui.modules.itineraries.transport_unit') }}</p>
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
                                                $transportName = trim((string) ($transportSource?->name ?? $transportUnit?->name ?? '-'));
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
                                                                    alt="{{ $transportName }} image"
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
                                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $transportTypeLabel !== '' ? $transportTypeLabel : 'Transport' }}</p>
                                                        <p class="mt-0.5 text-xs font-medium text-gray-800 dark:text-gray-100">{{ $transportName !== '' ? $transportName : '-' }}</p>
                                                        @if ($transportModel !== '')
                                                            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">{{ $transportModel }}</p>
                                                        @endif
                                                        <div class="mt-1 flex flex-wrap gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                            <span>Seat: {{ $seatCapacity !== null ? $seatCapacity : '-' }}</span>
                                                            <span>| Luggage: {{ $luggageCapacity !== null ? $luggageCapacity : '-' }}</span>
                                                            <span>| AC: {{ $airConditioned === null ? '-' : ($airConditioned ? 'Yes' : 'No') }}</span>
                                                            <span>| Driver: {{ $withDriver === null ? '-' : ($withDriver ? 'Yes' : 'No') }}</span>
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
                                            {{ $dayStartTravelMinutes !== null ? $dayStartTravelMinutes : '-' }} min
                                        </span>
                                        <span class="timeline-travel-spacer"></span>
                                    </div>
                                    <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                    <div class="ml-2 flex-1 rounded-lg border border-gray-200 px-2 py-2 dark:border-gray-700">
                                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[9rem_minmax(0,1fr)]">
                                                <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60">
                                                    <div class="relative aspect-[16/9] h-full overflow-hidden">
                                                    @if (filled($startPointImageUrl))
                                                        <img src="{{ $startPointImageUrl }}" alt="{{ __('ui.common.start_point') }}" class="absolute inset-0 block h-full w-full object-cover object-center">
                                                    @else
                                                        <div class="flex h-full w-full flex-col items-center justify-center gap-1 text-gray-500 dark:text-gray-300">
                                                            <i class="{{ $startPointType === 'airport' ? 'fa-solid fa-plane' : 'fa-solid fa-bed' }} text-sm"></i>
                                                            <span class="text-[10px] font-semibold uppercase tracking-wide">{{ __('ui.common.start_point') }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $startPointTypeLabel }}</p>
                                                <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ $startPointLabel ?: __('ui.modules.itineraries.not_set') }}</span>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $startPointLocation ?: '-' }}
                                                    | {{ __('ui.modules.itineraries.start_tour') }}: {{ $dayStartTime ?? '--:--' }}
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
                                                {{ $travelMinutes !== null ? $travelMinutes . ' min' : '- min' }}
                                            </span>
                                            <span class="timeline-travel-spacer"></span>
                                        </div>
                                            <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                        @php
                                            $itemType = (string) ($item['type'] ?? '');
                                            $itemTypeLabel = $itemType === 'fnb'
                                                ? 'F&B'
                                                : ($itemType === 'transfer' ? "Island's Transfer" : ucfirst($itemType));
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
                                            $itemDestination = trim((string) ($item['destination_label'] ?? ''));
                                            $itemLocationFallback = trim((string) ($item['location'] ?? ''));
                                            $itemMetaLocation = $itemRegionCity !== '' && $itemRegionCity !== '-'
                                                ? $itemRegionCity
                                                : ($itemLocationFallback !== '' ? $itemLocationFallback : '-');
                                            if ($itemDestination !== '') {
                                                $itemMetaLocation .= ', ' . $itemDestination;
                                            }
                                        @endphp
                                        <div class="ml-2 flex-1 rounded-lg border px-2 py-2 {{ $isMainExperience ? 'border-amber-400 bg-amber-50/70 dark:border-amber-500 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-700' }}">
                                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[9rem_minmax(0,1fr)]">
                                                <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60">
                                                    <div class="relative aspect-[16/9] h-full overflow-hidden">
                                                        @if (!empty($item['thumbnail_url']))
                                                            <img src="{{ $item['thumbnail_url'] }}" alt="{{ ($item['name'] ?: $itemTypeLabel) . ' thumbnail' }}" class="absolute inset-0 block h-full w-full object-cover object-center">
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
                                                        @if ($isMainExperience)
                                                            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">{{ __('ui.common.main_experience') }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $itemMetaLocation }} | {{ $itemStartTime }} - {{ $itemEndTime }}
                                                    </div>
                                                </div>
                                            </div>
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
                                    <div class="ml-2 flex-1 rounded-lg border border-gray-200 px-2 py-2 dark:border-gray-700">
                                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[9rem_minmax(0,1fr)]">
                                            <div class="overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800/60">
                                                <div class="relative aspect-[16/9] h-full overflow-hidden">
                                                    @if (filled($endPointImageUrl))
                                                        <img src="{{ $endPointImageUrl }}" alt="{{ __('ui.common.end_point') }}" class="absolute inset-0 block h-full w-full object-cover object-center">
                                                    @else
                                                        <div class="flex h-full w-full flex-col items-center justify-center gap-1 text-gray-500 dark:text-gray-300">
                                                            <i class="{{ $endPointType === 'airport' ? 'fa-solid fa-plane-arrival' : 'fa-solid fa-bed' }} text-sm"></i>
                                                            <span class="text-[10px] font-semibold uppercase tracking-wide">{{ __('ui.common.end_point') }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $endPointTypeLabel }}</p>
                                                <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ $endPointLabel ?: __('ui.modules.itineraries.not_set') }}</span>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $endPointLocation ?: '-' }}
                                                    | {{ __('ui.modules.itineraries.end_tour') }}: {{ $dayEndTime ?? '--:--' }}
                                                </div>
                                                @if ($dayItems->isEmpty())
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.itineraries.no_schedule_item') }}</p>
                                                @endif
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
                    @endphp
                    @if (filled($itineraryIncludeText) || filled($itineraryExcludeText))
                        <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                            @if (filled($itineraryIncludeText))
                                <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50/60 px-2 py-1 dark:border-emerald-800 dark:bg-emerald-900/20">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('ui.modules.itineraries.itinerary_include') }}</p>
                                    <x-rich-text :content="$itinerary->itinerary_include" class="mt-0.5 text-xs text-emerald-900 dark:text-emerald-100" />
                                </div>
                            @endif
                            @if (filled($itineraryExcludeText))
                                <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50/60 px-2 py-1 dark:border-rose-800 dark:bg-rose-900/20">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">{{ __('ui.modules.itineraries.itinerary_exclude') }}</p>
                                    <x-rich-text :content="$itinerary->itinerary_exclude" class="mt-0.5 text-xs text-rose-900 dark:text-rose-100" />
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            <div class="space-y-4 lg:col-span-5">
                <div class="app-card p-4 space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.activity_timeline') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ __('ui.modules.itineraries.detailed_audit_log') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
                <div class="app-card min-w-0 h-fit p-4 lg:self-start xl:sticky xl:top-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ __('ui.common.itinerary_map') }}</h2>
                    <div id="itinerary-show-map" class="mt-3 h-[520px] md:h-[640px] w-full rounded-lg border border-gray-300 dark:border-gray-700"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $dayPointByDayForMap = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
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
                        'name' => (string) ($dayPoint->startAirport->name ?? __('ui.common.start_point')),
                        'location' => (string) ($dayPoint->startAirport->location ?? '-'),
                        'lat' => $dayPoint->startAirport->latitude,
                        'lng' => $dayPoint->startAirport->longitude,
                    ];
                }
                if ($type === 'hotel' && $dayPoint->startHotel) {
                    return [
                        'type' => 'hotel',
                        'name' => (string) ($dayPoint->startHotel->name ?? __('ui.common.start_point')),
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
                    'name' => (string) ($dayPoint->endAirport->name ?? __('ui.common.end_point')),
                    'location' => (string) ($dayPoint->endAirport->location ?? '-'),
                    'lat' => $dayPoint->endAirport->latitude,
                    'lng' => $dayPoint->endAirport->longitude,
                ];
            }
            if ($type === 'hotel' && $dayPoint->endHotel) {
                return [
                    'type' => 'hotel',
                    'name' => (string) ($dayPoint->endHotel->name ?? __('ui.common.end_point')),
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
                    'map_order' => 0,
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
                    'map_order' => ((int) ($attraction->pivot->visit_order ?? 999999)) * 10,
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
                    'map_order' => ((int) ($activityItem->visit_order ?? 999999)) * 10,
                    'travel_minutes_to_next' => $activityItem->travel_minutes_to_next !== null
                        ? max(0, (int) $activityItem->travel_minutes_to_next)
                        : null,
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
                        'name' => (string) (($transfer->name ?? '-') . ' (Departure)'),
                        'location' => (string) ($transfer->departure_point_name ?? '-'),
                        'lat' => (float) $departureLat,
                        'lng' => (float) $departureLng,
                        'day_number' => $day,
                        'visit_order' => (int) ($transferItem->visit_order ?? 999999),
                        'map_order' => ((int) ($transferItem->visit_order ?? 999999)) * 10,
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
                        'name' => (string) (($transfer->name ?? '-') . ' (Arrival)'),
                        'location' => (string) ($transfer->arrival_point_name ?? '-'),
                        'lat' => (float) $arrivalLat,
                        'lng' => (float) $arrivalLng,
                        'day_number' => $day,
                        'visit_order' => ((int) ($transferItem->visit_order ?? 999999)) + 1,
                        'map_order' => (((int) ($transferItem->visit_order ?? 999999)) * 10) + 1,
                        'travel_minutes_to_next' => $transferItem->travel_minutes_to_next !== null
                            ? max(0, (int) $transferItem->travel_minutes_to_next)
                            : null,
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
                    'location' => (string) ($foodBeverageItem->foodBeverage->vendor->location ?? '-'),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'day_number' => $day,
                    'visit_order' => (int) ($foodBeverageItem->visit_order ?? 999999),
                    'map_order' => ((int) ($foodBeverageItem->visit_order ?? 999999)) * 10,
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
                    'map_order' => 9999999,
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
            let activeScheduleDay = null;
            let onScheduleDayChange = null;
            const scheduleDayTabs = Array.from(document.querySelectorAll('.itinerary-schedule-day-tab'));
            const scheduleDayPanels = Array.from(document.querySelectorAll('[data-itinerary-schedule-day-panel]'));
            const setActiveScheduleDay = (dayNumber) => {
                activeScheduleDay = dayNumber;
                scheduleDayTabs.forEach((tab) => {
                    const tabDay = Number(tab.dataset.day || 0);
                    const active = tabDay === dayNumber;
                    tab.classList.toggle('btn-primary-sm', active);
                    tab.classList.toggle('btn-outline-sm', !active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    tab.setAttribute('tabindex', active ? '0' : '-1');
                });
                scheduleDayPanels.forEach((panel) => {
                    const panelDay = Number(panel.dataset.itineraryScheduleDayPanel || 0);
                    panel.hidden = panelDay !== dayNumber;
                });
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
                return ['attraction', 'activity', 'transfer', 'fnb', 'hotel', 'airport'].includes(value) ? value : 'attraction';
            };
            const iconByType = (type) => {
                const normalized = normalizeType(type);
                if (normalized === 'activity') return 'fa-solid fa-person-hiking';
                if (normalized === 'transfer') return 'fa-solid fa-ship';
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
                    : (markerType === 'transfer'
                        ? '#7c3aed'
                        : (markerType === 'fnb'
                        ? '#d97706'
                        : (markerType === 'airport'
                            ? '#0284c7'
                            : (markerType === 'hotel' ? '#0f766e' : '#1d4ed8'))));
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
                const dayLabel = @json(__('ui.modules.itineraries.day_short'));
                activePoints.forEach((point) => {
                    dayCounter[point.day_number] = (dayCounter[point.day_number] || 0) + 1;
                    const index = dayCounter[point.day_number];
                    const latLng = toLatLng(point.lat, point.lng);
                    if (!latLng) return;
                    bounds.push([latLng.lat, latLng.lng]);
                    const marker = L.marker(latLng, { icon: markerBadge(index, point.type) }).addTo(mapDataLayer);
                    marker.bindPopup(`#${index} | ${dayLabel} ${point.day_number} | ${escapeHtml(point.name || '-')}<br>${escapeHtml(point.location || '-')}`);
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
                                if (currentToken !== routeRenderToken) return;
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
            onScheduleDayChange = async (dayNumber) => {
                if (!Number.isFinite(dayNumber) || dayNumber < 1) return;
                selectedDay = dayNumber;
                await requestSafeRender(selectedDay);
            };

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
