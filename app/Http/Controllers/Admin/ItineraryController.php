<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesActivityTimelineAjax;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Airport;
use App\Models\ActivityLog;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\Inquiry;
use App\Models\Itinerary;
use App\Models\TouristAttraction;
use App\Models\TransportUnit;
use App\Services\ActivityAuditLogger;
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
        private readonly ActivityAuditLogger $activityAuditLogger
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
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude', 'ideal_visit_minutes']);
        $activities = Activity::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'activity_type', 'duration_minutes', 'adult_publish_rate', 'child_publish_rate']);
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

        return view('modules.itineraries.create', compact('touristAttractions', 'activities', 'foodBeverages', 'hotels', 'airports', 'transportUnits', 'destinations', 'inquiries', 'prefillInquiryId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
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
            'daily_start_point_types.*' => ['nullable', 'string', 'in:previous_day_end,hotel,airport'],
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
            'daily_end_point_types.*' => ['nullable', 'string', 'in:hotel,airport'],
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
        unset($validated['itinerary_food_beverage_items']);
        unset($validated['daily_transport_units']);
        $this->validateScheduleItems($items, (int) $validated['duration_days']);
        $this->validateActivityItems($activityItems, (int) $validated['duration_days']);
        $this->validateFoodBeverageItems($foodBeverageItems, (int) $validated['duration_days']);

        $itinerary = Itinerary::withoutActivityLogging(function () use ($validated): Itinerary {
            return Itinerary::query()->create($validated);
        });
        $itinerary->touristAttractions()->sync($this->buildSyncPayload($items));
        $this->syncItineraryActivities($itinerary, $activityItems);
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
                ->with('success', 'Itinerary duplicated successfully. Silakan sesuaikan data lalu simpan.');
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
                ->with('error', 'Itinerary sudah final dan tidak dapat diubah.');
        }
        if ($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'Itinerary cannot be edited because the related quotation is approved.');
        }
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,adult_publish_rate,child_publish_rate',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,publish_rate,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,name,seat_capacity',
            'itineraryTransportUnits.transportUnit.transport:id,name,transport_type',
            'dayPoints',
            'inquiry:id,inquiry_number,customer_id',
                        'arrivalTransport:id,name,transport_type',
            'departureTransport:id,name,transport_type',
        ]);
        $touristAttractions = TouristAttraction::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'destination_id', 'latitude', 'longitude', 'ideal_visit_minutes']);
        $activities = Activity::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'activity_type', 'duration_minutes', 'adult_publish_rate', 'child_publish_rate']);
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

        return view('modules.itineraries.edit', compact('itinerary', 'touristAttractions', 'activities', 'foodBeverages', 'hotels', 'airports', 'transportUnits', 'destinations', 'inquiries', 'activityLogs'));
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

    public function show(Request $request, Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude,description',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,adult_publish_rate,child_publish_rate,includes,excludes,benefits,notes',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,publish_rate,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,name,seat_capacity',
            'itineraryTransportUnits.transportUnit.transport:id,name,transport_type',
            'dayPoints',
            'dayPoints.startAirport:id,name,location,city,province,latitude,longitude',
            'dayPoints.startHotel:id,name,address,city,province,latitude,longitude',
            'dayPoints.startHotelRoom:id,hotels_id,rooms,view',
            'dayPoints.endAirport:id,name,location,city,province,latitude,longitude',
            'dayPoints.endHotel:id,name,address,city,province,latitude,longitude',
            'dayPoints.endHotelRoom:id,hotels_id,rooms,view',
            'inquiry:id,inquiry_number,customer_id',
            'inquiry.customer:id,name',
            'quotation:id,itinerary_id,status',
                        'arrivalTransport:id,name,transport_type',
            'departureTransport:id,name,transport_type',
        ]);
        $dayGroups = $itinerary->touristAttractions->groupBy(fn ($attraction) => (int) $attraction->pivot->day_number);
        $activityDayGroups = $itinerary->itineraryActivities->groupBy(fn ($item) => (int) $item->day_number);
        $foodBeverageDayGroups = $itinerary->itineraryFoodBeverages->groupBy(fn ($item) => (int) $item->day_number);
        $transportUnitByDay = $itinerary->itineraryTransportUnits->keyBy(fn ($item) => (int) $item->day_number);

        $activities = $itinerary->activities()
            ->with('user:id,name')
            ->latest()
            ->paginate(5, ['*'], 'activity_page')
            ->withQueryString();

        if ($this->wantsActivityTimelineFragment($request)) {
            return $this->activityTimelineFragmentResponse($activities);
        }

        return view('modules.itineraries.show', compact('itinerary', 'dayGroups', 'activityDayGroups', 'foodBeverageDayGroups', 'transportUnitByDay', 'activities'));
    }

    public function generatePdf(Request $request, Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude,description,gallery_images',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,adult_publish_rate,child_publish_rate,notes,includes,excludes,gallery_images',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryFoodBeverages.foodBeverage:id,vendor_id,name,service_type,duration_minutes,publish_rate,meal_period,notes,menu_highlights,gallery_images',
            'itineraryFoodBeverages.foodBeverage.vendor:id,name,location,city,province,latitude,longitude',
            'itineraryTransportUnits.transportUnit:id,name,brand_model,seat_capacity,luggage_capacity,air_conditioned,with_driver,images',
            'itineraryTransportUnits.transportUnit.transport:id,name,transport_type',
            'dayPoints',
            'dayPoints.startAirport:id,name,location,city,province',
            'dayPoints.startHotel:id,name,address,city,province',
            'dayPoints.startHotelRoom:id,hotels_id,rooms,view,cover',
            'dayPoints.endAirport:id,name,location,city,province',
            'dayPoints.endHotel:id,name,address,city,province',
            'dayPoints.endHotelRoom:id,hotels_id,rooms,view,cover',
            'inquiry:id,inquiry_number,customer_id,status,priority,source,deadline,notes',
            'inquiry.customer:id,name,code',
        ]);

        $scheduleByDay = [];
        $dayPointByDay = $itinerary->dayPoints->keyBy(fn ($point) => (int) $point->day_number);
        $transportUnitByDay = $itinerary->itineraryTransportUnits->keyBy(fn ($item) => (int) $item->day_number);
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
                        'thumbnail_data_uri' => null,
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
                    'thumbnail_data_uri' => null,
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
            $startBaseMinutes = $toMinutes($startTime !== '--:--' ? $startTime : null);
            $endTime = $lastEndBaseMinutes !== null
                ? ($fromMinutes($lastEndBaseMinutes + $lastTravelToEnd) ?? '--:--')
                : ($startBaseMinutes !== null
                    ? ($fromMinutes($startBaseMinutes + max(0, (int) ($startTravelMinutes ?? 0))) ?? '--:--')
                    : '--:--');
            $dayTransportItem = $transportUnitByDay[$day] ?? null;
            $dayTransportUnit = $dayTransportItem?->transportUnit;
            $transportMaster = $dayTransportUnit?->transport;
            $transportUnitImage = $dayTransportUnit
                ? $this->resolveGalleryImageDataUri($dayTransportUnit->images ?? [])
                : null;
            $transportImage = $transportUnitImage;
            $dayTransport = [
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
                'thumbnail_data_uri' => $transportImage,
            ];
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
                'transport_unit' => $dayTransport,
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

    public function update(Request $request, Itinerary $itinerary)
    {
        $itinerary->loadMissing(['quotation:id,itinerary_id,status']);
        if (! $this->canManageItinerary($itinerary, 'update')) {
            return $this->denyItineraryMutation($itinerary);
        }
        if ($itinerary->isFinal()) {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'Itinerary sudah final dan tidak dapat diubah.');
        }
        if ($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') {
            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'Itinerary cannot be updated because the related quotation is approved.');
        }
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
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
            'daily_start_point_types.*' => ['nullable', 'string', 'in:previous_day_end,hotel,airport'],
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
            'daily_end_point_types.*' => ['nullable', 'string', 'in:hotel,airport'],
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
        unset($validated['itinerary_food_beverage_items']);
        unset($validated['daily_transport_units']);
        $this->validateScheduleItems($items, (int) $validated['duration_days']);
        $this->validateActivityItems($activityItems, (int) $validated['duration_days']);
        $this->validateFoodBeverageItems($foodBeverageItems, (int) $validated['duration_days']);

        $beforeAudit = $this->buildItineraryAuditSnapshot($itinerary);

        Itinerary::withoutActivityLogging(function () use ($itinerary, $validated): void {
            $itinerary->update($validated);
        });
        $itinerary->touristAttractions()->sync($this->buildSyncPayload($items));
        $this->syncItineraryActivities($itinerary, $activityItems);
        $this->syncItineraryFoodBeverages($itinerary, $foodBeverageItems);
        $this->syncDayPoints($itinerary, $dayPoints);
        $this->syncHotelStays($itinerary, $hotelStays);
        $this->syncDailyTransportUnits($itinerary, $transportUnitsByDay);
        $itinerary->refresh();
        $this->activityAuditLogger->logUpdated($itinerary, $beforeAudit, $this->buildItineraryAuditSnapshot($itinerary), 'Itinerary');

        $this->syncInquiryProcessedStatus($itinerary->inquiry_id);

        return redirect()->route('itineraries.show', $itinerary)->with('success', 'Itinerary updated successfully.');
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
                ->with('error', 'Itinerary sudah final dan tidak dapat dihapus.');
        }

        if ($itinerary->quotation) {
            $reasons = ['quotation'];
            if ($itinerary->quotation->status === 'approved') {
                $reasons[0] = 'quotation (approved)';
            }
            if ($itinerary->quotation->booking) {
                $reasons[] = 'booking';
            }
            if ($itinerary->quotation->booking?->invoice) {
                $reasons[] = 'invoice';
            }

            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'Itinerary tidak bisa dihapus karena sudah terhubung ke ' . implode(', ', $reasons) . '. Hapus data terkait terlebih dahulu.');
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
                ->with('error', 'Itinerary sudah final dan tidak dapat diubah statusnya.');
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
            if ($itinerary->quotation->status === 'approved') {
                $reasons[0] = 'quotation (approved)';
            }
            if ($itinerary->quotation->booking) {
                $reasons[] = 'booking';
            }
            if ($itinerary->quotation->booking?->invoice) {
                $reasons[] = 'invoice';
            }

            return redirect()
                ->route('itineraries.show', $itinerary)
                ->with('error', 'Itinerary tidak bisa dinonaktifkan karena sudah terhubung ke ' . implode(', ', $reasons) . '. Hapus data terkait terlebih dahulu.');
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
            ->with('error', 'Hanya creator yang dapat mengubah atau menghapus itinerary ini.');
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
        $roomHotelMap = \App\Models\HotelRoom::query()
            ->pluck('hotels_id', 'id')
            ->all();
        $roomHotelMap = HotelRoom::query()
            ->pluck('hotels_id', 'id')
            ->all();

        for ($day = 1; $day <= $durationDays; $day++) {
            $startType = (string) ($startTypes[$day] ?? ($day === 1 ? 'airport' : 'previous_day_end'));
            if (! in_array($startType, ['previous_day_end', 'hotel', 'airport'], true)) {
                $startType = $day === 1 ? 'airport' : 'previous_day_end';
            }

            $endType = (string) ($endTypes[$day] ?? ($day === $durationDays ? 'airport' : 'hotel'));
            if (! in_array($endType, ['hotel', 'airport'], true)) {
                $endType = $day === $durationDays ? 'airport' : 'hotel';
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
            $mainExperienceType = (string) ($mainExperienceTypes[$day] ?? '');
            if (! in_array($mainExperienceType, ['attraction', 'activity', 'fnb'], true)) {
                $mainExperienceType = '';
            }
            $mainExperienceItemId = (int) ($mainExperienceItems[$day] ?? 0);

            if (in_array($startType, ['hotel', 'airport'], true) && $startItemId <= 0) {
                throw ValidationException::withMessages([
                    "daily_start_point_items.{$day}" => "Start point item on day {$day} is required.",
                ]);
            }
            if ($startType === 'hotel') {
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
                $startHotelBookingMode = null;
            }
            if (in_array($endType, ['hotel', 'airport'], true) && $endItemId <= 0) {
                throw ValidationException::withMessages([
                    "daily_end_point_items.{$day}" => "End point item on day {$day} is required.",
                ]);
            }
            if ($endType === 'hotel') {
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
                'start_point_type' => $startType,
                'start_airport_id' => $resolvedStartAirportId,
                'start_hotel_id' => $resolvedStartHotelId,
                'start_hotel_room_id' => $resolvedStartHotelRoomId,
                'start_hotel_booking_mode' => $resolvedStartHotelBookingMode,
                'end_point_type' => $endType,
                'end_airport_id' => $endType === 'airport' ? $endItemId : null,
                'end_hotel_id' => $endType === 'hotel' ? $endItemId : null,
                'end_hotel_room_id' => $endType === 'hotel' ? $endRoomId : null,
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

    private function buildItineraryAuditSnapshot(Itinerary $itinerary): array
    {
        $itinerary->load([
            'touristAttractions:id,name',
            'itineraryActivities:id,itinerary_id,activity_id,pax,pax_adult,pax_child,day_number,start_time,end_time,travel_minutes_to_next,visit_order',
            'itineraryActivities.activity:id,name',
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

}
