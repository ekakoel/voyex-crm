<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ItineraryDayPoint extends Model
{
    use LogsActivity;

    protected $fillable = [
        'itinerary_id',
        'day_number',
        'day_start_time',
        'day_start_travel_minutes',
        'day_include',
        'day_exclude',
        'main_experience_type',
        'main_tourist_attraction_id',
        'main_activity_id',
        'main_food_beverage_id',
        'start_point_type',
        'start_airport_id',
        'start_hotel_id',
        'start_hotel_room_id',
        'end_point_type',
        'end_airport_id',
        'end_hotel_id',
        'end_hotel_room_id',
        'end_hotel_booking_mode',
    ];

    protected $casts = [
        'day_start_travel_minutes' => 'integer',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function startAirport()
    {
        return $this->belongsTo(Airport::class, 'start_airport_id');
    }

    public function startHotel()
    {
        return $this->belongsTo(Hotel::class, 'start_hotel_id');
    }

    public function startHotelRoom()
    {
        return $this->belongsTo(HotelRoom::class, 'start_hotel_room_id');
    }

    public function endAirport()
    {
        return $this->belongsTo(Airport::class, 'end_airport_id');
    }

    public function endHotel()
    {
        return $this->belongsTo(Hotel::class, 'end_hotel_id');
    }

    public function endHotelRoom()
    {
        return $this->belongsTo(HotelRoom::class, 'end_hotel_room_id');
    }


    public function mainTouristAttraction()
    {
        return $this->belongsTo(TouristAttraction::class, 'main_tourist_attraction_id');
    }

    public function mainActivity()
    {
        return $this->belongsTo(Activity::class, 'main_activity_id');
    }

    public function mainFoodBeverage()
    {
        return $this->belongsTo(FoodBeverage::class, 'main_food_beverage_id');
    }
}



