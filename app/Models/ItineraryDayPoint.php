<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryDayPoint extends Model
{
    protected $fillable = [
        'itinerary_id',
        'day_number',
        'day_start_time',
        'day_start_travel_minutes',
        'main_experience_type',
        'main_tourist_attraction_id',
        'main_activity_id',
        'main_food_beverage_id',
        'start_point_type',
        'start_airport_id',
        'start_accommodation_id',
        'end_point_type',
        'end_airport_id',
        'end_accommodation_id',
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

    public function startAccommodation()
    {
        return $this->belongsTo(Accommodation::class, 'start_accommodation_id');
    }

    public function endAirport()
    {
        return $this->belongsTo(Airport::class, 'end_airport_id');
    }

    public function endAccommodation()
    {
        return $this->belongsTo(Accommodation::class, 'end_accommodation_id');
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
