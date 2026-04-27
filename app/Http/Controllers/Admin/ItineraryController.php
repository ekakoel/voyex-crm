<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesActivityTimelineAjax;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Airport;
use App\Models\ActivityLog;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\Inquiry;
use App\Models\IslandTransfer;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Models\TouristAttraction;
use App\Models\TransportUnit;
use App\Services\ActivityAuditLogger;
use App\Services\QuotationItinerarySyncService;
use App\Models\Vendor;
use App\Support\ImageThumbnailGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItineraryController extends Controller
{
    use HandlesActivityTimelineAjax;

    public function __construct(
        private readonly ActivityAuditLogger $activityAuditLogger,
        private readonly QuotationItinerarySyncService $quotationItinerarySyncService
    ) {
    }

    public function index()
    {
        $query = Itinerary::query()
            ->withTrashed()
            ->with([
                'creator:id,name',
                'destination:id,name',
                'touristAttractions:id,name',
                'inquiry:id,inquiry_number,customer_id',
                'inquiry.customer:id,name',
                'quotation:id,itinerary_id,status',
            ]);

        $query->when(request('title'), fn ($q) => $q->where('title', 'like', '%'.request('title').'%'));
        $query->when(request('destination_id'), function ($q) {
            $destinationId = (int) request('destination_id');
            if ($destinationId <= 0) {
                return;
            }

            if (Schema::hasColumn('itineraries', 'destination_id')) {
                $q->where('destination_id', $destinationId);
                return;
            }

            $destinationName = Destination::query()
                ->whereKey($destinationId)
                ->value('name');

            if ($destinationName) {
                $q->where('destination', $destinationName);
            }
        });
        $query->when(request('duration'), function ($q) {
            $duration = (int) request('duration');
            if ($duration > 0) {
                $q->where('duration_days', $duration);
            }
        });

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $itineraries = $query
            ->orderByDesc('itineraries.created_at')
            ->orderByDesc('itineraries.id')
            ->paginate($perPage)
            ->withQueryString();
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.itineraries.index', compact('itineraries', 'destinations'));
    }

    public function create(Request $request)
    {
        $touristAttractions = TouristAttraction::query()
            ->with('destination:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'source', 'destination_id', 'latitude', 'longitude', 'ideal_visit_minutes']);
        $activities = Activity::query()
            ->with([
                'vendor:id,name,city,province,location,latitude,longitude,destination_id',
                'vendor.destination:id,name,city,province',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'activity_type', 'duration_minutes', 'adult_publish_rate', 'child_publish_rate']);
        $islandTransfers = IslandTransfer::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'vendor_id',
                'name',
                'transfer_type',
                'departure_point_name',
                'departure_latitude',
                'departure_longitude',
                'arrival_point_name',
                'arrival_latitude',
                'arrival_longitude',
                'route_geojson',
                'duration_minutes',
                'gallery_images',
            ]);
        $foodBeverages = FoodBeverage::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'service_type', 'duration_minutes', 'publish_rate', 'meal_period', 'notes', 'menu_highlights']);
        $hotels = Hotel::query()
            ->where('status', 'active')
            ->with([
                'rooms' => function ($query) {
                    $query->orderBy('rooms')
                        ->select(['id', 'hotels_id', 'rooms', 'view']);
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'city', 'province', 'destination_id', 'latitude', 'longitude']);
        $airports = Airport::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude']);
        $transportUnits = TransportUnit::query()
            ->with([
                'transport:id,name,is_active,vendor_id',
                'transport.vendor:id,name,location,destination_id,city,province',
                'transport.vendor.destination:id,name,city,province',
            ])
            ->where('is_active', true)
            ->whereHas('transport', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'seat_capacity', 'vendor_id']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);
        $inquiries = Inquiry::query()
            ->with([
                'customer:id,name,code',
                'assignedUser:id,name',
                'followUps' => function ($query) {
                    $query->latest('due_date')->limit(1);
                },
            ])
            ->withCount('itineraries')
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

        return view('modules.itineraries.create', compact('touristAttractions', 'activities', 'islandTransfers', 'foodBeverages', 'hotels', 'airports', 'transportUnits', 'destinations', 'inquiries', 'prefillInquiryId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:255'],
            'arrival_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'departure_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'inquiry_id' => ['nullable', 'integer', 'exists:inquiries,id'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:7'],
            'duration_nights' => ['required', 'integer', 'min:0', 'max:6', 'lte:duration_days'],
            'description' => ['nullable', 'string'],
            'itinerary_include' => ['nullable', 'string'],
            'itinerary_exclude' => ['nullable', 'string'],
            'daily_start_point_types' => ['nullable', 'array'],
            'daily_start_point_types.*' => ['nullable', 'string', Rule::in(['', 'previous_day_end', 'hotel', 'airport'])],
            'daily_start_point_items' => ['nullable', 'array'],
            'daily_start_point_room_ids' => ['nullable', 'array'],
            'daily_start_point_room_ids.*' => ['nullable', 'integer'],
            'daily_start_point_room_counts' => ['nullable', 'array'],
            'daily_start_point_room_counts.*' => ['nullable', 'integer', 'min:1'],
            'daily_start_hotel_booking_modes' => ['nullable', 'array'],
            'daily_start_hotel_booking_modes.*' => ['nullable', 'string', 'in:arranged,self'],
            'day_start_times' => ['nullable', 'array'],
            'day_start_times.*' => ['nullable', 'date_format:H:i'],
            'day_start_travel_minutes' => ['nullable', 'array'],
            'day_start_travel_minutes.*' => ['nullable', 'integer', 'min:0'],
            'daily_end_point_types' => ['nullable', 'array'],
            'daily_end_point_types.*' => ['nullable', 'string', Rule::in(['', 'hotel', 'airport'])],
            'daily_end_point_items' => ['nullable', 'array'],
            'daily_end_point_room_ids' => ['nullable', 'array'],
            'daily_end_point_room_ids.*' => ['nullable', 'integer'],
            'daily_end_point_room_counts' => ['nullable', 'array'],
            'daily_end_point_room_counts.*' => ['nullable', 'integer', 'min:1'],
            'daily_end_hotel_booking_modes' => ['nullable', 'array'],
            'daily_end_hotel_booking_modes.*' => ['nullable', 'string', 'in:arranged,self'],
            'daily_main_experience_types' => ['nullable', 'array'],
            'daily_main_experience_types.*' => ['nullable', 'string', 'in:attraction,activity,fnb'],
            'daily_main_experience_items' => ['nullable', 'array'],
            'daily_main_experience_items.*' => ['nullable', 'integer', 'min:1'],
            'hotel_stays' => ['nullable', 'array'],
            'hotel_stays.*.hotel_id' => ['required', 'integer', 'exists:hotels,id'],
            'hotel_stays.*.day_number' => ['required', 'integer', 'min:1'],
            'hotel_stays.*.night_count' => ['required', 'integer', 'min:1'],
            'hotel_stays.*.room_count' => ['required', 'integer', 'min:1'],
            'itinerary_items' => ['nullable', 'array'],
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
            'itinerary_activity_items.*.pax_adult' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.pax_child' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'itinerary_island_transfer_items' => ['nullable', 'array'],
            'itinerary_island_transfer_items.*.island_transfer_id' => ['required', 'integer', 'exists:island_transfers,id'],
            'itinerary_island_transfer_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_island_transfer_items.*.pax' => ['nullable', 'integer', 'min:1'],
            'itinerary_island_transfer_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_island_transfer_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_island_transfer_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_island_transfer_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
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
            'daily_transport_units.*.transport_unit_id' => ['nullable', 'integer', 'exists:transports,id'],
        ]);

        // Business rule: new itinerary must always start as active.
        $validated['is_active'] = true;
        $validated['created_by'] = auth()->id();
        $validated['status'] = Itinerary::STATUS_PENDING;
        $validated['destination_id'] = $this->resolveDestinationId($validated['destination'] ?? null);
        $items = $validated['itinerary_items'] ?? [];
        $activityItems = $validated['itinerary_activity_items'] ?? [];
        $islandTransferItems = $validated['itinerary_island_transfer_items'] ?? [];
        $foodBeverageItems = $validated['itinerary_food_beverage_items'] ?? [];
        $hotelStays = $this->normalizeHotelStays(
            $validated['hotel_stays'] ?? [],
            (int) ($validated['duration_days'] ?? 1)
        );
        $dayPoints = $this->normalizeDayPoints(
            (int) ($validated['duration_days'] ?? 1),
            $validated['daily_start_point_types'] ?? [],
            $validated['daily_start_point_items'] ?? [],
            $validated['daily_start_point_room_ids'] ?? [],
            $validated['daily_start_point_room_counts'] ?? [],
            $validated['daily_start_hotel_booking_modes'] ?? [],
            $validated['day_start_times'] ?? [],
            $validated['day_start_travel_minutes'] ?? [],
            $validated['daily_end_point_types'] ?? [],
            $validated['daily_end_point_items'] ?? [],
            $validated['daily_end_point_room_ids'] ?? [],
            $validated['daily_end_point_room_counts'] ?? [],
            $validated['daily_end_hotel_booking_modes'] ?? [],
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
        unset($validated['daily_start_point_types']);
        unset($validated['daily_start_point_items']);
        unset($validated['daily_start_point_room_ids']);
        unset($validated['daily_start_point_room_counts']);
        unset($validated['daily_start_hotel_booking_modes']);
        unset($validated['day_start_times']);
        unset($validated['day_start_travel_minutes']);
        unset($validated['daily_end_point_types']);
        unset($validated['daily_end_point_items']);
        unset($validated['daily_end_point_room_ids']);
        unset($validated['daily_end_point_room_counts']);
        unset($validated['daily_end_hotel_booking_modes']);
        unset($validated['daily_main_experience_types']);
        unset($validated['daily_main_experience_items']);
        unset($validated['hotel_stays']);
        unset($validated['itinerary_items']);
        unset($validated['itinerary_activity_items']);
        unset($validated['itinerary_island_transfer_items']);
        unset($validated['itinerary_food_beverage_items']);
        unset($validated['daily_transport_units']);
        $this->validateScheduleItems($items, (int) $validated['duration_days']);
        $this->validateActivityItems($activityItems, (int) $validated['duration_days']);
        $this->validateIslandTransferItems($islandTransferItems, (int) $validated['duration_days']);
        $this->validateFoodBeverageItems($foodBeverageItems, (int) $validated['duration_days']);

        $itinerary = Itinerary::withoutActivityLogging(function () use ($validated): Itinerary {
            return Itinerary::query()->create($validated);
        });
        $itinerary->touristAttractions()->sync($this->buildSyncPayload($items));
        $this->syncItineraryActivities($itinerary, $activityItems);
        $this->syncItineraryIslandTransfers($itinerary, $islandTransferItems);
        $this->syncItineraryFoodBeverages($itinerary, $foodBeverageItems);
        $this->syncDayPoints($itinerary, $dayPoints);
        $this->syncHotelStays($itinerary, $hotelStays);
        $this->syncDailyTransportUnits($itinerary, $transportUnitsByDay);
        $this->activityAuditLogger->logCreated($itinerary, $this->buildItineraryAuditSnapshot($itinerary), 'Itinerary');

        $this->syncInquiryProcessedStatus($itinerary->inquiry_id);

        return redirect()->route('itineraries.show', $itinerary)->with('success', 'Itinerary created successfully.');
    }

    public function duplicate(Itinerary $itinerary)
    {
        $userId = (int) auth()->id();
        if ($userId <= 0) {
            return redirect()
                ->route('itineraries.index')
                ->with('error', 'Unauthenticated request.');
        }

        $duplicateLockSeconds = 8;
        $duplicateLockKey = sprintf('itinerary-duplicate:%d:%d', $userId, (int) $itinerary->id);
        if (! Cache::add($duplicateLockKey, now()->toIso8601String(), $duplicateLockSeconds)) {
            return redirect()
                ->back()
                ->with('error', 'Duplicate request is still being processed. Please wait a few seconds and try again.');
        }

        $itinerary->load([
            'touristAttractions',
            'itineraryActivities',
            'itineraryIslandTransfers',
            'itineraryFoodBeverages',
            'itineraryTransportUnits',
            'dayPoints',
            'hotels',
        ]);

        try {
            $duplicated = DB::transaction(function () use ($itinerary): Itinerary {
                $newItinerary = Itinerary::withoutActivityLogging(function () use ($itinerary): Itinerary {
                    return Itinerary::query()->create([
                        'inquiry_id' => $itinerary->inquiry_id,
                        'created_by' => auth()->id(),
                        'title' => $this->buildDuplicatedTitle((string) $itinerary->title),
                        'order_number' => $itinerary->order_number,
                        'destination' => $itinerary->destination,
                        'destination_id' => $itinerary->destination_id,
                        'arrival_transport_id' => $itinerary->arrival_transport_id,
                        'departure_transport_id' => $itinerary->departure_transport_id,
                        'duration_days' => $itinerary->duration_days,
                        'duration_nights' => $itinerary->duration_nights,
                        'description' => $itinerary->description,
                        'itinerary_include' => $itinerary->itinerary_include,
                        'itinerary_exclude' => $itinerary->itinerary_exclude,
                        'is_active' => true,
                        'status' => Itinerary::STATUS_PENDING,
                    ]);
                });

                $attractionSync = $itinerary->touristAttractions
                    ->mapWithKeys(fn ($attraction) => [
                        (int) $attraction->id => [
                            'day_number' => (int) ($attraction->pivot->day_number ?? 1),
                            'start_time' => $attraction->pivot->start_time,
                            'end_time' => $attraction->pivot->end_time,
                            'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next !== null
                                ? (int) $attraction->pivot->travel_minutes_to_next
                                : null,
                            'visit_order' => (int) ($attraction->pivot->visit_order ?? 1),
                        ],
                    ])
                    ->all();

                if ($attractionSync !== []) {
                    $newItinerary->touristAttractions()->sync($attractionSync);
                }

                $activityRows = $itinerary->itineraryActivities
                ->map(fn ($item) => [
                    'itinerary_id' => $newItinerary->id,
                    'activity_id' => (int) $item->activity_id,
                    'day_number' => (int) $item->day_number,
                    'pax' => $item->pax !== null ? (int) $item->pax : null,
                    'pax_adult' => $item->pax_adult !== null ? (int) $item->pax_adult : null,
                    'pax_child' => $item->pax_child !== null ? (int) $item->pax_child : null,
                    'start_time' => $item->start_time,
                    'end_time' => $item->end_time,
                    'travel_minutes_to_next' => $item->travel_minutes_to_next !== null ? (int) $item->travel_minutes_to_next : null,
                    'visit_order' => (int) ($item->visit_order ?? 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

                if ($activityRows !== []) {
                    $newItinerary->itineraryActivities()->insert($activityRows);
                }

                $islandTransferRows = $itinerary->itineraryIslandTransfers
                ->map(fn ($item) => [
                    'itinerary_id' => $newItinerary->id,
                    'island_transfer_id' => (int) $item->island_transfer_id,
                    'day_number' => (int) $item->day_number,
                    'pax' => $item->pax !== null ? (int) $item->pax : null,
                    'start_time' => $item->start_time,
                    'end_time' => $item->end_time,
                    'travel_minutes_to_next' => $item->travel_minutes_to_next !== null ? (int) $item->travel_minutes_to_next : null,
                    'visit_order' => (int) ($item->visit_order ?? 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

                if ($islandTransferRows !== []) {
                    $newItinerary->itineraryIslandTransfers()->insert($islandTransferRows);
                }

                $foodRows = $itinerary->itineraryFoodBeverages
                ->map(fn ($item) => [
                    'itinerary_id' => $newItinerary->id,
                    'food_beverage_id' => (int) $item->food_beverage_id,
                    'day_number' => (int) $item->day_number,
                    'pax' => $item->pax !== null ? (int) $item->pax : null,
                    'start_time' => $item->start_time,
                    'end_time' => $item->end_time,
                    'meal_type' => $item->meal_type,
                    'travel_minutes_to_next' => $item->travel_minutes_to_next !== null ? (int) $item->travel_minutes_to_next : null,
                    'visit_order' => (int) ($item->visit_order ?? 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

                if ($foodRows !== []) {
                    $newItinerary->itineraryFoodBeverages()->insert($foodRows);
                }

                $dayPointRows = $itinerary->dayPoints
                ->map(fn ($point) => [
                    'itinerary_id' => $newItinerary->id,
                    'day_number' => (int) $point->day_number,
                    'day_start_time' => $point->day_start_time,
                    'day_start_travel_minutes' => (int) ($point->day_start_travel_minutes ?? 0),
                    'day_include' => $point->day_include,
                    'day_exclude' => $point->day_exclude,
                    'main_experience_type' => $point->main_experience_type,
                    'main_tourist_attraction_id' => $point->main_tourist_attraction_id,
                    'main_activity_id' => $point->main_activity_id,
                    'main_food_beverage_id' => $point->main_food_beverage_id,
                    'start_point_type' => $point->start_point_type,
                    'start_airport_id' => $point->start_airport_id,
                    'start_hotel_id' => $point->start_hotel_id,
                    'start_hotel_room_id' => $point->start_hotel_room_id,
                    'start_hotel_booking_mode' => $point->start_hotel_booking_mode,
                    'end_point_type' => $point->end_point_type,
                    'end_airport_id' => $point->end_airport_id,
                    'end_hotel_id' => $point->end_hotel_id,
                    'end_hotel_room_id' => $point->end_hotel_room_id,
                    'end_hotel_booking_mode' => $point->end_hotel_booking_mode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

                if ($dayPointRows !== []) {
                    $newItinerary->dayPoints()->insert($dayPointRows);
                }

                $transportRows = $itinerary->itineraryTransportUnits
                ->map(fn ($item) => [
                    'itinerary_id' => $newItinerary->id,
                    'transport_unit_id' => (int) $item->transport_unit_id,
                    'day_number' => (int) $item->day_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

                if ($transportRows !== []) {
                    $newItinerary->itineraryTransportUnits()->insert($transportRows);
                }

                if (Schema::hasTable('hotel_itinerary')) {
                    $hotelRows = $itinerary->hotels
                        ->map(fn ($hotel) => [
                            'itinerary_id' => $newItinerary->id,
                            'hotel_id' => (int) $hotel->id,
                            'day_number' => (int) ($hotel->pivot->day_number ?? 1),
                            'night_count' => (int) ($hotel->pivot->night_count ?? 1),
                            'room_count' => (int) ($hotel->pivot->room_count ?? 1),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                        ->values()
                        ->all();

                    if ($hotelRows !== []) {
                        DB::table('hotel_itinerary')->insert($hotelRows);
                    }
                }

                return $newItinerary;
            });

            ActivityLog::query()->create([
                'user_id' => auth()->id(),
                'module' => 'Itinerary',
                'action' => 'duplicated_from',
                'subject_id' => $duplicated->id,
                'subject_type' => $duplicated->getMorphClass(),
                'properties' => [
                    'changes' => [
                        [
                            'field' => 'source_itinerary',
                            'label' => 'Source Itinerary',
                            'from' => null,
                            'to' => sprintf(
                                '#%d - %s',
                                (int) $itinerary->id,
                                (string) ($itinerary->title ?? '-')
                            ),
                        ],
                    ],
                    'source_itinerary_id' => (int) $itinerary->id,
                    'source_itinerary_title' => (string) ($itinerary->title ?? ''),
                ],
            ]);
            $this->syncInquiryProcessedStatus($duplicated->inquiry_id);

            return redirect()
                ->route('itineraries.edit', $duplicated)
                ->with('success', 'Itinerary duplicated successfully. Please review and save the updated data.');
        } catch (\Throwable $e) {
            Cache::forget($duplicateLockKey);
            throw $e;
        }
    }

    public function edit(Request $request, Itinerary $itinerary)
    {
        $itinerary->loadMissing(['quotation:id,itinerary_id,status']);
        if (! $this->canManageItinerary($itinerary, 'update')) {
            return $this->denyItineraryMutation($itinerary);
        }
        if ($itinerary->isFinal()) {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'This itinerary is final and cannot be edited.');
        }
        if ($this->isItineraryLockedByQuotation($itinerary)) {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'Itinerary cannot be edited because the related quotation is approved/final.');
        }
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,adult_publish_rate,child_publish_rate',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryIslandTransfers.islandTransfer:id,vendor_id,name,transfer_type,departure_point_name,departure_latitude,departure_longitude,arrival_point_name,arrival_latitude,arrival_longitude,route_geojson,duration_minutes,gallery_images',
            'itineraryIslandTransfers.islandTransfer.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,publish_rate,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,name,transport_type,brand_model,seat_capacity,luggage_capacity,air_conditioned,with_driver,images',
            'itineraryTransportUnits.transportUnit.transport:id,name,transport_type,images,brand_model,seat_capacity,luggage_capacity,air_conditioned,with_driver',
            'dayPoints',
            'inquiry:id,inquiry_number,customer_id',
                        'arrivalTransport:id,name,transport_type',
            'departureTransport:id,name,transport_type',
        ]);
        $touristAttractions = TouristAttraction::query()
            ->with('destination:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'source', 'destination_id', 'latitude', 'longitude', 'ideal_visit_minutes']);
        $activities = Activity::query()
            ->with([
                'vendor:id,name,city,province,location,latitude,longitude,destination_id',
                'vendor.destination:id,name,city,province',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'activity_type', 'duration_minutes', 'adult_publish_rate', 'child_publish_rate']);
        $islandTransfers = IslandTransfer::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'vendor_id',
                'name',
                'transfer_type',
                'departure_point_name',
                'departure_latitude',
                'departure_longitude',
                'arrival_point_name',
                'arrival_latitude',
                'arrival_longitude',
                'route_geojson',
                'duration_minutes',
                'gallery_images',
            ]);
        $foodBeverages = FoodBeverage::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'service_type', 'duration_minutes', 'publish_rate', 'meal_period', 'notes', 'menu_highlights']);
        $hotels = Hotel::query()
            ->where('status', 'active')
            ->with([
                'rooms' => function ($query) {
                    $query->orderBy('rooms')
                        ->select(['id', 'hotels_id', 'rooms', 'view']);
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'city', 'province', 'destination_id', 'latitude', 'longitude']);
        $airports = Airport::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude']);
        $transportUnits = TransportUnit::query()
            ->with([
                'transport:id,name,transport_type,is_active,vendor_id',
                'transport.vendor:id,name,location,destination_id,city,province',
                'transport.vendor.destination:id,name,city,province',
            ])
            ->where('is_active', true)
            ->whereHas('transport', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'seat_capacity', 'vendor_id']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);
        $inquiries = Inquiry::query()
            ->with([
                'customer:id,name,code',
                'assignedUser:id,name',
                'followUps' => function ($query) {
                    $query->latest('due_date')->limit(1);
                },
            ])
            ->withCount('itineraries')
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
        $activityLogs = $itinerary->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activityLogs);
        }

        return view('modules.itineraries.edit', compact('itinerary', 'touristAttractions', 'activities', 'islandTransfers', 'foodBeverages', 'hotels', 'airports', 'transportUnits', 'destinations', 'inquiries', 'activityLogs'));
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

    public function manualItemValidationNotifications(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'enabled' => false,
                'count' => 0,
                'latest' => null,
            ], 401);
        }

        if (! $user->can('itineraries.manual_item_queue.view')) {
            return response()->json([
                'enabled' => false,
                'count' => 0,
                'latest' => null,
            ]);
        }

        $baseQuery = $this->pendingManualItemValidationQuery($user);

        $count = (clone $baseQuery)->count();
        $latest = (clone $baseQuery)->latest('id')->first();
        $latestProperties = is_array($latest?->properties) ? $latest->properties : [];

        return response()->json([
            'enabled' => true,
            'count' => (int) $count,
            'latest' => $latest ? [
                'id' => (int) $latest->id,
                'item_type' => (string) ($latestProperties['item_type'] ?? ''),
                'item_name' => (string) ($latestProperties['item_name'] ?? ''),
                'creator_name' => (string) ($latestProperties['creator_name'] ?? ''),
                'edit_url' => (string) ($latestProperties['edit_url'] ?? ''),
                'created_at' => optional($latest->created_at)->toIso8601String(),
            ] : null,
        ]);
    }

    public function manualItemValidationQueue(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->can('itineraries.manual_item_queue.view'), 403);

        $logs = $this->pendingManualItemValidationQuery($user)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('editor.manual-item-queue', compact('logs'));
    }

    public function markManualItemValidated(Request $request, ActivityLog $activityLog)
    {
        $user = $request->user();
        abort_unless($user && $user->can('itineraries.manual_item_queue.validate'), 403);

        $isManualCreatedLog = (string) ($activityLog->module ?? '') === 'itinerary_day_planner'
            && (string) ($activityLog->action ?? '') === 'manual_item_created';
        if (! $isManualCreatedLog) {
            return back()->with('error', 'Selected item is not part of manual item validation queue.');
        }

        $properties = is_array($activityLog->properties) ? $activityLog->properties : [];
        if (! empty($properties['validated_at'])) {
            return back()->with('success', 'Item is already marked as validated.');
        }

        $properties['validated_at'] = now()->toIso8601String();
        $properties['validated_by'] = (int) ($user->id ?? 0);
        $properties['validated_by_name'] = (string) ($user->name ?? 'Editor');
        $properties['requires_validation'] = false;
        $activityLog->properties = $properties;
        $activityLog->save();

        return back()->with('success', 'Manual item marked as validated.');
    }

    public function activitySuggestions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $destination = trim((string) ($validated['destination'] ?? ''));
        $region = trim((string) ($validated['region'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 12);

        return response()->json([
            'data' => $this->buildActivitySuggestionOptions($keyword, $destination, $region, $limit),
        ]);
    }

    public function storeActivitySuggestion(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:1440'],
        ]);

        ['activity_name' => $activityName, 'region' => $regionName, 'vendor' => $vendorName] =
            $this->parseManualActivityInputFormat((string) ($validated['name'] ?? ''));

        $existing = Activity::query()
            ->with('vendor:id,name,city,province,latitude,longitude')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($activityName)])
            ->where('is_active', true)
            ->whereHas('vendor', function ($query) use ($vendorName) {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($vendorName)]);
            })
            ->latest('id')
            ->first();
        if ($existing) {
            return response()->json([
                'created' => false,
                'data' => $this->formatActivitySuggestionItem($existing),
            ]);
        }

        $vendor = $this->resolveOrCreateVendorForManualActivity($vendorName, $regionName);

        $activityType = $this->resolveOrCreateActivityType('Manual Input');
        $durationMinutes = (int) ($validated['duration_minutes'] ?? 60);
        $durationMinutes = max(15, min(1440, $durationMinutes));

        $activity = Activity::query()->create([
            'vendor_id' => (int) $vendor->id,
            'name' => $activityName,
            'activity_type' => (string) $activityType->name,
            'activity_type_id' => (int) $activityType->id,
            'duration_minutes' => $durationMinutes,
            'adult_contract_rate' => 0,
            'child_contract_rate' => 0,
            'adult_markup_type' => 'fixed',
            'adult_markup' => 0,
            'child_markup_type' => 'fixed',
            'child_markup' => 0,
            'adult_publish_rate' => 0,
            'child_publish_rate' => 0,
            'contract_price' => 0,
            'notes' => 'Draft created from Itinerary Day Planner quick add. Format source: Activity, Region, Vendor. Please complete details and verify pricing.',
            'is_active' => true,
        ]);
        $activity->load([
            'vendor:id,name,city,province,latitude,longitude,destination_id',
            'vendor.destination:id,name,city,province',
        ]);
        $this->logManualItemCreatedForEditorValidation(
            'activity',
            (int) $activity->id,
            (string) $activity->name,
            route('activities.edit', $activity)
        );

        return response()->json([
            'created' => true,
            'data' => $this->formatActivitySuggestionItem($activity),
        ], 201);
    }

    public function touristAttractionSuggestions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $destination = trim((string) ($validated['destination'] ?? ''));
        $region = trim((string) ($validated['region'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 12);

        return response()->json([
            'data' => $this->buildTouristAttractionSuggestionOptions($keyword, $destination, $region, $limit),
        ]);
    }

    public function storeTouristAttractionSuggestion(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ideal_visit_minutes' => ['nullable', 'integer', 'min:15', 'max:1440'],
        ]);

        ['name' => $attractionName, 'region' => $regionName, 'destination' => $destinationName] =
            $this->parseManualTouristAttractionInputFormat((string) ($validated['name'] ?? ''));

        $existing = TouristAttraction::query()
            ->with('destination:id,name,city,province')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($attractionName)])
            ->where('is_active', true)
            ->where(function ($query) use ($regionName) {
                $query->where('city', 'like', '%' . $regionName . '%')
                    ->orWhere('province', 'like', '%' . $regionName . '%')
                    ->orWhere('location', 'like', '%' . $regionName . '%');
            });
        if ($destinationName !== '') {
            $existing->where(function ($query) use ($destinationName) {
                $query->whereHas('destination', function ($destinationQuery) use ($destinationName) {
                    $destinationQuery->where('name', 'like', '%' . $destinationName . '%')
                        ->orWhere('city', 'like', '%' . $destinationName . '%')
                        ->orWhere('province', 'like', '%' . $destinationName . '%');
                })
                    ->orWhere('city', 'like', '%' . $destinationName . '%')
                    ->orWhere('province', 'like', '%' . $destinationName . '%')
                    ->orWhere('location', 'like', '%' . $destinationName . '%');
            });
        }
        $existing = $existing
            ->latest('id')
            ->first();
        if ($existing) {
            return response()->json([
                'created' => false,
                'data' => $this->formatTouristAttractionSuggestionItem($existing),
            ]);
        }

        $idealVisitMinutes = (int) ($validated['ideal_visit_minutes'] ?? 120);
        $idealVisitMinutes = max(15, min(1440, $idealVisitMinutes));
        $destinationId = $this->resolveDestinationId($destinationName);

        $attraction = TouristAttraction::query()->create([
            'name' => $attractionName,
            'ideal_visit_minutes' => $idealVisitMinutes,
            'contract_rate_per_pax' => 0,
            'markup_type' => 'fixed',
            'markup' => 0,
            'publish_rate_per_pax' => 0,
            'location' => $regionName,
            'city' => $regionName,
            'province' => $regionName,
            'destination_id' => $destinationId,
            'source' => 'Manual Input',
            'description' => 'Draft created from Itinerary Day Planner quick add. Format source: Attraction, Region, Destination. Please complete details and verify pricing.',
            'is_active' => true,
        ]);
        $attraction->load('destination:id,name,city,province');
        $this->logManualItemCreatedForEditorValidation(
            'attraction',
            (int) $attraction->id,
            (string) $attraction->name,
            route('tourist-attractions.edit', $attraction)
        );

        return response()->json([
            'created' => true,
            'data' => $this->formatTouristAttractionSuggestionItem($attraction),
        ], 201);
    }

    public function foodBeverageSuggestions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $destination = trim((string) ($validated['destination'] ?? ''));
        $region = trim((string) ($validated['region'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 12);

        return response()->json([
            'data' => $this->buildFoodBeverageSuggestionOptions($keyword, $destination, $region, $limit),
        ]);
    }

    public function storeFoodBeverageSuggestion(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:1440'],
        ]);

        ['name' => $fnbName, 'region' => $regionName, 'vendor' => $vendorName] =
            $this->parseManualFoodBeverageInputFormat((string) ($validated['name'] ?? ''));

        $existing = FoodBeverage::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude,destination_id')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($fnbName)])
            ->where('is_active', true)
            ->whereHas('vendor', function ($query) use ($vendorName) {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($vendorName)]);
            })
            ->latest('id')
            ->first();
        if ($existing) {
            return response()->json([
                'created' => false,
                'data' => $this->formatFoodBeverageSuggestionItem($existing),
            ]);
        }

        $vendor = $this->resolveOrCreateVendorForManualActivity($vendorName, $regionName);
        $durationMinutes = (int) ($validated['duration_minutes'] ?? 60);
        $durationMinutes = max(15, min(1440, $durationMinutes));

        $foodBeverage = FoodBeverage::query()->create([
            'vendor_id' => (int) $vendor->id,
            'name' => $fnbName,
            'service_type' => 'Restaurant',
            'duration_minutes' => $durationMinutes,
            'contract_rate' => 0,
            'markup_type' => 'fixed',
            'markup' => 0,
            'publish_rate' => 0,
            'meal_period' => '',
            'menu_highlights' => '',
            'notes' => 'Draft created from Itinerary Day Planner quick add. Format source: F&B Name, Region, Vendor. Please complete details and verify pricing.',
            'is_active' => true,
        ]);
        $foodBeverage->load([
            'vendor:id,name,city,province,location,latitude,longitude,destination_id',
            'vendor.destination:id,name,city,province',
        ]);
        $this->logManualItemCreatedForEditorValidation(
            'fnb',
            (int) $foodBeverage->id,
            (string) $foodBeverage->name,
            route('food-beverages.edit', $foodBeverage)
        );

        return response()->json([
            'created' => true,
            'data' => $this->formatFoodBeverageSuggestionItem($foodBeverage),
        ], 201);
    }

    public function show(Request $request, Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,city,province,destination_id,latitude,longitude,description,gallery_images',
            'touristAttractions.destination:id,name',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,adult_publish_rate,child_publish_rate,includes,excludes,benefits,notes,gallery_images',
            'itineraryActivities.activity.vendor:id,name,location,city,province,destination_id,latitude,longitude',
            'itineraryActivities.activity.vendor.destination:id,name',
            'itineraryIslandTransfers.islandTransfer:id,vendor_id,name,transfer_type,departure_point_name,departure_latitude,departure_longitude,arrival_point_name,arrival_latitude,arrival_longitude,route_geojson,duration_minutes,notes,gallery_images',
            'itineraryIslandTransfers.islandTransfer.vendor:id,name,location,city,province,destination_id,latitude,longitude',
            'itineraryIslandTransfers.islandTransfer.vendor.destination:id,name',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,publish_rate,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,destination_id,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage.vendor.destination:id,name',
            'itineraryTransportUnits.transportUnit:id,name,transport_type,brand_model,seat_capacity,luggage_capacity,air_conditioned,with_driver,images',
            'itineraryTransportUnits.transportUnit.transport:id,name,transport_type,images,brand_model,seat_capacity,luggage_capacity,air_conditioned,with_driver',
            'dayPoints',
            'dayPoints.startAirport:id,name,location,city,province,latitude,longitude,cover',
            'dayPoints.startHotel:id,name,address,city,province,latitude,longitude,cover',
            'dayPoints.startHotelRoom:id,hotels_id,rooms,view,cover',
            'dayPoints.endAirport:id,name,location,city,province,latitude,longitude,cover',
            'dayPoints.endHotel:id,name,address,city,province,latitude,longitude,cover',
            'dayPoints.endHotelRoom:id,hotels_id,rooms,view,cover',
            'inquiry:id,inquiry_number,customer_id',
            'inquiry.customer:id,name',
            'quotation:id,itinerary_id,status',
                        'arrivalTransport:id,name,transport_type',
            'departureTransport:id,name,transport_type',
        ]);
        $dayGroups = $itinerary->touristAttractions->groupBy(fn ($attraction) => (int) $attraction->pivot->day_number);
        $activityDayGroups = $itinerary->itineraryActivities->groupBy(fn ($item) => (int) $item->day_number);
        $islandTransferDayGroups = $itinerary->itineraryIslandTransfers->groupBy(fn ($item) => (int) $item->day_number);
        $foodBeverageDayGroups = $itinerary->itineraryFoodBeverages->groupBy(fn ($item) => (int) $item->day_number);
        $transportUnitsByDay = $itinerary->itineraryTransportUnits
            ->groupBy(fn ($item) => (int) $item->day_number);

        $activities = $itinerary->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.itineraries.show', compact('itinerary', 'dayGroups', 'activityDayGroups', 'islandTransferDayGroups', 'foodBeverageDayGroups', 'transportUnitsByDay', 'activities'));
    }

    public function generatePdf(Request $request, Itinerary $itinerary)
    {
        $pdfLocale = $this->normalizePdfLocale((string) $request->query('locale', app()->getLocale()));
        $previousLocale = app()->getLocale();
        app()->setLocale($pdfLocale);
        $pdfFontConfig = $this->resolvePdfFontConfig($pdfLocale);

        try {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude,description,gallery_images',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,adult_publish_rate,child_publish_rate,notes,includes,excludes,gallery_images',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryIslandTransfers.islandTransfer:id,vendor_id,name,transfer_type,departure_point_name,departure_latitude,departure_longitude,arrival_point_name,arrival_latitude,arrival_longitude,route_geojson,duration_minutes,notes,gallery_images',
            'itineraryIslandTransfers.islandTransfer.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,publish_rate,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,name,brand_model,seat_capacity,luggage_capacity,air_conditioned,with_driver,images',
            'itineraryTransportUnits.transportUnit.transport:id,name,transport_type',
            'dayPoints',
            'dayPoints.startAirport:id,name,location,city,province,cover',
            'dayPoints.startHotel:id,name,address,city,province',
            'dayPoints.startHotelRoom:id,hotels_id,rooms,view,cover',
            'dayPoints.endAirport:id,name,location,city,province,cover',
            'dayPoints.endHotel:id,name,address,city,province',
            'dayPoints.endHotelRoom:id,hotels_id,rooms,view,cover',
            'inquiry:id,inquiry_number,customer_id,status,priority,source,deadline,notes',
            'inquiry.customer:id,name,code',
        ]);

        $scheduleByDay = [];
        $dayPointByDay = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
        $transportUnitsByDay = $itinerary->itineraryTransportUnits->groupBy(fn ($item) => (int) $item->day_number);
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
        $resolvePoint = function ($dayPoint, string $scope, array $previousEnd = ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'thumbnail_data_uri' => null]): array {
            if (!$dayPoint) {
                return $scope === 'start'
                    ? array_merge($previousEnd, ['label' => $previousEnd['label'] ?? ($previousEnd['name'] ?? 'Not set')])
                    : ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'label' => 'Not set', 'thumbnail_data_uri' => null];
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
                        'label' => (string) ($dayPoint->startAirport?->name ?? 'Not set'),
                        'thumbnail_data_uri' => $this->resolveAirportCoverDataUri($dayPoint->startAirport?->cover),
                    ];
                }
                if ($type === 'hotel') {
                    $hotelName = (string) ($dayPoint->startHotel?->name ?? 'Not set');
                    $roomName = (string) ($dayPoint->startHotelRoom?->rooms ?? '');
                    $label = $roomName !== ''
                        ? ($hotelName . ' - ' . $roomName)
                        : $hotelName;
                    return [
                        'name' => $label,
                        'location' => (string) ($dayPoint->startHotel?->address ?? '-'),
                        'type' => 'Hotel',
                        'label' => $label,
                        'thumbnail_data_uri' => $this->resolveHotelRoomCoverDataUri($dayPoint->startHotelRoom?->cover),
                    ];
                }
                return ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'label' => 'Not set', 'thumbnail_data_uri' => null];
            }

            $type = (string) ($dayPoint->end_point_type ?? '');
            if ($type === 'airport') {
                return [
                    'name' => (string) ($dayPoint->endAirport?->name ?? 'Not set'),
                    'location' => (string) ($dayPoint->endAirport?->location ?? '-'),
                    'type' => 'Airport',
                    'label' => (string) ($dayPoint->endAirport?->name ?? 'Not set'),
                    'thumbnail_data_uri' => $this->resolveAirportCoverDataUri($dayPoint->endAirport?->cover),
                ];
            }
            if ($type === 'hotel') {
                $hotelName = (string) ($dayPoint->endHotel?->name ?? 'Not set');
                $roomName = (string) ($dayPoint->endHotelRoom?->rooms ?? '');
                $label = $roomName !== ''
                    ? ($hotelName . ' - ' . $roomName)
                    : $hotelName;
                return [
                    'name' => $label,
                    'location' => (string) ($dayPoint->endHotel?->address ?? '-'),
                    'type' => 'Hotel',
                    'label' => $label,
                    'thumbnail_data_uri' => $this->resolveHotelRoomCoverDataUri($dayPoint->endHotelRoom?->cover),
                ];
            }
            return ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'label' => 'Not set', 'thumbnail_data_uri' => null];
        };
        $previousEndPoint = ['name' => 'Not set', 'location' => '-', 'type' => 'Unknown', 'label' => 'Not set', 'thumbnail_data_uri' => null];
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
                        'includes' => (string) ($item->activity->includes ?? ''),
                        'excludes' => (string) ($item->activity->excludes ?? ''),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($item->activity->gallery_images ?? []),
                        'pax' => (int) ($item->pax ?? 0),
                        'pax_adult' => (int) ($item->pax_adult ?? $item->pax ?? 0),
                        'pax_child' => (int) ($item->pax_child ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                });

            $islandTransfers = $itinerary->itineraryIslandTransfers
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(function ($item) {
                    $transfer = $item->islandTransfer;
                    return [
                        'type' => 'Island Transfer',
                        'source_type' => 'transfer',
                        'source_id' => (int) ($item->island_transfer_id ?? 0),
                        'name' => (string) ($transfer->name ?? '-'),
                        'location' => trim((string) (($transfer->departure_point_name ?? '-') . ' -> ' . ($transfer->arrival_point_name ?? '-'))),
                        'description' => (string) ($transfer->notes ?? '-'),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($transfer->gallery_images ?? []),
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
                        'vendor_name' => (string) ($item->foodBeverage->vendor->name ?? '-'),
                        'menu_highlights' => (string) ($item->foodBeverage->menu_highlights ?? ''),
                        'location' => (string) ($item->foodBeverage->vendor->location ?? '-'),
                        'description' => (string) ($item->foodBeverage->notes ?? $item->foodBeverage->menu_highlights ?? '-'),
                        'thumbnail_data_uri' => $this->resolveGalleryImageDataUri($item->foodBeverage->gallery_images ?? []),
                        'publish_rate' => (float) ($item->foodBeverage->publish_rate ?? 0),
                        'currency' => 'IDR',
                        'pax' => (int) ($item->pax ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                });

            $items = $attractions->merge($activities)->merge($islandTransfers)->merge($foodBeverages)
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
            $startBaseMinutes = $toMinutes($startTime !== '--:--' ? $startTime : null);
            $endTime = $lastEndBaseMinutes !== null
                ? ($fromMinutes($lastEndBaseMinutes + $lastTravelToEnd) ?? '--:--')
                : ($startBaseMinutes !== null
                    ? ($fromMinutes($startBaseMinutes + max(0, (int) ($startTravelMinutes ?? 0))) ?? '--:--')
                    : '--:--');
            $dayTransportItems = $transportUnitsByDay->get($day, collect());
            $dayTransports = $dayTransportItems
                ->map(function ($transportItem) {
                    $dayTransportUnit = $transportItem?->transportUnit;
                    $transportMaster = $dayTransportUnit?->transport;

                    return [
                        'assigned' => (bool) $dayTransportUnit,
                        'unit_name' => (string) ($dayTransportUnit?->name ?? '-'),
                        'brand_model' => (string) ($dayTransportUnit?->brand_model ?? '-'),
                        'seat_capacity' => $dayTransportUnit?->seat_capacity !== null ? (int) $dayTransportUnit->seat_capacity : null,
                        'luggage_capacity' => $dayTransportUnit?->luggage_capacity !== null ? (int) $dayTransportUnit->luggage_capacity : null,
                        'currency' => 'IDR',
                        'with_driver' => (bool) ($dayTransportUnit?->with_driver ?? false),
                        'air_conditioned' => (bool) ($dayTransportUnit?->air_conditioned ?? false),
                        'transport_name' => (string) ($transportMaster?->name ?? '-'),
                        'transport_type' => (string) ($transportMaster?->transport_type ?? '-'),
                        'provider_name' => '-',
                        'location' => '-',
                        'city' => '-',
                        'province' => '-',
                        'thumbnail_data_uri' => $dayTransportUnit
                            ? $this->resolveGalleryImageDataUri($dayTransportUnit->images ?? [])
                            : null,
                    ];
                })
                ->values()
                ->all();
            $timelineItems = collect([
                [
                    'type' => 'Start Point',
                    'name' => $startPoint['name'],
                    'location' => $startPoint['location'],
                    'description' => '-',
                    'thumbnail_data_uri' => $startPoint['thumbnail_data_uri'] ?? null,
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
                'thumbnail_data_uri' => $endPoint['thumbnail_data_uri'] ?? null,
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
                'start_point_type_label' => $startPoint['label'] ?? ($startPoint['type'] ?? 'Unknown'),
                'end_point_type_label' => $endPoint['label'] ?? ($endPoint['type'] ?? 'Unknown'),
                'transport_units' => $dayTransports,
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
            'pdfFontFamilyCss' => $pdfFontConfig['family_css'],
            'pdfFontFaceCss' => $pdfFontConfig['font_face_css'],
            'pdfLocale' => $pdfLocale,
        ])->setPaper('a4', 'portrait');

        $filename = 'itinerary-' . Str::slug($itinerary->title ?: 'untitled') . '.pdf';
        $mode = strtolower((string) $request->query('mode', 'download'));
        if ($mode === 'stream') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    private function resolveGalleryImageDataUri($galleryImages): ?string
    {
        $images = is_array($galleryImages) ? $galleryImages : [];
        foreach ($images as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }
            $normalizedPath = trim(str_replace('\\', '/', $path), '/');
            $thumbnailPath = ImageThumbnailGenerator::thumbnailPathFor($normalizedPath);
            $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
            if ($thumbnailDataUri) {
                return $thumbnailDataUri;
            }

            if (Storage::disk('public')->exists($normalizedPath)) {
                ImageThumbnailGenerator::generate('public', $normalizedPath, 360, 240);
                $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
                if ($thumbnailDataUri) {
                    return $thumbnailDataUri;
                }
            }

            $originalDataUri = $this->resolveStorageImageDataUri($normalizedPath);
            if ($originalDataUri) {
                return $originalDataUri;
            }
        }

        return null;
    }

    private function resolveAirportCoverDataUri(?string $coverPath): ?string
    {
        $rawPath = trim(str_replace('\\', '/', (string) $coverPath), '/');
        if ($rawPath === '') {
            return null;
        }

        if (Str::startsWith($rawPath, ['http://', 'https://'])) {
            return null;
        }

        if (Str::startsWith($rawPath, 'storage/')) {
            $rawPath = Str::after($rawPath, 'storage/');
        }

        $candidates = [$rawPath];
        if (! Str::contains($rawPath, '/')) {
            $candidates[] = 'airports/covers/' . $rawPath;
            $candidates[] = 'airports/cover/' . $rawPath;
        }

        foreach (array_values(array_unique($candidates)) as $candidate) {
            $thumbnailPath = ImageThumbnailGenerator::thumbnailPathFor($candidate);
            $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
            if ($thumbnailDataUri) {
                return $thumbnailDataUri;
            }

            if (Storage::disk('public')->exists($candidate)) {
                ImageThumbnailGenerator::generate('public', $candidate, 360, 240);
                $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
                if ($thumbnailDataUri) {
                    return $thumbnailDataUri;
                }
            }

            $originalDataUri = $this->resolveStorageImageDataUri($candidate);
            if ($originalDataUri) {
                return $originalDataUri;
            }
        }

        return null;
    }

    private function resolveHotelRoomCoverDataUri(?string $coverPath): ?string
    {
        $rawPath = trim(str_replace('\\', '/', (string) $coverPath), '/');
        if ($rawPath === '') {
            return null;
        }

        if (Str::startsWith($rawPath, ['http://', 'https://'])) {
            return null;
        }

        if (Str::startsWith($rawPath, 'storage/')) {
            $rawPath = Str::after($rawPath, 'storage/');
        }

        $candidates = [$rawPath];
        if (! Str::contains($rawPath, '/')) {
            $candidates[] = 'hotels/rooms/' . $rawPath;
        }

        foreach (array_values(array_unique($candidates)) as $candidate) {
            $thumbnailPath = ImageThumbnailGenerator::thumbnailPathFor($candidate);
            $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
            if ($thumbnailDataUri) {
                return $thumbnailDataUri;
            }

            if (Storage::disk('public')->exists($candidate)) {
                ImageThumbnailGenerator::generate('public', $candidate, 360, 240);
                $thumbnailDataUri = $this->resolveStorageImageDataUri($thumbnailPath);
                if ($thumbnailDataUri) {
                    return $thumbnailDataUri;
                }
            }

            $originalDataUri = $this->resolveStorageImageDataUri($candidate);
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

    private function normalizePdfLocale(string $locale): string
    {
        $normalized = str_replace('-', '_', trim($locale));
        $supported = array_keys((array) config('app.supported_locales', []));
        if (in_array($normalized, $supported, true)) {
            return $normalized;
        }

        return (string) config('app.locale', 'en');
    }

    /**
     * @return array{family_css:string,font_face_css:string}
     */
    private function resolvePdfFontConfig(string $locale): array
    {
        $fallback = [
            'family_css' => "'DejaVu Sans', Arial, sans-serif",
            'font_face_css' => '',
        ];

        if (! in_array($locale, ['zh_Hant', 'zh_Hans'], true)) {
            return $fallback;
        }

        $fontPath = $this->resolvePdfCjkFontPath($locale);
        if (! $fontPath) {
            return $fallback;
        }

        $extension = strtolower((string) pathinfo($fontPath, PATHINFO_EXTENSION));
        $format = $extension === 'otf' ? 'opentype' : 'truetype';
        $fontUrl = $this->toFileUrl($fontPath);

        return [
            'family_css' => "'VoyexPdfCjk', 'DejaVu Sans', Arial, sans-serif",
            'font_face_css' => "@font-face { font-family: 'VoyexPdfCjk'; font-style: normal; font-weight: 400; src: url('{$fontUrl}') format('{$format}'); }",
        ];
    }

    private function resolvePdfCjkFontPath(string $locale): ?string
    {
        $byLocale = [
            'zh_Hant' => [
                'NotoSansTC-Regular.ttf',
                'NotoSerifTC-Regular.ttf',
                'SourceHanSansTC-Regular.otf',
                'SourceHanSerifTC-Regular.otf',
            ],
            'zh_Hans' => [
                'NotoSansSC-Regular.ttf',
                'NotoSerifSC-Regular.ttf',
                'SourceHanSansSC-Regular.otf',
                'SourceHanSerifSC-Regular.otf',
            ],
        ];
        $fileNames = $byLocale[$locale] ?? [];
        if ($fileNames === []) {
            return null;
        }

        $basePaths = [
            resource_path('fonts/cjk'),
            storage_path('fonts/cjk'),
            storage_path('fonts'),
            public_path('fonts/cjk'),
            public_path('fonts'),
        ];

        foreach ($fileNames as $fileName) {
            foreach ($basePaths as $basePath) {
                $fullPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
                if (File::exists($fullPath)) {
                    return $fullPath;
                }
            }
        }

        return null;
    }

    private function toFileUrl(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            return 'file:///' . $normalized;
        }

        return 'file://' . $normalized;
    }

    public function update(Request $request, Itinerary $itinerary)
    {
        $itinerary->loadMissing(['quotation:id,itinerary_id,status']);
        if (! $this->canManageItinerary($itinerary, 'update')) {
            return $this->denyItineraryMutation($itinerary);
        }
        if ($itinerary->isFinal()) {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'This itinerary is final and cannot be edited.');
        }
        if ($this->isItineraryLockedByQuotation($itinerary)) {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'Itinerary cannot be updated because the related quotation is approved/final.');
        }
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:255'],
            'arrival_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'departure_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'inquiry_id' => ['nullable', 'integer', 'exists:inquiries,id'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:7'],
            'duration_nights' => ['required', 'integer', 'min:0', 'max:6', 'lte:duration_days'],
            'description' => ['nullable', 'string'],
            'itinerary_include' => ['nullable', 'string'],
            'itinerary_exclude' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'daily_start_point_types' => ['nullable', 'array'],
            'daily_start_point_types.*' => ['nullable', 'string', Rule::in(['', 'previous_day_end', 'hotel', 'airport'])],
            'daily_start_point_items' => ['nullable', 'array'],
            'daily_start_point_room_ids' => ['nullable', 'array'],
            'daily_start_point_room_ids.*' => ['nullable', 'integer'],
            'daily_start_point_room_counts' => ['nullable', 'array'],
            'daily_start_point_room_counts.*' => ['nullable', 'integer', 'min:1'],
            'daily_start_hotel_booking_modes' => ['nullable', 'array'],
            'daily_start_hotel_booking_modes.*' => ['nullable', 'string', 'in:arranged,self'],
            'day_start_times' => ['nullable', 'array'],
            'day_start_times.*' => ['nullable', 'date_format:H:i'],
            'day_start_travel_minutes' => ['nullable', 'array'],
            'day_start_travel_minutes.*' => ['nullable', 'integer', 'min:0'],
            'daily_end_point_types' => ['nullable', 'array'],
            'daily_end_point_types.*' => ['nullable', 'string', Rule::in(['', 'hotel', 'airport'])],
            'daily_end_point_items' => ['nullable', 'array'],
            'daily_end_point_room_ids' => ['nullable', 'array'],
            'daily_end_point_room_ids.*' => ['nullable', 'integer'],
            'daily_end_point_room_counts' => ['nullable', 'array'],
            'daily_end_point_room_counts.*' => ['nullable', 'integer', 'min:1'],
            'daily_end_hotel_booking_modes' => ['nullable', 'array'],
            'daily_end_hotel_booking_modes.*' => ['nullable', 'string', 'in:arranged,self'],
            'daily_main_experience_types' => ['nullable', 'array'],
            'daily_main_experience_types.*' => ['nullable', 'string', 'in:attraction,activity,fnb'],
            'daily_main_experience_items' => ['nullable', 'array'],
            'daily_main_experience_items.*' => ['nullable', 'integer', 'min:1'],
            'hotel_stays' => ['nullable', 'array'],
            'hotel_stays.*.hotel_id' => ['required', 'integer', 'exists:hotels,id'],
            'hotel_stays.*.day_number' => ['required', 'integer', 'min:1'],
            'hotel_stays.*.night_count' => ['required', 'integer', 'min:1'],
            'hotel_stays.*.room_count' => ['required', 'integer', 'min:1'],
            'itinerary_items' => ['nullable', 'array'],
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
            'itinerary_activity_items.*.pax_adult' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.pax_child' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'itinerary_island_transfer_items' => ['nullable', 'array'],
            'itinerary_island_transfer_items.*.island_transfer_id' => ['required', 'integer', 'exists:island_transfers,id'],
            'itinerary_island_transfer_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_island_transfer_items.*.pax' => ['nullable', 'integer', 'min:1'],
            'itinerary_island_transfer_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_island_transfer_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_island_transfer_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_island_transfer_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
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
            'daily_transport_units.*.transport_unit_id' => ['nullable', 'integer', 'exists:transports,id'],
        ]);

        // Business rule: itinerary must remain active on create/update.
        $validated['is_active'] = true;
        $validated['destination_id'] = $this->resolveDestinationId($validated['destination'] ?? null);
        $items = $validated['itinerary_items'] ?? [];
        $activityItems = $validated['itinerary_activity_items'] ?? [];
        $islandTransferItems = $validated['itinerary_island_transfer_items'] ?? [];
        $foodBeverageItems = $validated['itinerary_food_beverage_items'] ?? [];
        $hotelStays = $this->normalizeHotelStays(
            $validated['hotel_stays'] ?? [],
            (int) ($validated['duration_days'] ?? 1)
        );
        $dayPoints = $this->normalizeDayPoints(
            (int) ($validated['duration_days'] ?? 1),
            $validated['daily_start_point_types'] ?? [],
            $validated['daily_start_point_items'] ?? [],
            $validated['daily_start_point_room_ids'] ?? [],
            $validated['daily_start_point_room_counts'] ?? [],
            $validated['daily_start_hotel_booking_modes'] ?? [],
            $validated['day_start_times'] ?? [],
            $validated['day_start_travel_minutes'] ?? [],
            $validated['daily_end_point_types'] ?? [],
            $validated['daily_end_point_items'] ?? [],
            $validated['daily_end_point_room_ids'] ?? [],
            $validated['daily_end_point_room_counts'] ?? [],
            $validated['daily_end_hotel_booking_modes'] ?? [],
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
        unset($validated['daily_start_point_types']);
        unset($validated['daily_start_point_items']);
        unset($validated['daily_start_point_room_ids']);
        unset($validated['daily_start_point_room_counts']);
        unset($validated['daily_start_hotel_booking_modes']);
        unset($validated['day_start_times']);
        unset($validated['day_start_travel_minutes']);
        unset($validated['daily_end_point_types']);
        unset($validated['daily_end_point_items']);
        unset($validated['daily_end_point_room_ids']);
        unset($validated['daily_end_point_room_counts']);
        unset($validated['daily_end_hotel_booking_modes']);
        unset($validated['daily_main_experience_types']);
        unset($validated['daily_main_experience_items']);
        unset($validated['hotel_stays']);
        unset($validated['itinerary_items']);
        unset($validated['itinerary_activity_items']);
        unset($validated['itinerary_island_transfer_items']);
        unset($validated['itinerary_food_beverage_items']);
        unset($validated['daily_transport_units']);
        $this->validateScheduleItems($items, (int) $validated['duration_days']);
        $this->validateActivityItems($activityItems, (int) $validated['duration_days']);
        $this->validateIslandTransferItems($islandTransferItems, (int) $validated['duration_days']);
        $this->validateFoodBeverageItems($foodBeverageItems, (int) $validated['duration_days']);

        $beforeAudit = $this->buildItineraryAuditSnapshot($itinerary);

        Itinerary::withoutActivityLogging(function () use ($itinerary, $validated): void {
            $itinerary->update($validated);
        });
        $itinerary->touristAttractions()->sync($this->buildSyncPayload($items));
        $this->syncItineraryActivities($itinerary, $activityItems);
        $this->syncItineraryIslandTransfers($itinerary, $islandTransferItems);
        $this->syncItineraryFoodBeverages($itinerary, $foodBeverageItems);
        $this->syncDayPoints($itinerary, $dayPoints);
        $this->syncHotelStays($itinerary, $hotelStays);
        $this->syncDailyTransportUnits($itinerary, $transportUnitsByDay);
        $itinerary->refresh();
        $this->activityAuditLogger->logUpdated($itinerary, $beforeAudit, $this->buildItineraryAuditSnapshot($itinerary), 'Itinerary');

        $this->syncInquiryProcessedStatus($itinerary->inquiry_id);
        $quotationSynced = $this->quotationItinerarySyncService->syncLinkedQuotationFromItinerary($itinerary);

        $successMessage = $quotationSynced
            ? 'Itinerary updated successfully. Linked quotation has been synced and reset to pending for re-validation/approval.'
            : 'Itinerary updated successfully.';

        return redirect()->route('itineraries.show', $itinerary)->with('success', $successMessage);
    }

    public function destroy(Itinerary $itinerary)
    {
        $itinerary->loadMissing([
            'quotation:id,itinerary_id,status',
            'quotation.booking:id,quotation_id',
            'quotation.booking.invoice:id,booking_id',
        ]);
        if (! $this->canManageItinerary($itinerary, 'delete')) {
            return $this->denyItineraryMutation($itinerary);
        }

        if ($itinerary->isFinal()) {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'This itinerary is final and cannot be deleted.');
        }

        if ($itinerary->quotation) {
            $reasons = ['quotation'];
            if ($this->isItineraryLockedByQuotation($itinerary)) {
                $reasons[0] = 'quotation (approved/final)';
            }
            if ($itinerary->quotation->booking) {
                $reasons[] = 'booking';
            }
            if ($itinerary->quotation->booking?->invoice) {
                $reasons[] = 'invoice';
            }

            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'This itinerary cannot be deleted because it is linked to ' . implode(', ', $reasons) . '. Please remove related data first.');
        }

        $itinerary->update(['is_active' => false]);
        $itinerary->delete();

        return redirect()->route('itineraries.index')->with('success', 'Itinerary deactivated successfully.');
    }

    public function toggleStatus($itinerary)
    {
        $itinerary = Itinerary::withTrashed()->findOrFail($itinerary);
        if (! $this->canManageItinerary($itinerary, 'delete')) {
            return $this->denyItineraryMutation($itinerary);
        }

        if ($itinerary->isFinal()) {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'This itinerary is final and its status cannot be changed.');
        }

        if ($itinerary->trashed()) {
            $itinerary->restore();
            $itinerary->update(['is_active' => true]);

            return redirect()
                ->route('itineraries.index')
                ->with('success', 'Itinerary activated successfully.');
        }

        $itinerary->loadMissing([
            'quotation:id,itinerary_id,status',
            'quotation.booking:id,quotation_id',
            'quotation.booking.invoice:id,booking_id',
        ]);
        if ($itinerary->quotation) {
            $reasons = ['quotation'];
            if ($this->isItineraryLockedByQuotation($itinerary)) {
                $reasons[0] = 'quotation (approved/final)';
            }
            if ($itinerary->quotation->booking) {
                $reasons[] = 'booking';
            }
            if ($itinerary->quotation->booking?->invoice) {
                $reasons[] = 'invoice';
            }

            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'This itinerary cannot be deactivated because it is linked to ' . implode(', ', $reasons) . '. Please remove related data first.');
        }

        $itinerary->update(['is_active' => false]);
        $itinerary->delete();

        return redirect()
            ->route('itineraries.index')
            ->with('success', 'Itinerary deactivated successfully.');
    }

    private function canManageItinerary(Itinerary $itinerary, string $ability = 'update'): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (! in_array($ability, ['update', 'delete'], true)) {
            $ability = 'update';
        }

        return $user->can($ability, $itinerary);
    }

    private function denyItineraryMutation(Itinerary $itinerary)
    {
        return redirect()
            ->route('itineraries.show', $itinerary)
            ->with('error', 'You do not have permission to modify this itinerary.');
    }

    private function isItineraryLockedByQuotation(Itinerary $itinerary): bool
    {
        $status = (string) ($itinerary->quotation->status ?? '');
        return in_array($status, ['approved', Quotation::FINAL_STATUS], true);
    }

    private function syncInquiryProcessedStatus(?int $inquiryId): void
    {
        if (! $inquiryId) {
            return;
        }
        $inquiry = Inquiry::query()->find($inquiryId);
        if (! $inquiry || $inquiry->isFinal()) {
            return;
        }
        if (($inquiry->status ?? '') === 'draft') {
            $inquiry->update(['status' => 'processed']);
        }
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

            $adultQty = max(0, (int) ($item['pax_adult'] ?? 0));
            $childQty = max(0, (int) ($item['pax_child'] ?? 0));
            $totalPax = $adultQty + $childQty;
            if ($totalPax <= 0) {
                $totalPax = max(1, (int) ($item['pax'] ?? 1));
                $adultQty = $totalPax;
                $childQty = 0;
            }

            $payload[] = [
                'itinerary_id' => $itinerary->id,
                'activity_id' => $activityId,
                'day_number' => $dayNumber,
                'pax' => $totalPax,
                'pax_adult' => $adultQty,
                'pax_child' => $childQty,
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

    private function validateIslandTransferItems(array $items, int $durationDays): void
    {
        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;
            $day = (int) ($item['day_number'] ?? 0);
            if ($day > $durationDays) {
                throw ValidationException::withMessages([
                    "itinerary_island_transfer_items.{$index}.day_number" => "Island transfer day on row {$rowNumber} cannot exceed itinerary duration.",
                ]);
            }
        }
    }

    private function syncItineraryIslandTransfers(Itinerary $itinerary, array $items): void
    {
        $itinerary->itineraryIslandTransfers()->delete();
        if ($items === []) {
            return;
        }

        $transferDurations = IslandTransfer::query()
            ->whereIn('id', array_column($items, 'island_transfer_id'))
            ->pluck('duration_minutes', 'id');

        $payload = [];
        $fallbackVisitOrderByDay = [];
        foreach (array_values($items) as $item) {
            $dayNumber = (int) $item['day_number'];
            $fallbackVisitOrderByDay[$dayNumber] = ($fallbackVisitOrderByDay[$dayNumber] ?? 0) + 1;
            $islandTransferId = (int) $item['island_transfer_id'];
            $startTime = $item['start_time'] ?: null;
            $durationMinutes = (int) ($transferDurations[$islandTransferId] ?? 60);
            $manualEndTime = $item['end_time'] ?: null;
            $travelMinutes = isset($item['travel_minutes_to_next']) && $item['travel_minutes_to_next'] !== ''
                ? max(0, (int) $item['travel_minutes_to_next'])
                : null;
            $visitOrder = isset($item['visit_order']) && $item['visit_order'] !== ''
                ? max(1, (int) $item['visit_order'])
                : $fallbackVisitOrderByDay[$dayNumber];

            $payload[] = [
                'itinerary_id' => $itinerary->id,
                'island_transfer_id' => $islandTransferId,
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

        $itinerary->itineraryIslandTransfers()->insert($payload);
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
                'meal_type' => $this->resolveMealTypeFromStartTime($startTime),
                'travel_minutes_to_next' => $travelMinutes,
                'visit_order' => $visitOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $itinerary->itineraryFoodBeverages()->insert($payload);
    }

    private function normalizeDayPoints(
        int $durationDays,
        array $startTypes,
        array $startItems,
        array $startRoomIds,
        array $startRoomCounts,
        array $startHotelBookingModes,
        array $dayStartTimes,
        array $dayStartTravelMinutes,
        array $endTypes,
        array $endItems,
        array $endRoomIds,
        array $endRoomCounts,
        array $endHotelBookingModes,
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
        $roomHotelMap = HotelRoom::query()
            ->pluck('hotels_id', 'id')
            ->all();

        for ($day = 1; $day <= $durationDays; $day++) {
            $startType = trim((string) ($startTypes[$day] ?? ''));
            if (! in_array($startType, ['', 'previous_day_end', 'hotel', 'airport'], true)) {
                $startType = '';
            }

            $endType = trim((string) ($endTypes[$day] ?? ''));
            if (! in_array($endType, ['', 'hotel', 'airport'], true)) {
                $endType = '';
            }

            $startItemId = (int) ($startItems[$day] ?? 0);
            $endItemId = (int) ($endItems[$day] ?? 0);
            $startRoomId = (int) ($startRoomIds[$day] ?? 0);
            $endRoomId = (int) ($endRoomIds[$day] ?? 0);
            $startHotelBookingMode = (string) ($startHotelBookingModes[$day] ?? 'arranged');
            if (! in_array($startHotelBookingMode, ['arranged', 'self'], true)) {
                $startHotelBookingMode = 'arranged';
            }
            $endHotelBookingMode = (string) ($endHotelBookingModes[$day] ?? 'arranged');
            if (! in_array($endHotelBookingMode, ['arranged', 'self'], true)) {
                $endHotelBookingMode = 'arranged';
            }
            $startRoomQty = max(1, (int) ($startRoomCounts[$day] ?? 1));
            $endRoomQty = max(1, (int) ($endRoomCounts[$day] ?? 1));
            $dayStartTimeRaw = trim((string) ($dayStartTimes[$day] ?? ''));
            $dayStartTime = $dayStartTimeRaw !== '' ? $dayStartTimeRaw : null;
            $dayStartTravel = isset($dayStartTravelMinutes[$day]) && $dayStartTravelMinutes[$day] !== ''
                ? max(0, (int) $dayStartTravelMinutes[$day])
                : 0;
            $startHotelIsSelfBooked = $startType === 'hotel' && $startHotelBookingMode === 'self';
            $endHotelIsSelfBooked = $endType === 'hotel' && $endHotelBookingMode === 'self';
            $mainExperienceType = (string) ($mainExperienceTypes[$day] ?? '');
            if (! in_array($mainExperienceType, ['attraction', 'activity', 'fnb'], true)) {
                $mainExperienceType = '';
            }
            $mainExperienceItemId = (int) ($mainExperienceItems[$day] ?? 0);

            if (
                ($startType === 'airport' || ($startType === 'hotel' && ! $startHotelIsSelfBooked))
                && $startItemId <= 0
            ) {
                throw ValidationException::withMessages([
                    "daily_start_point_items.{$day}" => "Start point item on day {$day} is required.",
                ]);
            }
            if ($startType === 'hotel') {
                if (! $startHotelIsSelfBooked) {
                    if ($startRoomId <= 0) {
                        throw ValidationException::withMessages([
                            "daily_start_point_room_ids.{$day}" => "Start room on day {$day} is required when start point is hotel.",
                        ]);
                    }
                    $roomHotelId = (int) ($roomHotelMap[$startRoomId] ?? 0);
                    if ($roomHotelId !== $startItemId) {
                        throw ValidationException::withMessages([
                            "daily_start_point_room_ids.{$day}" => "Selected start room on day {$day} does not belong to selected hotel.",
                        ]);
                    }
                } else {
                    if ($startRoomId > 0 && $startItemId <= 0) {
                        throw ValidationException::withMessages([
                            "daily_start_point_items.{$day}" => "Please select start hotel on day {$day} when start room is selected.",
                        ]);
                    }
                    if ($startRoomId > 0 && $startItemId > 0) {
                        $roomHotelId = (int) ($roomHotelMap[$startRoomId] ?? 0);
                        if ($roomHotelId !== $startItemId) {
                            throw ValidationException::withMessages([
                                "daily_start_point_room_ids.{$day}" => "Selected start room on day {$day} does not belong to selected hotel.",
                            ]);
                        }
                    }
                }
            } else {
                $startHotelBookingMode = null;
            }
            if (
                ($endType === 'airport' || ($endType === 'hotel' && ! $endHotelIsSelfBooked))
                && $endItemId <= 0
            ) {
                throw ValidationException::withMessages([
                    "daily_end_point_items.{$day}" => "End point item on day {$day} is required.",
                ]);
            }
            if ($endType === 'hotel') {
                if (! $endHotelIsSelfBooked) {
                    if ($endRoomId <= 0) {
                        throw ValidationException::withMessages([
                            "daily_end_point_room_ids.{$day}" => "End room on day {$day} is required when end point is hotel.",
                        ]);
                    }
                    $roomHotelId = (int) ($roomHotelMap[$endRoomId] ?? 0);
                    if ($roomHotelId !== $endItemId) {
                        throw ValidationException::withMessages([
                            "daily_end_point_room_ids.{$day}" => "Selected end room on day {$day} does not belong to selected hotel.",
                        ]);
                    }
                } else {
                    if ($endRoomId > 0 && $endItemId <= 0) {
                        throw ValidationException::withMessages([
                            "daily_end_point_items.{$day}" => "Please select end hotel on day {$day} when end room is selected.",
                        ]);
                    }
                    if ($endRoomId > 0 && $endItemId > 0) {
                        $roomHotelId = (int) ($roomHotelMap[$endRoomId] ?? 0);
                        if ($roomHotelId !== $endItemId) {
                            throw ValidationException::withMessages([
                                "daily_end_point_room_ids.{$day}" => "Selected end room on day {$day} does not belong to selected hotel.",
                            ]);
                        }
                    }
                }
            } else {
                $endHotelBookingMode = null;
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

            $resolvedStartAirportId = null;
            $resolvedStartHotelId = null;
            $resolvedStartHotelRoomId = null;
            $resolvedStartHotelBookingMode = null;

            if ($startType === 'airport') {
                $resolvedStartAirportId = $startItemId > 0 ? $startItemId : null;
            } elseif ($startType === 'hotel') {
                $resolvedStartHotelId = $startItemId > 0 ? $startItemId : null;
                $resolvedStartHotelRoomId = $startRoomId > 0 ? $startRoomId : null;
                $resolvedStartHotelBookingMode = $startHotelBookingMode;
            } elseif ($startType === 'previous_day_end') {
                $previousDayRow = $rows[$day - 2] ?? null;
                if (is_array($previousDayRow)) {
                    $previousEndType = (string) ($previousDayRow['end_point_type'] ?? '');
                    if ($previousEndType === 'airport') {
                        $resolvedStartAirportId = (int) ($previousDayRow['end_airport_id'] ?? 0) ?: null;
                    } elseif ($previousEndType === 'hotel') {
                        $resolvedStartHotelId = (int) ($previousDayRow['end_hotel_id'] ?? 0) ?: null;
                        $resolvedStartHotelRoomId = (int) ($previousDayRow['end_hotel_room_id'] ?? 0) ?: null;
                        $resolvedStartHotelBookingMode = (string) ($previousDayRow['end_hotel_booking_mode'] ?? 'arranged');
                        if (! in_array($resolvedStartHotelBookingMode, ['arranged', 'self'], true)) {
                            $resolvedStartHotelBookingMode = 'arranged';
                        }
                    }
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
                'start_point_type' => $startType !== '' ? $startType : null,
                'start_airport_id' => $resolvedStartAirportId,
                'start_hotel_id' => $resolvedStartHotelId,
                'start_hotel_room_id' => $resolvedStartHotelRoomId,
                'start_hotel_booking_mode' => $resolvedStartHotelBookingMode,
                'end_point_type' => $endType !== '' ? $endType : null,
                'end_airport_id' => $endType === 'airport' ? $endItemId : null,
                'end_hotel_id' => $endType === 'hotel' && $endItemId > 0 ? $endItemId : null,
                'end_hotel_room_id' => $endType === 'hotel' && $endRoomId > 0 ? $endRoomId : null,
                'end_hotel_booking_mode' => $endType === 'hotel' ? $endHotelBookingMode : null,
            ];
        }

        return $rows;
    }

    private function normalizeHotelStays(array $rows, int $durationDays): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $hotelId = (int) ($row['hotel_id'] ?? 0);
            $dayNumber = (int) ($row['day_number'] ?? 0);
            $nightCount = (int) ($row['night_count'] ?? 0);
            $roomCount = (int) ($row['room_count'] ?? 0);

            if ($hotelId <= 0 || $dayNumber < 1 || $dayNumber > $durationDays || $nightCount < 1 || $roomCount < 1) {
                continue;
            }

            $maxNightCount = max(1, ($durationDays - $dayNumber) + 1);
            $normalized[] = [
                'hotel_id' => $hotelId,
                'day_number' => $dayNumber,
                'night_count' => min($nightCount, $maxNightCount),
                'room_count' => $roomCount,
            ];
        }

        usort($normalized, function (array $left, array $right): int {
            $dayComparison = $left['day_number'] <=> $right['day_number'];
            if ($dayComparison !== 0) {
                return $dayComparison;
            }

            return $left['hotel_id'] <=> $right['hotel_id'];
        });

        $unique = [];
        foreach ($normalized as $row) {
            $unique[$row['hotel_id'].'-'.$row['day_number']] = $row;
        }

        return array_values($unique);
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
                'day_include' => $row['day_include'] ?? null,
                'day_exclude' => $row['day_exclude'] ?? null,
                'main_experience_type' => $row['main_experience_type'],
                'main_tourist_attraction_id' => $row['main_tourist_attraction_id'],
                'main_activity_id' => $row['main_activity_id'],
                'main_food_beverage_id' => $row['main_food_beverage_id'],
                'start_point_type' => $row['start_point_type'],
                'start_airport_id' => $row['start_airport_id'],
                'start_hotel_id' => $row['start_hotel_id'] ?? null,
                'start_hotel_room_id' => $row['start_hotel_room_id'] ?? null,
                'start_hotel_booking_mode' => $row['start_hotel_booking_mode'] ?? null,
                'end_point_type' => $row['end_point_type'],
                'end_airport_id' => $row['end_airport_id'],
                'end_hotel_id' => $row['end_hotel_id'] ?? null,
                'end_hotel_room_id' => $row['end_hotel_room_id'] ?? null,
                'end_hotel_booking_mode' => $row['end_hotel_booking_mode'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $rows);

        $itinerary->dayPoints()->insert($payload);
    }

    private function syncHotelStays(Itinerary $itinerary, array $rows): void
    {
        if (! Schema::hasTable('hotel_itinerary')) {
            return;
        }

        DB::table('hotel_itinerary')
            ->where('itinerary_id', $itinerary->id)
            ->delete();

        if ($rows === []) {
            return;
        }

        $timestamp = now();
        $payload = array_map(function (array $row) use ($itinerary, $timestamp): array {
            return [
                'itinerary_id' => $itinerary->id,
                'hotel_id' => (int) $row['hotel_id'],
                'day_number' => (int) $row['day_number'],
                'night_count' => (int) $row['night_count'],
                'room_count' => (int) $row['room_count'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, $rows);

        DB::table('hotel_itinerary')->insert($payload);
    }

    private function normalizeDailyTransportUnits(array $rows, int $durationDays): array
    {
        $normalized = [];
        $seen = [];
        $countByDay = [];
        foreach ($rows as $row) {
            $day = (int) ($row['day_number'] ?? 0);
            if ($day < 1 || $day > $durationDays) {
                continue;
            }
            $transportUnitId = (int) ($row['transport_unit_id'] ?? 0);
            if ($transportUnitId <= 0) {
                continue;
            }
            $key = $day . '-' . $transportUnitId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $countByDay[$day] = (int) ($countByDay[$day] ?? 0) + 1;
            if ($countByDay[$day] > 10) {
                throw ValidationException::withMessages([
                    'daily_transport_units' => "Day {$day} can contain maximum 10 transport units.",
                ]);
            }
            $normalized[] = [
                'day_number' => $day,
                'transport_unit_id' => $transportUnitId,
            ];
        }

        usort($normalized, function (array $left, array $right): int {
            if ($left['day_number'] !== $right['day_number']) {
                return $left['day_number'] <=> $right['day_number'];
            }

            return $left['transport_unit_id'] <=> $right['transport_unit_id'];
        });

        return array_values($normalized);
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

    private function buildItineraryAuditSnapshot(Itinerary $itinerary): array
    {
        $itinerary->load([
            'touristAttractions:id,name',
            'itineraryActivities:id,itinerary_id,activity_id,pax,pax_adult,pax_child,day_number,start_time,end_time,travel_minutes_to_next,visit_order',
            'itineraryActivities.activity:id,name',
            'itineraryIslandTransfers:id,itinerary_id,island_transfer_id,pax,day_number,start_time,end_time,travel_minutes_to_next,visit_order',
            'itineraryIslandTransfers.islandTransfer:id,name',
            'itineraryFoodBeverages:id,itinerary_id,food_beverage_id,pax,day_number,start_time,end_time,travel_minutes_to_next,visit_order,meal_type',
            'itineraryFoodBeverages.foodBeverage:id,name',
            'itineraryTransportUnits:id,itinerary_id,transport_unit_id,day_number',
            'itineraryTransportUnits.transportUnit:id,name',
            'dayPoints:id,itinerary_id,day_number,day_start_time,day_start_travel_minutes,day_include,day_exclude,start_point_type,start_airport_id,start_hotel_id,start_hotel_room_id,start_hotel_booking_mode,end_point_type,end_airport_id,end_hotel_id,end_hotel_room_id,end_hotel_booking_mode',
        ]);

        return [
            'inquiry_id' => (int) ($itinerary->inquiry_id ?? 0),
            'title' => (string) ($itinerary->title ?? ''),
            'destination' => (string) ($itinerary->destination ?? ''),
            'arrival_transport_id' => (int) ($itinerary->arrival_transport_id ?? 0),
            'departure_transport_id' => (int) ($itinerary->departure_transport_id ?? 0),
            'duration_days' => (int) ($itinerary->duration_days ?? 0),
            'duration_nights' => (int) ($itinerary->duration_nights ?? 0),
            'description' => trim((string) ($itinerary->description ?? '')),
            'itinerary_include' => trim((string) ($itinerary->itinerary_include ?? '')),
            'itinerary_exclude' => trim((string) ($itinerary->itinerary_exclude ?? '')),
            'is_active' => (bool) ($itinerary->is_active ?? false),
            'status' => (string) ($itinerary->status ?? ''),
            'attractions' => $itinerary->touristAttractions
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->name ?? ''),
                    'day_number' => (int) ($row->pivot->day_number ?? 0),
                    'start_time' => $row->pivot->start_time ? substr((string) $row->pivot->start_time, 0, 5) : null,
                    'end_time' => $row->pivot->end_time ? substr((string) $row->pivot->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => (int) ($row->pivot->travel_minutes_to_next ?? 0),
                    'visit_order' => (int) ($row->pivot->visit_order ?? 0),
                ])
                ->sort(function (array $left, array $right): int {
                    $dayComparison = ($left['day_number'] ?? 0) <=> ($right['day_number'] ?? 0);
                    if ($dayComparison !== 0) {
                        return $dayComparison;
                    }
                    return ($left['visit_order'] ?? 0) <=> ($right['visit_order'] ?? 0);
                })
                ->values()
                ->toArray(),
            'activities' => $itinerary->itineraryActivities
                ->map(fn ($row) => [
                    'activity_id' => (int) ($row->activity_id ?? 0),
                    'activity_name' => (string) ($row->activity?->name ?? ''),
                    'pax' => (int) ($row->pax ?? 0),
                    'pax_adult' => (int) ($row->pax_adult ?? 0),
                    'pax_child' => (int) ($row->pax_child ?? 0),
                    'day_number' => (int) ($row->day_number ?? 0),
                    'start_time' => $row->start_time ? substr((string) $row->start_time, 0, 5) : null,
                    'end_time' => $row->end_time ? substr((string) $row->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => (int) ($row->travel_minutes_to_next ?? 0),
                    'visit_order' => (int) ($row->visit_order ?? 0),
                ])
                ->sort(function (array $left, array $right): int {
                    $dayComparison = ($left['day_number'] ?? 0) <=> ($right['day_number'] ?? 0);
                    if ($dayComparison !== 0) {
                        return $dayComparison;
                    }
                    $visitComparison = ($left['visit_order'] ?? 0) <=> ($right['visit_order'] ?? 0);
                    if ($visitComparison !== 0) {
                        return $visitComparison;
                    }
                    return ($left['activity_id'] ?? 0) <=> ($right['activity_id'] ?? 0);
                })
                ->values()
                ->toArray(),
            'island_transfers' => $itinerary->itineraryIslandTransfers
                ->map(fn ($row) => [
                    'island_transfer_id' => (int) ($row->island_transfer_id ?? 0),
                    'island_transfer_name' => (string) ($row->islandTransfer?->name ?? ''),
                    'pax' => (int) ($row->pax ?? 0),
                    'day_number' => (int) ($row->day_number ?? 0),
                    'start_time' => $row->start_time ? substr((string) $row->start_time, 0, 5) : null,
                    'end_time' => $row->end_time ? substr((string) $row->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => (int) ($row->travel_minutes_to_next ?? 0),
                    'visit_order' => (int) ($row->visit_order ?? 0),
                ])
                ->sort(function (array $left, array $right): int {
                    $dayComparison = ($left['day_number'] ?? 0) <=> ($right['day_number'] ?? 0);
                    if ($dayComparison !== 0) {
                        return $dayComparison;
                    }
                    $visitComparison = ($left['visit_order'] ?? 0) <=> ($right['visit_order'] ?? 0);
                    if ($visitComparison !== 0) {
                        return $visitComparison;
                    }
                    return ($left['island_transfer_id'] ?? 0) <=> ($right['island_transfer_id'] ?? 0);
                })
                ->values()
                ->toArray(),
            'food_beverages' => $itinerary->itineraryFoodBeverages
                ->map(fn ($row) => [
                    'food_beverage_id' => (int) ($row->food_beverage_id ?? 0),
                    'food_beverage_name' => (string) ($row->foodBeverage?->name ?? ''),
                    'pax' => (int) ($row->pax ?? 0),
                    'day_number' => (int) ($row->day_number ?? 0),
                    'start_time' => $row->start_time ? substr((string) $row->start_time, 0, 5) : null,
                    'end_time' => $row->end_time ? substr((string) $row->end_time, 0, 5) : null,
                    'travel_minutes_to_next' => (int) ($row->travel_minutes_to_next ?? 0),
                    'visit_order' => (int) ($row->visit_order ?? 0),
                    'meal_type' => (string) ($row->meal_type ?? ''),
                ])
                ->sort(function (array $left, array $right): int {
                    $dayComparison = ($left['day_number'] ?? 0) <=> ($right['day_number'] ?? 0);
                    if ($dayComparison !== 0) {
                        return $dayComparison;
                    }
                    $visitComparison = ($left['visit_order'] ?? 0) <=> ($right['visit_order'] ?? 0);
                    if ($visitComparison !== 0) {
                        return $visitComparison;
                    }
                    return ($left['food_beverage_id'] ?? 0) <=> ($right['food_beverage_id'] ?? 0);
                })
                ->values()
                ->toArray(),
            'day_points' => $itinerary->dayPoints
                ->map(fn ($row) => [
                    'day_number' => (int) ($row->day_number ?? 0),
                    'day_start_time' => $row->day_start_time ? substr((string) $row->day_start_time, 0, 5) : null,
                    'day_start_travel_minutes' => (int) ($row->day_start_travel_minutes ?? 0),
                    'day_include' => (string) ($row->day_include ?? ''),
                    'day_exclude' => (string) ($row->day_exclude ?? ''),
                    'start_point_type' => (string) ($row->start_point_type ?? ''),
                    'start_airport_id' => (int) ($row->start_airport_id ?? 0),
                    'start_hotel_id' => (int) ($row->start_hotel_id ?? 0),
                    'start_hotel_room_id' => (int) ($row->start_hotel_room_id ?? 0),
                    'start_hotel_booking_mode' => (string) ($row->start_hotel_booking_mode ?? ''),
                    'end_point_type' => (string) ($row->end_point_type ?? ''),
                    'end_airport_id' => (int) ($row->end_airport_id ?? 0),
                    'end_hotel_id' => (int) ($row->end_hotel_id ?? 0),
                    'end_hotel_room_id' => (int) ($row->end_hotel_room_id ?? 0),
                    'end_hotel_booking_mode' => (string) ($row->end_hotel_booking_mode ?? ''),
                ])
                ->sortBy('day_number')
                ->values()
                ->toArray(),
            'transport_units' => $itinerary->itineraryTransportUnits
                ->map(fn ($row) => [
                    'day_number' => (int) ($row->day_number ?? 0),
                    'transport_unit_id' => (int) ($row->transport_unit_id ?? 0),
                    'transport_unit_name' => (string) ($row->transportUnit?->name ?? ''),
                ])
                ->sortBy('day_number')
                ->values()
                ->toArray(),
        ];
    }

    private function calculateTimeFromDuration(string $startTime, int $durationMinutes): string
    {
        $start = Carbon::createFromFormat('H:i', $startTime);
        return $start->addMinutes(max(1, $durationMinutes))->format('H:i');
    }

    private function resolveMealTypeFromStartTime(?string $startTime): ?string
    {
        $raw = trim((string) $startTime);
        if ($raw === '') {
            return null;
        }

        try {
            $time = Carbon::createFromFormat('H:i', substr($raw, 0, 5));
        } catch (\Throwable $e) {
            return null;
        }

        $hour = (int) $time->format('H');

        if ($hour < 11) {
            return 'Breakfast';
        }
        if ($hour < 16) {
            return 'Lunch';
        }

        return 'Dinner';
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
                        [Airport::class, 'province'],
            [Vendor::class, 'province'],
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

    private function resolveDestinationId(?string $destination): ?int
    {
        $destination = trim((string) $destination);
        if ($destination === '') {
            return null;
        }

        return Destination::query()
            ->where('name', $destination)
            ->orWhere('city', $destination)
            ->orWhere('province', $destination)
            ->value('id');
    }

    private function buildDuplicatedTitle(string $title): string
    {
        $baseTitle = trim($title) !== '' ? trim($title) : 'Untitled Itinerary';
        $candidate = $baseTitle . ' (Copy)';

        if (! Itinerary::query()->where('title', $candidate)->exists()) {
            return $candidate;
        }

        $counter = 2;
        while (Itinerary::query()->where('title', "{$candidate} {$counter}")->exists()) {
            $counter++;
        }

        return "{$candidate} {$counter}";
    }

    /**
     * @return array{activity_name:string,region:string,vendor:string}
     */
    private function parseManualActivityInputFormat(string $rawInput): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $rawInput) ?? '');
        if ($normalized === '') {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "Activity Name, Region, Vendor".',
            ]);
        }

        $parts = preg_split('/\s*,\s*/', $normalized) ?: [];
        $parts = array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $parts
        ), static fn ($value) => $value !== ''));

        if (count($parts) < 3) {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "Activity Name, Region, Vendor". Example: ATV Ride, Ubud, Bali Adventure.',
            ]);
        }

        $activityName = (string) ($parts[0] ?? '');
        $regionName = (string) ($parts[1] ?? '');
        $vendorName = trim(implode(', ', array_slice($parts, 2)));
        if ($activityName === '' || $regionName === '' || $vendorName === '') {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "Activity Name, Region, Vendor".',
            ]);
        }

        return [
            'activity_name' => $activityName,
            'region' => $regionName,
            'vendor' => $vendorName,
        ];
    }

    private function resolveOrCreateVendorForManualActivity(string $vendorName, string $regionName): Vendor
    {
        $vendor = Vendor::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($vendorName))])
            ->first();

        if ($vendor) {
            if (! $vendor->is_active) {
                $vendor->is_active = true;
                $vendor->save();
            }

            return $vendor;
        }

        return Vendor::query()->create([
            'name' => trim($vendorName),
            'location' => trim($regionName),
            'city' => trim($regionName),
            'province' => trim($regionName),
            'is_active' => true,
        ]);
    }

    /**
     * @return array{ name:string, region:string, destination:string }
     */
    private function parseManualTouristAttractionInputFormat(string $rawInput): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $rawInput) ?? '');
        if ($normalized === '') {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "Attraction Name, Region, Destination".',
            ]);
        }

        $parts = preg_split('/\s*,\s*/', $normalized) ?: [];
        $parts = array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $parts
        ), static fn ($value) => $value !== ''));

        if (count($parts) < 3) {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "Attraction Name, Region, Destination". Example: Tanah Lot, Tabanan, Bali.',
            ]);
        }

        $name = (string) ($parts[0] ?? '');
        $region = (string) ($parts[1] ?? '');
        $destination = trim(implode(', ', array_slice($parts, 2)));
        if ($name === '' || $region === '' || $destination === '') {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "Attraction Name, Region, Destination".',
            ]);
        }

        return [
            'name' => $name,
            'region' => $region,
            'destination' => $destination,
        ];
    }

    /**
     * @return array{name:string,region:string,vendor:string}
     */
    private function parseManualFoodBeverageInputFormat(string $rawInput): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $rawInput) ?? '');
        if ($normalized === '') {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "F&B Name, Region, Vendor".',
            ]);
        }

        $parts = preg_split('/\s*,\s*/', $normalized) ?: [];
        $parts = array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $parts
        ), static fn ($value) => $value !== ''));

        if (count($parts) < 3) {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "F&B Name, Region, Vendor". Example: Seafood Dinner, Jimbaran, Ocean Grill.',
            ]);
        }

        $name = (string) ($parts[0] ?? '');
        $region = (string) ($parts[1] ?? '');
        $vendor = trim(implode(', ', array_slice($parts, 2)));
        if ($name === '' || $region === '' || $vendor === '') {
            throw ValidationException::withMessages([
                'name' => 'Manual format is required: "F&B Name, Region, Vendor".',
            ]);
        }

        return [
            'name' => $name,
            'region' => $region,
            'vendor' => $vendor,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildActivitySuggestionOptions(string $keyword = '', string $destination = '', string $region = '', int $limit = 12): array
    {
        $keyword = trim((string) $keyword);
        $destination = trim((string) $destination);
        $region = trim((string) $region);
        $query = Activity::query()
            ->with([
                'vendor:id,name,city,province,location,latitude,longitude,destination_id',
                'vendor.destination:id,name,city,province',
            ])
            ->where('is_active', true);

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhereHas('vendor', function ($vendorQuery) use ($keyword) {
                        $vendorQuery->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('city', 'like', '%' . $keyword . '%')
                            ->orWhere('province', 'like', '%' . $keyword . '%');
                });
            });
        }
        if ($destination !== '') {
            $query->whereHas('vendor', function ($vendorQuery) use ($destination) {
                $vendorQuery->where(function ($nested) use ($destination) {
                    $nested->where('city', 'like', '%' . $destination . '%')
                        ->orWhere('province', 'like', '%' . $destination . '%')
                        ->orWhere('location', 'like', '%' . $destination . '%')
                        ->orWhereHas('destination', function ($destinationQuery) use ($destination) {
                            $destinationQuery->where('name', 'like', '%' . $destination . '%')
                                ->orWhere('city', 'like', '%' . $destination . '%')
                                ->orWhere('province', 'like', '%' . $destination . '%');
                        });
                });
            });
        }
        if ($region !== '') {
            $query->whereHas('vendor', function ($vendorQuery) use ($region) {
                $vendorQuery->where('city', 'like', '%' . $region . '%')
                    ->orWhere('province', 'like', '%' . $region . '%')
                    ->orWhere('location', 'like', '%' . $region . '%');
            });
        }

        return $query
            ->orderBy('name')
            ->limit(max(5, min(50, $limit)))
            ->get(['id', 'vendor_id', 'name', 'duration_minutes'])
            ->map(fn (Activity $activity) => $this->formatActivitySuggestionItem($activity))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTouristAttractionSuggestionOptions(string $keyword = '', string $destination = '', string $region = '', int $limit = 12): array
    {
        $keyword = trim((string) $keyword);
        $destination = trim((string) $destination);
        $region = trim((string) $region);

        $query = TouristAttraction::query()
            ->with('destination:id,name,city,province')
            ->where('is_active', true);

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('city', 'like', '%' . $keyword . '%')
                    ->orWhere('province', 'like', '%' . $keyword . '%')
                    ->orWhere('location', 'like', '%' . $keyword . '%')
                    ->orWhere('source', 'like', '%' . $keyword . '%');
            });
        }
        if ($destination !== '') {
            $query->where(function ($builder) use ($destination) {
                $builder->where('city', 'like', '%' . $destination . '%')
                    ->orWhere('province', 'like', '%' . $destination . '%')
                    ->orWhere('location', 'like', '%' . $destination . '%')
                    ->orWhereHas('destination', function ($destinationQuery) use ($destination) {
                        $destinationQuery->where('name', 'like', '%' . $destination . '%')
                            ->orWhere('city', 'like', '%' . $destination . '%')
                            ->orWhere('province', 'like', '%' . $destination . '%');
                    });
            });
        }
        if ($region !== '') {
            $query->where(function ($builder) use ($region) {
                $builder->where('city', 'like', '%' . $region . '%')
                    ->orWhere('province', 'like', '%' . $region . '%')
                    ->orWhere('location', 'like', '%' . $region . '%');
            });
        }

        return $query
            ->orderBy('name')
            ->limit(max(5, min(50, $limit)))
            ->get(['id', 'name', 'destination_id', 'city', 'province', 'location', 'source', 'latitude', 'longitude', 'ideal_visit_minutes'])
            ->map(fn (TouristAttraction $attraction) => $this->formatTouristAttractionSuggestionItem($attraction))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFoodBeverageSuggestionOptions(string $keyword = '', string $destination = '', string $region = '', int $limit = 12): array
    {
        $keyword = trim((string) $keyword);
        $destination = trim((string) $destination);
        $region = trim((string) $region);

        $query = FoodBeverage::query()
            ->with([
                'vendor:id,name,city,province,location,latitude,longitude,destination_id',
                'vendor.destination:id,name,city,province',
            ])
            ->where('is_active', true);

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhereHas('vendor', function ($vendorQuery) use ($keyword) {
                        $vendorQuery->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('city', 'like', '%' . $keyword . '%')
                            ->orWhere('province', 'like', '%' . $keyword . '%');
                    });
            });
        }
        if ($destination !== '') {
            $query->whereHas('vendor', function ($vendorQuery) use ($destination) {
                $vendorQuery->where(function ($nested) use ($destination) {
                    $nested->where('city', 'like', '%' . $destination . '%')
                        ->orWhere('province', 'like', '%' . $destination . '%')
                        ->orWhere('location', 'like', '%' . $destination . '%')
                        ->orWhereHas('destination', function ($destinationQuery) use ($destination) {
                            $destinationQuery->where('name', 'like', '%' . $destination . '%')
                                ->orWhere('city', 'like', '%' . $destination . '%')
                                ->orWhere('province', 'like', '%' . $destination . '%');
                        });
                });
            });
        }
        if ($region !== '') {
            $query->whereHas('vendor', function ($vendorQuery) use ($region) {
                $vendorQuery->where('city', 'like', '%' . $region . '%')
                    ->orWhere('province', 'like', '%' . $region . '%')
                    ->orWhere('location', 'like', '%' . $region . '%');
            });
        }

        return $query
            ->orderBy('name')
            ->limit(max(5, min(50, $limit)))
            ->get(['id', 'vendor_id', 'name', 'duration_minutes'])
            ->map(fn (FoodBeverage $foodBeverage) => $this->formatFoodBeverageSuggestionItem($foodBeverage))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTouristAttractionSuggestionItem(TouristAttraction $attraction): array
    {
        $region = trim((string) ($attraction->city ?? '')) !== ''
            ? trim((string) $attraction->city)
            : (trim((string) ($attraction->province ?? '')) !== ''
                ? trim((string) $attraction->province)
                : (trim((string) ($attraction->location ?? '')) !== ''
                    ? trim((string) $attraction->location)
                    : '-'));
        $destinationName = trim((string) ($attraction->destination?->name ?? '')) !== ''
            ? trim((string) $attraction->destination?->name)
            : '-';
        $label = trim((string) $attraction->name) . ', ' . $region . ', ' . $destinationName;

        return [
            'id' => (int) $attraction->id,
            'name' => (string) $attraction->name,
            'label' => $label,
            'destination' => $destinationName,
            'region' => $region,
            'provider' => trim((string) ($attraction->source ?? '')),
            'city' => trim((string) ($attraction->city ?? '')),
            'location' => trim((string) ($attraction->location ?? '')),
            'province' => trim((string) ($attraction->province ?? '')),
            'latitude' => $attraction->latitude,
            'longitude' => $attraction->longitude,
            'ideal_visit_minutes' => max(1, (int) ($attraction->ideal_visit_minutes ?? 120)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatActivitySuggestionItem(Activity $activity): array
    {
        $vendorName = trim((string) ($activity->vendor?->name ?? ''));
        $city = trim((string) ($activity->vendor?->city ?? ''));
        $province = trim((string) ($activity->vendor?->province ?? ''));
        $region = $city !== '' ? $city : ($province !== '' ? $province : '-');
        $safeVendorName = $vendorName !== '' ? $vendorName : '-';
        $label = trim((string) $activity->name) . ', ' . $region . ', ' . $safeVendorName;

        return [
            'id' => (int) $activity->id,
            'name' => (string) $activity->name,
            'label' => $label,
            'vendor_name' => $vendorName,
            'destination' => (string) ($activity->vendor?->destination?->name ?? ''),
            'city' => $city,
            'province' => $province,
            'location' => trim((string) ($activity->vendor?->location ?? '')),
            'latitude' => $activity->vendor?->latitude,
            'longitude' => $activity->vendor?->longitude,
            'duration_minutes' => max(1, (int) ($activity->duration_minutes ?? 60)),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function formatFoodBeverageSuggestionItem(FoodBeverage $foodBeverage): array
    {
        $vendorName = trim((string) ($foodBeverage->vendor?->name ?? ''));
        $city = trim((string) ($foodBeverage->vendor?->city ?? ''));
        $province = trim((string) ($foodBeverage->vendor?->province ?? ''));
        $region = $city !== '' ? $city : ($province !== '' ? $province : '-');
        $safeVendorName = $vendorName !== '' ? $vendorName : '-';
        $label = trim((string) $foodBeverage->name) . ' - ' . $safeVendorName;

        return [
            'id' => (int) $foodBeverage->id,
            'name' => (string) $foodBeverage->name,
            'label' => $label,
            'vendor_name' => $vendorName,
            'destination' => (string) ($foodBeverage->vendor?->destination?->name ?? ''),
            'city' => $city,
            'province' => $province,
            'location' => trim((string) ($foodBeverage->vendor?->location ?? '')),
            'latitude' => $foodBeverage->vendor?->latitude,
            'longitude' => $foodBeverage->vendor?->longitude,
            'duration_minutes' => max(1, (int) ($foodBeverage->duration_minutes ?? 60)),
            'region' => $region,
        ];
    }

    private function resolveOrCreateActivityType(string $name): ActivityType
    {
        $normalizedName = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($normalizedName === '') {
            $normalizedName = 'General';
        }

        $existing = ActivityType::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }

            return $existing;
        }

        $baseSlug = Str::slug($normalizedName);
        if ($baseSlug === '') {
            $baseSlug = 'activity-type';
        }
        $slug = $baseSlug;
        $counter = 2;
        while (ActivityType::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return ActivityType::query()->create([
            'name' => $normalizedName,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function logManualItemCreatedForEditorValidation(
        string $itemType,
        int $itemId,
        string $itemName,
        string $editUrl
    ): void {
        if ($itemId <= 0) {
            return;
        }

        $actor = auth()->user();
        $normalizedType = strtolower(trim($itemType));
        $subjectType = match ($normalizedType) {
            'activity' => Activity::class,
            'attraction' => TouristAttraction::class,
            'fnb' => FoodBeverage::class,
            default => Activity::class,
        };

        ActivityLog::query()->create([
            'user_id' => $actor?->id,
            'module' => 'itinerary_day_planner',
            'action' => 'manual_item_created',
            'subject_id' => $itemId,
            'subject_type' => $subjectType,
            'properties' => [
                'item_type' => $normalizedType,
                'item_name' => trim((string) $itemName),
                'creator_name' => trim((string) ($actor?->name ?? 'Unknown')),
                'edit_url' => trim((string) $editUrl),
                'source' => 'itinerary_day_planner',
                'requires_validation' => true,
            ],
        ]);
    }

    private function pendingManualItemValidationQuery($user)
    {
        return ActivityLog::query()
            ->where('module', 'itinerary_day_planner')
            ->where('action', 'manual_item_created')
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', '!=', (int) ($user?->id ?? 0));
            })
            ->where(function ($query): void {
                $query->whereNull('properties')
                    ->orWhereRaw("JSON_EXTRACT(properties, '$.validated_at') IS NULL");
            });
    }

}
