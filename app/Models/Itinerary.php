<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $fillable = [
        'inquiry_id',
        'title',
        'duration_days',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function touristAttractions()
    {
        return $this->belongsToMany(TouristAttraction::class)
            ->withPivot(['day_number', 'start_time', 'end_time', 'travel_minutes_to_next', 'visit_order'])
            ->withTimestamps()
            ->orderByPivot('day_number')
            ->orderByPivot('visit_order');
    }

    public function itineraryActivities()
    {
        return $this->hasMany(ItineraryActivity::class)
            ->orderBy('day_number')
            ->orderBy('visit_order');
    }

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }
}
