<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ItineraryFoodBeverage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'itinerary_id',
        'food_beverage_id',
        'day_number',
        'pax',
        'pax_adult',
        'pax_child',
        'start_time',
        'end_time',
        'meal_type',
        'travel_minutes_to_next',
        'visit_order',
    ];

    protected $casts = [
        'pax' => 'integer',
        'pax_adult' => 'integer',
        'pax_child' => 'integer',
        'day_number' => 'integer',
        'travel_minutes_to_next' => 'integer',
        'visit_order' => 'integer',
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


