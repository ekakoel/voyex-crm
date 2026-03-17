<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Models\User;
use App\Models\Destination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Itinerary extends Model
{
    use SoftDeletes;
    use HasAudit;

    public const STATUS_OPTIONS = [
        'draft',
        'processed',
        'pending',
        'approved',
        'rejected',
        'final',
    ];

    public const FINAL_STATUS = 'final';

    protected $fillable = [
        'inquiry_id',
        'created_by',
        'title',
        'destination',
        'destination_id',
        'arrival_transport_id',
        'departure_transport_id',
        'duration_days',
        'duration_nights',
        'description',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function isFinal(): bool
    {
        return $this->status === self::FINAL_STATUS;
    }

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

    public function destination()
    {
        return $this->belongsTo(Destination::class);
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






