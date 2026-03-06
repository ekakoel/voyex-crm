<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
use App\Models\Activity;
use App\Models\Airport;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Inquiry;
use App\Models\Itinerary;
use App\Models\TouristAttraction;
use App\Models\Transport;
use App\Models\TransportUnit;
use App\Models\Vendor;
use App\Support\ImageThumbnailGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ItineraryController extends Controller
{
    public function index()
    {
        $itineraries = Itinerary::query()
            ->with([
                'touristAttractions:id,name',
                'inquiry:id,inquiry_number,customer_id',
                'inquiry.customer:id,name',
            ])
            ->orderBy('title')
            ->paginate(10);

        return view('modules.itineraries.index', compact('itineraries'));
    }

    public function create(Request $request)
    {
        $touristAttractions = TouristAttraction::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude', 'ideal_visit_minutes']);
        $activities = Activity::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'activity_type', 'duration_minutes', 'agent_price', 'currency']);
        $foodBeverages = FoodBeverage::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'service_type', 'duration_minutes', 'agent_price', 'currency', 'meal_period', 'notes', 'menu_highlights']);
        $accommodations = Accommodation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'star_rating', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude']);
        $airports = Airport::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude']);
        $transportUnits = TransportUnit::query()
            ->with('transport:id,name,city,province,destination_id,is_active')
            ->where('is_active', true)
            ->whereHas('transport', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'transport_id', 'name', 'vehicle_type', 'seat_capacity', 'currency']);
        $inquiries = Inquiry::query()
            ->with([
                'customer:id,name,code',
                'assignedUser:id,name',
            ])
            ->orderByDesc('id')
            ->get([
                'id',
                'inquiry_number',
                'customer_id',
                'source',
                'status',
                'priority',
                'assigned_to',
                'deadline',
                'notes',
                'created_at',
            ]);
        $prefillInquiryId = $request->integer('inquiry_id') ?: null;

        return view('modules.itineraries.create', compact('touristAttractions', 'activities', 'foodBeverages', 'accommodations', 'airports', 'transportUnits', 'inquiries', 'prefillInquiryId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'arrival_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'departure_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'inquiry_id' => ['nullable', 'integer', 'exists:inquiries,id'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'duration_nights' => ['required', 'integer', 'min:0', 'lte:duration_days'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'accommodation_stays' => ['nullable', 'array'],
            'accommodation_stays.*.accommodation_id' => ['nullable', 'integer', 'exists:accommodations,id'],
            'accommodation_stays.*.day_number' => ['nullable', 'integer', 'min:1'],
            'accommodation_stays.*.night_count' => ['nullable', 'integer', 'min:1'],
            'accommodation_stays.*.room_count' => ['nullable', 'integer', 'min:1'],
            'daily_start_point_types' => ['nullable', 'array'],
            'daily_start_point_types.*' => ['nullable', 'string', 'in:previous_day_end,accommodation,airport'],
            'daily_start_point_items' => ['nullable', 'array'],
            'day_start_times' => ['nullable', 'array'],
            'day_start_times.*' => ['nullable', 'date_format:H:i'],
            'day_start_travel_minutes' => ['nullable', 'array'],
            'day_start_travel_minutes.*' => ['nullable', 'integer', 'min:0'],
            'daily_end_point_types' => ['nullable', 'array'],
            'daily_end_point_types.*' => ['nullable', 'string', 'in:accommodation,airport'],
            'daily_end_point_items' => ['nullable', 'array'],
            'daily_main_experience_types' => ['nullable', 'array'],
            'daily_main_experience_types.*' => ['nullable', 'string', 'in:attraction,activity,fnb'],
            'daily_main_experience_items' => ['nullable', 'array'],
            'daily_main_experience_items.*' => ['nullable', 'integer', 'min:1'],
            'itinerary_items' => ['required', 'array', 'min:1'],
            'itinerary_items.*.tourist_attraction_id' => ['required', 'integer', 'distinct', 'exists:tourist_attractions,id'],
            'itinerary_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'itinerary_activity_items' => ['nullable', 'array'],
            'itinerary_activity_items.*.activity_id' => ['required', 'integer', 'exists:activities,id'],
            'itinerary_activity_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_activity_items.*.pax' => ['nullable', 'integer', 'min:1'],
            'itinerary_activity_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'itinerary_food_beverage_items' => ['nullable', 'array'],
            'itinerary_food_beverage_items.*.food_beverage_id' => ['required', 'integer', 'exists:food_beverages,id'],
            'itinerary_food_beverage_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_food_beverage_items.*.pax' => ['nullable', 'integer', 'min:1'],
            'itinerary_food_beverage_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_food_beverage_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_food_beverage_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_food_beverage_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'daily_transport_units' => ['nullable', 'array'],
            'daily_transport_units.*.day_number' => ['required', 'integer', 'min:1'],
            'daily_transport_units.*.transport_unit_id' => ['nullable', 'integer', 'exists:transport_units,id'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $accommodationStays = $this->normalizeAccommodationStays($validated['accommodation_stays'] ?? []);
        $items = $validated['itinerary_items'];
        $activityItems = $validated['itinerary_activity_items'] ?? [];
        $foodBeverageItems = $validated['itinerary_food_beverage_items'] ?? [];
        $dayPoints = $this->normalizeDayPoints(
            (int) ($validated['duration_days'] ?? 1),
            $validated['daily_start_point_types'] ?? [],
            $validated['daily_start_point_items'] ?? [],
            $validated['day_start_times'] ?? [],
            $validated['day_start_travel_minutes'] ?? [],
            $validated['daily_end_point_types'] ?? [],
            $validated['daily_end_point_items'] ?? [],
            $validated['daily_main_experience_types'] ?? [],
            $validated['daily_main_experience_items'] ?? [],
            $items,
            $activityItems,
            $foodBeverageItems
        );
        $transportUnitsByDay = $this->normalizeDailyTransportUnits(
            $validated['daily_transport_units'] ?? [],
            (int) ($validated['duration_days'] ?? 1)
        );
        unset($validated['accommodation_stays']);
        unset($validated['daily_start_point_types']);
        unset($validated['daily_start_point_items']);
        unset($validated['day_start_times']);
        unset($validated['day_start_travel_minutes']);
        unset($validated['daily_end_point_types']);
        unset($validated['daily_end_point_items']);
        unset($validated['daily_main_experience_types']);
        unset($validated['daily_main_experience_items']);
        unset($validated['itinerary_items']);
        unset($validated['itinerary_activity_items']);
        unset($validated['itinerary_food_beverage_items']);
        unset($validated['daily_transport_units']);
        $this->validateScheduleItems($items, (int) $validated['duration_days']);
        $this->validateActivityItems($activityItems, (int) $validated['duration_days']);
        $this->validateFoodBeverageItems($foodBeverageItems, (int) $validated['duration_days']);
        $this->validateAccommodationStays($accommodationStays, (int) $validated['duration_days']);

        $itinerary = Itinerary::query()->create($validated);
        $itinerary->touristAttractions()->sync($this->buildSyncPayload($items));
        $this->syncItineraryActivities($itinerary, $activityItems);
        $this->syncItineraryFoodBeverages($itinerary, $foodBeverageItems);
        $this->syncAccommodationStays($itinerary, $accommodationStays);
        $this->syncDayPoints($itinerary, $dayPoints);
        $this->syncDailyTransportUnits($itinerary, $transportUnitsByDay);

        return redirect()->route('itineraries.index')->with('success', 'Itinerary created successfully.');
    }

    public function edit(Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,agent_price,currency',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,agent_price,currency,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,transport_id,name,vehicle_type,seat_capacity,currency',
            'itineraryTransportUnits.transportUnit.transport:id,name,city,province,destination_id',
            'dayPoints',
            'inquiry:id,inquiry_number,customer_id',
            'accommodations:id,name,category,star_rating,city,province',
            'arrivalTransport:id,name,transport_type,location,city,province',
            'departureTransport:id,name,transport_type,location,city,province',
        ]);
        $touristAttractions = TouristAttraction::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude', 'ideal_visit_minutes']);
        $activities = Activity::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'activity_type', 'duration_minutes', 'agent_price', 'currency']);
        $foodBeverages = FoodBeverage::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'service_type', 'duration_minutes', 'agent_price', 'currency', 'meal_period', 'notes', 'menu_highlights']);
        $accommodations = Accommodation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'star_rating', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude']);
        $airports = Airport::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude']);
        $transportUnits = TransportUnit::query()
            ->with('transport:id,name,city,province,destination_id,is_active')
            ->where('is_active', true)
            ->whereHas('transport', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'transport_id', 'name', 'vehicle_type', 'seat_capacity', 'currency']);
        $inquiries = Inquiry::query()
            ->with([
                'customer:id,name,code',
                'assignedUser:id,name',
            ])
            ->orderByDesc('id')
            ->get([
                'id',
                'inquiry_number',
                'customer_id',
                'source',
                'status',
                'priority',
                'assigned_to',
                'deadline',
                'notes',
                'created_at',
            ]);
        return view('modules.itineraries.edit', compact('itinerary', 'touristAttractions', 'activities', 'foodBeverages', 'accommodations', 'airports', 'transportUnits', 'inquiries'));
    }

    public function destinationSuggestions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 12);

        return response()->json([
            'data' => $this->buildDestinationOptions($keyword, $limit),
        ]);
    }

    public function show(Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude,description',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,agent_price,currency,includes,benefits,notes',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,agent_price,currency,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,transport_id,name,vehicle_type,seat_capacity,currency',
            'itineraryTransportUnits.transportUnit.transport:id,name,city,province,destination_id',
            'dayPoints',
            'dayPoints.startAirport:id,name,location,city,province,latitude,longitude',
            'dayPoints.startAccommodation:id,name,location,city,province,latitude,longitude',
            'dayPoints.endAirport:id,name,location,city,province,latitude,longitude',
            'dayPoints.endAccommodation:id,name,location,city,province,latitude,longitude',
            'inquiry:id,inquiry_number,customer_id',
            'inquiry.customer:id,name',
            'accommodations:id,name,category,star_rating,city,province',
            'arrivalTransport:id,name,transport_type,location,city,province',
            'departureTransport:id,name,transport_type,location,city,province',
        ]);
        $dayGroups = $itinerary->touristAttractions->groupBy(fn ($attraction) => (int) $attraction->pivot->day_number);
        $activityDayGroups = $itinerary->itineraryActivities->groupBy(fn ($item) => (int) $item->day_number);
        $foodBeverageDayGroups = $itinerary->itineraryFoodBeverages->groupBy(fn ($item) => (int) $item->day_number);
        $transportUnitByDay = $itinerary->itineraryTransportUnits->keyBy(fn ($item) => (int) $item->day_number);

        return view('modules.itineraries.show', compact('itinerary', 'dayGroups', 'activityDayGroups', 'foodBeverageDayGroups', 'transportUnitByDay'));
    }

    public function generatePdf(Request $request, Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude,description,gallery_images',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,agent_price,currency,notes,gallery_images',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,agent_price,currency,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'dayPoints',
            'dayPoints.startAirport:id,name,location,city,province',
            'dayPoints.startAccommodation:id,name,location,city,province',
            'dayPoints.endAirport:id,name,location,city,province',
            'dayPoints.endAccommodation:id,name,location,city,province',
            'inquiry:id,inquiry_number,customer_id,status,priority,source,deadline,notes',
            'inquiry.customer:id,name,code',
        ]);

        $scheduleByDay = [];
        $dayPointByDay = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
        $toMinutes = static function (?string $time): ?int {
            $value = substr((string) $time, 0, 5);
            if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
                return null;
            }
            return ((int) substr($value, 0, 2) * 60) + (int) substr($value, 3, 2);
        };
        $fromMinutes = static function (?int $minutes): ?string {
            if (!is_int($minutes)) {
                return null;
            }
            $normalized = max(0, $minutes);
            $hours = (int) floor($normalized / 60);
            $mins = $normalized % 60;
            return str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT);
        };
        $resolvePoint = static function ($dayPoint, string $scope, array $previousEnd = ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown']): array {
            if (!$dayPoint) {
                return $scope === 'start' ? $previousEnd : ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown'];
            }
            if ($scope === 'start') {
                $type = (string) ($dayPoint->start_point_type ?? '');
                if ($type === 'previous_day_end') {
                    return $previousEnd;
                }
                if ($type === 'airport') {
                    return [
                        'name' => (string) ($dayPoint->startAirport?->name ?? 'Not set'),
                        'location' => (string) ($dayPoint->startAirport?->location ?? '-'),
                        'type' => 'Airport',
                    ];
                }
                if ($type === 'accommodation') {
                    return [
                        'name' => (string) ($dayPoint->startAccommodation?->name ?? 'Not set'),
                        'location' => (string) ($dayPoint->startAccommodation?->location ?? '-'),
                        'type' => 'Accommodation',
                    ];
                }
                return ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown'];
            }

            $type = (string) ($dayPoint->end_point_type ?? '');
            if ($type === 'airport') {
                return [
                    'name' => (string) ($dayPoint->endAirport?->name ?? 'Not set'),
                    'location' => (string) ($dayPoint->endAirport?->location ?? '-'),
                    'type' => 'Airport',
                ];
            }
            if ($type === 'accommodation') {
                return [
                    'name' => (string) ($dayPoint->endAccommodation?->name ?? 'Not set'),
                    'location' => (string) ($dayPoint->endAccommodation?->location ?? '-'),
                    'type' => 'Accommodation',
                ];
            }
            return ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown'];
        };
        $previousEndPoint = ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown'];
        for ($day = 1; $day <= (int) $itinerary->duration_days; $day++) {
            $dayPoint = $dayPointByDay[$day] ?? null;
            $mainExperienceType = (string) ($dayPoint?->main_experience_type ?? '');
            if (! in_array($mainExperienceType, ['attraction', 'activity', 'fnb'], true)) {
                $mainExperienceType = '';
            }
            $mainExperienceId = $mainExperienceType === 'attraction'
                ? (int) ($dayPoint?->main_tourist_attraction_id ?? 0)
                : ($mainExperienceType === 'activity'
                    ? (int) ($dayPoint?->main_activity_id ?? 0)
                    : ($mainExperienceType === 'fnb'
                        ? (int) ($dayPoint?->main_food_beverage_id ?? 0)
                        : 0));

            $attractions = $itinerary->touristAttractions
                ->filter(fn ($attraction) => (int) ($attraction->pivot->day_number ?? 0) === $day)
                ->map(function ($attraction) {
                    return [
                        'type' => 'Attraction',
                        'source_type' => 'attraction',
                        'source_id' => (int) $attraction->id,
                        'name' => (string) $attraction->name,
                        'location' => (string) ($attraction->location ?? '-'),
                        'description' => (string) ($attraction->description ?? '-'),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($attraction->gallery_images ?? []),
                        'pax' => null,
                        'start_time' => $attraction->pivot->start_time ? substr((string) $attraction->pivot->start_time, 0, 5) : '--:--',
                        'end_time' => $attraction->pivot->end_time ? substr((string) $attraction->pivot->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next,
                        'visit_order' => (int) ($attraction->pivot->visit_order ?? 999999),
                    ];
                });

            $activities = $itinerary->itineraryActivities
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(function ($item) {
                    return [
                        'type' => 'Activity',
                        'source_type' => 'activity',
                        'source_id' => (int) ($item->activity_id ?? 0),
                        'name' => (string) ($item->activity->name ?? '-'),
                        'location' => (string) ($item->activity->vendor->location ?? '-'),
                        'description' => (string) ($item->activity->notes ?? '-'),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($item->activity->gallery_images ?? []),
                        'pax' => (int) ($item->pax ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                });

            $foodBeverages = $itinerary->itineraryFoodBeverages
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(function ($item) {
                    return [
                        'type' => 'F&B',
                        'source_type' => 'fnb',
                        'source_id' => (int) ($item->food_beverage_id ?? 0),
                        'name' => (string) ($item->foodBeverage->name ?? '-'),
                        'location' => (string) ($item->foodBeverage->vendor->location ?? '-'),
                        'description' => (string) ($item->foodBeverage->notes ?? $item->foodBeverage->menu_highlights ?? '-'),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($item->foodBeverage->gallery_images ?? []),
                        'agent_price' => (float) ($item->foodBeverage->agent_price ?? 0),
                        'currency' => (string) ($item->foodBeverage->currency ?? 'IDR'),
                        'pax' => (int) ($item->pax ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                });

            $items = $attractions->merge($activities)->merge($foodBeverages)
                ->sortBy('visit_order')
                ->values()
                ->map(function (array $item) use ($mainExperienceType, $mainExperienceId) {
                    $item['is_main_experience'] = $mainExperienceType !== ''
                        && $mainExperienceId > 0
                        && (string) ($item['source_type'] ?? '') === $mainExperienceType
                        && (int) ($item['source_id'] ?? 0) === $mainExperienceId;

                    return $item;
                });
            $startPoint = $resolvePoint($dayPoint, 'start', $previousEndPoint);
            $endPoint = $resolvePoint($dayPoint, 'end', ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown']);
            $startTime = $dayPoint && !empty($dayPoint->day_start_time)
                ? substr((string) $dayPoint->day_start_time, 0, 5)
                : ($items->pluck('start_time')->filter(fn ($time) => $time !== '--:--')->first() ?? '--:--');
            $startTravelMinutes = $dayPoint && $dayPoint->day_start_travel_minutes !== null
                ? max(0, (int) $dayPoint->day_start_travel_minutes)
                : null;
            $lastItem = $items->last();
            $lastEndBaseMinutes = $lastItem ? $toMinutes($lastItem['end_time'] ?? null) : null;
            $lastTravelToEnd = $lastItem ? max(0, (int) ($lastItem['travel_minutes_to_next'] ?? 0)) : 0;
            $endTime = $lastEndBaseMinutes !== null
                ? ($fromMinutes($lastEndBaseMinutes + $lastTravelToEnd) ?? '--:--')
                : '--:--';
            $timelineItems = collect([
                [
                    'type' => 'Start Point',
                    'name' => $startPoint['name'],
                    'location' => $startPoint['location'],
                    'description' => '-',
                    'thumbnail_data_uri' => null,
                    'pax' => null,
                    'start_time' => $startTime,
                    'end_time' => null,
                    'travel_minutes_to_next' => $startTravelMinutes,
                    'visit_order' => 0,
                    'point_role' => 'start',
                    'point_type_label' => $startPoint['type'] ?? 'Unknown',
                    'is_main_experience' => false,
                ],
            ])->merge($items)->push([
                'type' => 'End Point',
                'name' => $endPoint['name'],
                'location' => $endPoint['location'],
                'description' => '-',
                'thumbnail_data_uri' => null,
                'pax' => null,
                'start_time' => null,
                'end_time' => $endTime,
                'travel_minutes_to_next' => null,
                'visit_order' => 9999999,
                'point_role' => 'end',
                'point_type_label' => $endPoint['type'] ?? 'Unknown',
                'is_main_experience' => false,
            ])->values();

            $scheduleByDay[] = [
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_travel_minutes' => $startTravelMinutes,
                'items' => $timelineItems,
            ];
            $previousEndPoint = $endPoint;
        }

        $pdf = Pdf::loadView('pdf.itinerary', [
            'itinerary' => $itinerary,
            'scheduleByDay' => $scheduleByDay,
            'companyName' => (string) config('app.name', 'Voyex CRM'),
            'companyTagline' => (string) env('COMPANY_TAGLINE', 'Travel Itinerary & Experience Planner'),
            'companyLogoDataUri' => $this->resolveCompanyLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'itinerary-' . Str::slug($itinerary->title ?: 'untitled') . '.pdf';
        $mode = strtolower((string) $request->query('mode', 'download'));
        if ($mode === 'stream') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    private function resolveGalleryImageDataUri($galleryImages): ?string
    {
        $images = is_array($galleryImages) ? $galleryImages : [];
        foreach ($images as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }
            $thumbnailPath = ImageThumbnailGenerator::thumbnailPathFor($path);
            $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
            if ($thumbnailDataUri) {
                return $thumbnailDataUri;
            }
            $originalDataUri = $this->resolveStorageImageDataUri($path);
            if ($originalDataUri) {
                return $originalDataUri;
            }
        }

        return null;
    }

    private function resolveStorageImageDataUri(string $path): ?string
    {
        $storage = Storage::disk('public');
        if (! $storage->exists($path)) {
            return null;
        }
        $binary = $storage->get($path);
        if ($binary === '') {
            return null;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    private function resolveCompanyLogoDataUri(): string
    {
        $candidates = [
            public_path('images/company-logo.png'),
            public_path('images/logo.png'),
            public_path('logo.png'),
        ];

        foreach ($candidates as $path) {
            if (! File::exists($path)) {
                continue;
            }
            $binary = File::get($path);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        $name = (string) config('app.name', 'VOYEX');
        $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3) ?: 'VYX');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="360" height="90" viewBox="0 0 360 90">
  <rect width="360" height="90" rx="14" fill="#0f172a"/>
  <circle cx="48" cy="45" r="24" fill="#1d4ed8"/>
  <text x="48" y="51" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" font-weight="700" fill="#ffffff">{$initials}</text>
  <text x="86" y="40" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#ffffff">{$name}</text>
  <text x="86" y="60" font-family="Arial, sans-serif" font-size="11" fill="#cbd5e1">Professional Itinerary</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function update(Request $request, Itinerary $itinerary)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'arrival_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'departure_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'inquiry_id' => ['nullable', 'integer', 'exists:inquiries,id'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'duration_nights' => ['required', 'integer', 'min:0', 'lte:duration_days'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'accommodation_stays' => ['nullable', 'array'],
            'accommodation_stays.*.accommodation_id' => ['nullable', 'integer', 'exists:accommodations,id'],
            'accommodation_stays.*.day_number' => ['nullable', 'integer', 'min:1'],
            'accommodation_stays.*.night_count' => ['nullable', 'integer', 'min:1'],
            'accommodation_stays.*.room_count' => ['nullable', 'integer', 'min:1'],
            'daily_start_point_types' => ['nullable', 'array'],
            'daily_start_point_types.*' => ['nullable', 'string', 'in:previous_day_end,accommodation,airport'],
            'daily_start_point_items' => ['nullable', 'array'],
            'day_start_times' => ['nullable', 'array'],
            'day_start_times.*' => ['nullable', 'date_format:H:i'],
            'day_start_travel_minutes' => ['nullable', 'array'],
            'day_start_travel_minutes.*' => ['nullable', 'integer', 'min:0'],
            'daily_end_point_types' => ['nullable', 'array'],
            'daily_end_point_types.*' => ['nullable', 'string', 'in:accommodation,airport'],
            'daily_end_point_items' => ['nullable', 'array'],
            'daily_main_experience_types' => ['nullable', 'array'],
            'daily_main_experience_types.*' => ['nullable', 'string', 'in:attraction,activity,fnb'],
            'daily_main_experience_items' => ['nullable', 'array'],
            'daily_main_experience_items.*' => ['nullable', 'integer', 'min:1'],
            'itinerary_items' => ['required', 'array', 'min:1'],
            'itinerary_items.*.tourist_attraction_id' => ['required', 'integer', 'distinct', 'exists:tourist_attractions,id'],
            'itinerary_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'itinerary_activity_items' => ['nullable', 'array'],
            'itinerary_activity_items.*.activity_id' => ['required', 'integer', 'exists:activities,id'],
            'itinerary_activity_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_activity_items.*.pax' => ['nullable', 'integer', 'min:1'],
            'itinerary_activity_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'itinerary_food_beverage_items' => ['nullable', 'array'],
            'itinerary_food_beverage_items.*.food_beverage_id' => ['required', 'integer', 'exists:food_beverages,id'],
            'itinerary_food_beverage_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_food_beverage_items.*.pax' => ['nullable', 'integer', 'min:1'],
            'itinerary_food_beverage_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_food_beverage_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_food_beverage_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_food_beverage_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'daily_transport_units' => ['nullable', 'array'],
            'daily_transport_units.*.day_number' => ['required', 'integer', 'min:1'],
            'daily_transport_units.*.transport_unit_id' => ['nullable', 'integer', 'exists:transport_units,id'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $accommodationStays = $this->normalizeAccommodationStays($validated['accommodation_stays'] ?? []);
        $items = $validated['itinerary_items'];
        $activityItems = $validated['itinerary_activity_items'] ?? [];
        $foodBeverageItems = $validated['itinerary_food_beverage_items'] ?? [];
        $dayPoints = $this->normalizeDayPoints(
            (int) ($validated['duration_days'] ?? 1),
            $validated['daily_start_point_types'] ?? [],
            $validated['daily_start_point_items'] ?? [],
            $validated['day_start_times'] ?? [],
            $validated['day_start_travel_minutes'] ?? [],
            $validated['daily_end_point_types'] ?? [],
            $validated['daily_end_point_items'] ?? [],
            $validated['daily_main_experience_types'] ?? [],
            $validated['daily_main_experience_items'] ?? [],
            $items,
            $activityItems,
            $foodBeverageItems
        );
        $transportUnitsByDay = $this->normalizeDailyTransportUnits(
            $validated['daily_transport_units'] ?? [],
            (int) ($validated['duration_days'] ?? 1)
        );
        unset($validated['accommodation_stays']);
        unset($validated['daily_start_point_types']);
        unset($validated['daily_start_point_items']);
        unset($validated['day_start_times']);
        unset($validated['day_start_travel_minutes']);
        unset($validated['daily_end_point_types']);
        unset($validated['daily_end_point_items']);
        unset($validated['daily_main_experience_types']);
        unset($validated['daily_main_experience_items']);
        unset($validated['itinerary_items']);
        unset($validated['itinerary_activity_items']);
        unset($validated['itinerary_food_beverage_items']);
        unset($validated['daily_transport_units']);
        $this->validateScheduleItems($items, (int) $validated['duration_days']);
        $this->validateActivityItems($activityItems, (int) $validated['duration_days']);
        $this->validateFoodBeverageItems($foodBeverageItems, (int) $validated['duration_days']);
        $this->validateAccommodationStays($accommodationStays, (int) $validated['duration_days']);

        $itinerary->update($validated);
        $itinerary->touristAttractions()->sync($this->buildSyncPayload($items));
        $this->syncItineraryActivities($itinerary, $activityItems);
        $this->syncItineraryFoodBeverages($itinerary, $foodBeverageItems);
        $this->syncAccommodationStays($itinerary, $accommodationStays);
        $this->syncDayPoints($itinerary, $dayPoints);
        $this->syncDailyTransportUnits($itinerary, $transportUnitsByDay);

        return redirect()->route('itineraries.index')->with('success', 'Itinerary updated successfully.');
    }

    public function destroy(Itinerary $itinerary)
    {
        $itinerary->delete();

        return redirect()->route('itineraries.index')->with('success', 'Itinerary deleted successfully.');
    }

    private function validateScheduleItems(array $items, int $durationDays): void
    {
        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;
            $day = (int) ($item['day_number'] ?? 0);
            if ($day > $durationDays) {
                throw ValidationException::withMessages([
                    "itinerary_items.{$index}.day_number" => "Day on row {$rowNumber} cannot exceed itinerary duration.",
                ]);
            }
        }
    }

    private function buildSyncPayload(array $items): array
    {
        $items = $this->sortScheduleItems($items);
        $durationByAttraction = TouristAttraction::query()
            ->whereIn('id', array_column($items, 'tourist_attraction_id'))
            ->pluck('ideal_visit_minutes', 'id');

        $syncData = [];
        $fallbackVisitOrderByDay = [];

        foreach (array_values($items) as $item) {
            $dayNumber = (int) $item['day_number'];
            $fallbackVisitOrderByDay[$dayNumber] = ($fallbackVisitOrderByDay[$dayNumber] ?? 0) + 1;
            $startTime = $item['start_time'] ?: null;
            $idealMinutes = (int) ($durationByAttraction[(int) $item['tourist_attraction_id']] ?? 120);
            $manualEndTime = $item['end_time'] ?: null;
            $travelMinutes = isset($item['travel_minutes_to_next']) && $item['travel_minutes_to_next'] !== ''
                ? max(0, (int) $item['travel_minutes_to_next'])
                : null;
            $visitOrder = isset($item['visit_order']) && $item['visit_order'] !== ''
                ? max(1, (int) $item['visit_order'])
                : $fallbackVisitOrderByDay[$dayNumber];

            $syncData[(int) $item['tourist_attraction_id']] = [
                'day_number' => $dayNumber,
                'start_time' => $startTime,
                'end_time' => $manualEndTime ?: ($startTime ? $this->calculateEndTimeFromIdealDuration($startTime, $idealMinutes) : null),
                'travel_minutes_to_next' => $travelMinutes,
                'visit_order' => $visitOrder,
            ];
        }

        return $syncData;
    }

    private function sortScheduleItems(array $items): array
    {
        $normalized = [];

        foreach (array_values($items) as $index => $item) {
            $normalized[] = [
                'tourist_attraction_id' => (int) $item['tourist_attraction_id'],
                'day_number' => (int) $item['day_number'],
                'start_time' => $item['start_time'] ?: null,
                'end_time' => $item['end_time'] ?: null,
                'travel_minutes_to_next' => isset($item['travel_minutes_to_next']) && $item['travel_minutes_to_next'] !== ''
                    ? max(0, (int) $item['travel_minutes_to_next'])
                    : null,
                'visit_order' => isset($item['visit_order']) && $item['visit_order'] !== ''
                    ? max(1, (int) $item['visit_order'])
                    : null,
                '_row_order' => $index,
            ];
        }

        usort($normalized, function (array $a, array $b): int {
            if ($a['day_number'] !== $b['day_number']) {
                return $a['day_number'] <=> $b['day_number'];
            }

            $aHasVisitOrder = $a['visit_order'] !== null;
            $bHasVisitOrder = $b['visit_order'] !== null;
            if ($aHasVisitOrder !== $bHasVisitOrder) {
                return $aHasVisitOrder ? -1 : 1;
            }
            if ($a['visit_order'] !== $b['visit_order']) {
                return ($a['visit_order'] ?? 999999) <=> ($b['visit_order'] ?? 999999);
            }

            $aHasStart = $a['start_time'] !== null;
            $bHasStart = $b['start_time'] !== null;
            if ($aHasStart !== $bHasStart) {
                return $aHasStart ? -1 : 1;
            }

            if ($a['start_time'] !== $b['start_time']) {
                return ($a['start_time'] ?? '99:99') <=> ($b['start_time'] ?? '99:99');
            }

            return $a['_row_order'] <=> $b['_row_order'];
        });

        return array_map(function (array $item): array {
            unset($item['_row_order']);
            return $item;
        }, $normalized);
    }

    private function calculateEndTimeFromIdealDuration(string $startTime, int $idealMinutes): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $startTime));
        $startTotal = ($hour * 60) + $minute;
        $endTotal = $startTotal + max(1, $idealMinutes);

        if ($endTotal > 1439) {
            $endTotal = 1439;
        }

        $endHour = intdiv($endTotal, 60);
        $endMinute = $endTotal % 60;

        return sprintf('%02d:%02d', $endHour, $endMinute);
    }

    private function validateActivityItems(array $items, int $durationDays): void
    {
        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;
            $day = (int) ($item['day_number'] ?? 0);
            if ($day > $durationDays) {
                throw ValidationException::withMessages([
                    "itinerary_activity_items.{$index}.day_number" => "Activity day on row {$rowNumber} cannot exceed itinerary duration.",
                ]);
            }
        }
    }

    private function syncItineraryActivities(Itinerary $itinerary, array $items): void
    {
        $itinerary->itineraryActivities()->delete();
        if ($items === []) {
            return;
        }

        $activityDurations = Activity::query()
            ->whereIn('id', array_column($items, 'activity_id'))
            ->pluck('duration_minutes', 'id');

        $payload = [];
        $fallbackVisitOrderByDay = [];
        foreach (array_values($items) as $item) {
            $dayNumber = (int) $item['day_number'];
            $fallbackVisitOrderByDay[$dayNumber] = ($fallbackVisitOrderByDay[$dayNumber] ?? 0) + 1;
            $activityId = (int) $item['activity_id'];
            $startTime = $item['start_time'] ?: null;
            $durationMinutes = (int) ($activityDurations[$activityId] ?? 60);
            $manualEndTime = $item['end_time'] ?: null;
            $travelMinutes = isset($item['travel_minutes_to_next']) && $item['travel_minutes_to_next'] !== ''
                ? max(0, (int) $item['travel_minutes_to_next'])
                : null;
            $visitOrder = isset($item['visit_order']) && $item['visit_order'] !== ''
                ? max(1, (int) $item['visit_order'])
                : $fallbackVisitOrderByDay[$dayNumber];

            $payload[] = [
                'itinerary_id' => $itinerary->id,
                'activity_id' => $activityId,
                'day_number' => $dayNumber,
                'pax' => max(1, (int) ($item['pax'] ?? 1)),
                'start_time' => $startTime,
                'end_time' => $manualEndTime ?: ($startTime ? $this->calculateTimeFromDuration($startTime, $durationMinutes) : null),
                'travel_minutes_to_next' => $travelMinutes,
                'visit_order' => $visitOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $itinerary->itineraryActivities()->insert($payload);
    }

    private function validateFoodBeverageItems(array $items, int $durationDays): void
    {
        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;
            $day = (int) ($item['day_number'] ?? 0);
            if ($day > $durationDays) {
                throw ValidationException::withMessages([
                    "itinerary_food_beverage_items.{$index}.day_number" => "F&B day on row {$rowNumber} cannot exceed itinerary duration.",
                ]);
            }
        }
    }

    private function syncItineraryFoodBeverages(Itinerary $itinerary, array $items): void
    {
        $itinerary->itineraryFoodBeverages()->delete();
        if ($items === []) {
            return;
        }

        $durations = FoodBeverage::query()
            ->whereIn('id', array_column($items, 'food_beverage_id'))
            ->pluck('duration_minutes', 'id');

        $payload = [];
        $fallbackVisitOrderByDay = [];
        foreach (array_values($items) as $item) {
            $dayNumber = (int) $item['day_number'];
            $fallbackVisitOrderByDay[$dayNumber] = ($fallbackVisitOrderByDay[$dayNumber] ?? 0) + 1;
            $foodBeverageId = (int) $item['food_beverage_id'];
            $startTime = $item['start_time'] ?: null;
            $durationMinutes = (int) ($durations[$foodBeverageId] ?? 60);
            $manualEndTime = $item['end_time'] ?: null;
            $travelMinutes = isset($item['travel_minutes_to_next']) && $item['travel_minutes_to_next'] !== ''
                ? max(0, (int) $item['travel_minutes_to_next'])
                : null;
            $visitOrder = isset($item['visit_order']) && $item['visit_order'] !== ''
                ? max(1, (int) $item['visit_order'])
                : $fallbackVisitOrderByDay[$dayNumber];

            $payload[] = [
                'itinerary_id' => $itinerary->id,
                'food_beverage_id' => $foodBeverageId,
                'day_number' => $dayNumber,
                'pax' => max(1, (int) ($item['pax'] ?? 1)),
                'start_time' => $startTime,
                'end_time' => $manualEndTime ?: ($startTime ? $this->calculateTimeFromDuration($startTime, $durationMinutes) : null),
                'travel_minutes_to_next' => $travelMinutes,
                'visit_order' => $visitOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $itinerary->itineraryFoodBeverages()->insert($payload);
    }

    private function validateAccommodationStays(array $stays, int $durationDays): void
    {
        $seen = [];
        foreach ($stays as $index => $stay) {
            $rowNumber = $index + 1;
            $accommodationId = (int) ($stay['accommodation_id'] ?? 0);
            if ($accommodationId <= 0) {
                continue;
            }
            $day = (int) ($stay['day_number'] ?? 0);
            $nights = (int) ($stay['night_count'] ?? 0);
            $rooms = (int) ($stay['room_count'] ?? 0);
            if ($day > $durationDays) {
                throw ValidationException::withMessages([
                    "accommodation_stays.{$index}.day_number" => "Accommodation day on row {$rowNumber} cannot exceed itinerary duration.",
                ]);
            }
            if ($rooms <= 0) {
                throw ValidationException::withMessages([
                    "accommodation_stays.{$index}.room_count" => "Room count on row {$rowNumber} must be at least 1.",
                ]);
            }
            if (($day + $nights - 1) > $durationDays) {
                throw ValidationException::withMessages([
                    "accommodation_stays.{$index}.night_count" => "Accommodation nights on row {$rowNumber} exceed itinerary duration range.",
                ]);
            }

            $comboKey = $accommodationId . ':' . $day;
            if (isset($seen[$comboKey])) {
                throw ValidationException::withMessages([
                    "accommodation_stays.{$index}.day_number" => "Duplicate accommodation assignment on day {$day} is not allowed.",
                ]);
            }
            $seen[$comboKey] = true;
        }
    }

    private function normalizeAccommodationStays(array $stays): array
    {
        $normalized = [];
        $cursorDay = 1;

        foreach (array_values($stays) as $stay) {
            $accommodationId = (int) ($stay['accommodation_id'] ?? 0);
            if ($accommodationId <= 0) {
                continue;
            }

            $nightCount = max(1, (int) ($stay['night_count'] ?? 1));
            $roomCount = max(1, (int) ($stay['room_count'] ?? 1));

            $normalized[] = [
                'accommodation_id' => $accommodationId,
                'day_number' => $cursorDay,
                'night_count' => $nightCount,
                'room_count' => $roomCount,
            ];

            $cursorDay += $nightCount;
        }

        return $normalized;
    }

    private function syncAccommodationStays(Itinerary $itinerary, array $stays): void
    {
        $itinerary->accommodations()->detach();

        foreach (array_values($stays) as $stay) {
            $accommodationId = (int) ($stay['accommodation_id'] ?? 0);
            $dayNumber = max(1, (int) ($stay['day_number'] ?? 1));
            $nightCount = max(1, (int) ($stay['night_count'] ?? 1));
            $roomCount = max(1, (int) ($stay['room_count'] ?? 1));
            if ($accommodationId <= 0) {
                continue;
            }

            $itinerary->accommodations()->attach($accommodationId, [
                'day_number' => $dayNumber,
                'night_count' => $nightCount,
                'room_count' => $roomCount,
            ]);
        }
    }

    private function normalizeDayPoints(
        int $durationDays,
        array $startTypes,
        array $startItems,
        array $dayStartTimes,
        array $dayStartTravelMinutes,
        array $endTypes,
        array $endItems,
        array $mainExperienceTypes,
        array $mainExperienceItems,
        array $attractionItems,
        array $activityItems,
        array $foodBeverageItems
    ): array {
        $rows = [];
        $availableByDay = [];

        foreach ($attractionItems as $item) {
            $day = (int) ($item['day_number'] ?? 0);
            $id = (int) ($item['tourist_attraction_id'] ?? 0);
            if ($day < 1 || $id <= 0) {
                continue;
            }
            $availableByDay[$day]['attraction'][$id] = true;
        }
        foreach ($activityItems as $item) {
            $day = (int) ($item['day_number'] ?? 0);
            $id = (int) ($item['activity_id'] ?? 0);
            if ($day < 1 || $id <= 0) {
                continue;
            }
            $availableByDay[$day]['activity'][$id] = true;
        }
        foreach ($foodBeverageItems as $item) {
            $day = (int) ($item['day_number'] ?? 0);
            $id = (int) ($item['food_beverage_id'] ?? 0);
            if ($day < 1 || $id <= 0) {
                continue;
            }
            $availableByDay[$day]['fnb'][$id] = true;
        }

        for ($day = 1; $day <= $durationDays; $day++) {
            $startType = (string) ($startTypes[$day] ?? ($day === 1 ? 'airport' : 'previous_day_end'));
            if (! in_array($startType, ['previous_day_end', 'accommodation', 'airport'], true)) {
                $startType = $day === 1 ? 'airport' : 'previous_day_end';
            }

            $endType = (string) ($endTypes[$day] ?? ($day === $durationDays ? 'airport' : 'accommodation'));
            if (! in_array($endType, ['accommodation', 'airport'], true)) {
                $endType = $day === $durationDays ? 'airport' : 'accommodation';
            }

            $startItemId = (int) ($startItems[$day] ?? 0);
            $endItemId = (int) ($endItems[$day] ?? 0);
            $dayStartTimeRaw = trim((string) ($dayStartTimes[$day] ?? ''));
            $dayStartTime = $dayStartTimeRaw !== '' ? $dayStartTimeRaw : null;
            $dayStartTravel = isset($dayStartTravelMinutes[$day]) && $dayStartTravelMinutes[$day] !== ''
                ? max(0, (int) $dayStartTravelMinutes[$day])
                : 0;
            $mainExperienceType = (string) ($mainExperienceTypes[$day] ?? '');
            if (! in_array($mainExperienceType, ['attraction', 'activity', 'fnb'], true)) {
                $mainExperienceType = '';
            }
            $mainExperienceItemId = (int) ($mainExperienceItems[$day] ?? 0);

            if (in_array($startType, ['accommodation', 'airport'], true) && $startItemId <= 0) {
                throw ValidationException::withMessages([
                    "daily_start_point_items.{$day}" => "Start point item on day {$day} is required.",
                ]);
            }
            if (in_array($endType, ['accommodation', 'airport'], true) && $endItemId <= 0) {
                throw ValidationException::withMessages([
                    "daily_end_point_items.{$day}" => "End point item on day {$day} is required.",
                ]);
            }
            if ($mainExperienceType !== '' && $mainExperienceItemId <= 0) {
                throw ValidationException::withMessages([
                    "daily_main_experience_items.{$day}" => "Main experience item on day {$day} is required.",
                ]);
            }
            if ($mainExperienceType !== '' && $mainExperienceItemId > 0) {
                $isExistsInDay = isset($availableByDay[$day][$mainExperienceType][$mainExperienceItemId]);
                if (! $isExistsInDay) {
                    throw ValidationException::withMessages([
                        "daily_main_experience_items.{$day}" => "Main experience on day {$day} must reference an item from the same day schedule.",
                    ]);
                }
            }

            $rows[] = [
                'day_number' => $day,
                'day_start_time' => $dayStartTime,
                'day_start_travel_minutes' => $dayStartTravel,
                'main_experience_type' => $mainExperienceType !== '' ? $mainExperienceType : null,
                'main_tourist_attraction_id' => $mainExperienceType === 'attraction' ? $mainExperienceItemId : null,
                'main_activity_id' => $mainExperienceType === 'activity' ? $mainExperienceItemId : null,
                'main_food_beverage_id' => $mainExperienceType === 'fnb' ? $mainExperienceItemId : null,
                'start_point_type' => $startType,
                'start_airport_id' => $startType === 'airport' ? $startItemId : null,
                'start_accommodation_id' => $startType === 'accommodation' ? $startItemId : null,
                'end_point_type' => $endType,
                'end_airport_id' => $endType === 'airport' ? $endItemId : null,
                'end_accommodation_id' => $endType === 'accommodation' ? $endItemId : null,
            ];
        }

        return $rows;
    }

    private function syncDayPoints(Itinerary $itinerary, array $rows): void
    {
        $itinerary->dayPoints()->delete();
        if ($rows === []) {
            return;
        }

        $payload = array_map(function (array $row) use ($itinerary): array {
            return [
                'itinerary_id' => $itinerary->id,
                'day_number' => (int) $row['day_number'],
                'day_start_time' => $row['day_start_time'],
                'day_start_travel_minutes' => (int) ($row['day_start_travel_minutes'] ?? 0),
                'main_experience_type' => $row['main_experience_type'],
                'main_tourist_attraction_id' => $row['main_tourist_attraction_id'],
                'main_activity_id' => $row['main_activity_id'],
                'main_food_beverage_id' => $row['main_food_beverage_id'],
                'start_point_type' => $row['start_point_type'],
                'start_airport_id' => $row['start_airport_id'],
                'start_accommodation_id' => $row['start_accommodation_id'],
                'end_point_type' => $row['end_point_type'],
                'end_airport_id' => $row['end_airport_id'],
                'end_accommodation_id' => $row['end_accommodation_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $rows);

        $itinerary->dayPoints()->insert($payload);
    }

    private function normalizeDailyTransportUnits(array $rows, int $durationDays): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            $day = (int) ($row['day_number'] ?? 0);
            if ($day < 1 || $day > $durationDays) {
                continue;
            }
            $transportUnitId = (int) ($row['transport_unit_id'] ?? 0);
            if ($transportUnitId <= 0) {
                continue;
            }
            $normalized[] = [
                'day_number' => $day,
                'transport_unit_id' => $transportUnitId,
            ];
        }

        usort($normalized, fn ($a, $b) => $a['day_number'] <=> $b['day_number']);

        $byDay = [];
        foreach ($normalized as $row) {
            $byDay[$row['day_number']] = $row;
        }

        return array_values($byDay);
    }

    private function syncDailyTransportUnits(Itinerary $itinerary, array $rows): void
    {
        $itinerary->itineraryTransportUnits()->delete();
        if ($rows === []) {
            return;
        }

        $payload = array_map(function (array $row) use ($itinerary): array {
            return [
                'itinerary_id' => $itinerary->id,
                'transport_unit_id' => (int) $row['transport_unit_id'],
                'day_number' => (int) $row['day_number'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $rows);

        $itinerary->itineraryTransportUnits()->insert($payload);
    }

    private function calculateTimeFromDuration(string $startTime, int $durationMinutes): string
    {
        $start = Carbon::createFromFormat('H:i', $startTime);
        return $start->addMinutes(max(1, $durationMinutes))->format('H:i');
    }

    /**
     * @return array<int, string>
     */
    private function buildDestinationOptions(string $keyword = '', int $limit = 12): array
    {
        $keyword = trim($keyword);
        $fromMasterDestination = Destination::query()
            ->select('province')
            ->where('is_active', true)
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->when($keyword !== '', fn ($q) => $q->where('province', 'like', '%' . $keyword . '%'))
            ->orderBy('province')
            ->limit($limit)
            ->pluck('province');

        $sources = [
            [TouristAttraction::class, 'province'],
            [Accommodation::class, 'province'],
            [Airport::class, 'province'],
            [Vendor::class, 'province'],
            [Transport::class, 'province'],
        ];

        $items = collect($fromMasterDestination);
        foreach ($sources as [$model, $column]) {
            $query = $model::query()
                ->select($column)
                ->whereNotNull($column)
                ->where($column, '!=', '');
            if ($keyword !== '') {
                $query->where($column, 'like', '%' . $keyword . '%');
            }
            $items = $items->merge(
                $query->distinct()
                    ->orderBy($column)
                    ->limit($limit)
                    ->pluck($column)
            );
        }

        return $items
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->map(fn ($value) => preg_replace('/\s+/', ' ', $value))
            ->unique(fn ($value) => mb_strtolower((string) $value))
            ->sort()
            ->take($limit)
            ->values()
            ->all();
    }

}



