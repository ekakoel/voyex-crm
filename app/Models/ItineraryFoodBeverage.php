<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryFoodBeverage extends Model
{
    protected $fillable = [
        'itinerary_id',
        'food_beverage_id',
        'day_number',
        'pax',
        'start_time',
        'end_time',
        'travel_minutes_to_next',
        'visit_order',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function foodBeverage()
    {
        return $this->belongsTo(FoodBeverage::class);
    }
}
