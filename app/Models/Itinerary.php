<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    use HasAudit;

    protected $fillable = [
        'inquiry_id',
        'created_by',
        'title',
        'destination',
        'arrival_transport_id',
        'departure_transport_id',
        'duration_days',
        'duration_nights',
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

    public function itineraryFoodBeverages()
    {
        return $this->hasMany(ItineraryFoodBeverage::class)
            ->orderBy('day_number')
            ->orderBy('visit_order');
    }

    public function itineraryTransportUnits()
    {
        return $this->hasMany(ItineraryTransportUnit::class)
            ->orderBy('day_number');
    }

    public function dayPoints()
    {
        return $this->hasMany(ItineraryDayPoint::class)
            ->orderBy('day_number');
    }

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotation()
    {
        return $this->hasOne(Quotation::class);
    }

    public function accommodations()
    {
        return $this->belongsToMany(Accommodation::class)
            ->withPivot(['day_number', 'night_count', 'room_count'])
            ->withTimestamps()
            ->orderByPivot('day_number')
            ->orderBy('name');
    }

    public function arrivalTransport()
    {
        return $this->belongsTo(Transport::class, 'arrival_transport_id');
    }

    public function departureTransport()
    {
        return $this->belongsTo(Transport::class, 'departure_transport_id');
    }
}
