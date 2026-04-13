<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Destination;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Itinerary extends Model
{
    use SoftDeletes;
    use HasAudit;
    use LogsActivity;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FINAL = 'final';

    public const STATUS_OPTIONS = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSED,
        self::STATUS_FINAL,
    ];

    public const FINAL_STATUS = self::STATUS_FINAL;

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
        'itinerary_include',
        'itinerary_exclude',
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

    public function syncLifecycleStatus(): void
    {
        $this->loadMissing('quotation:id,itinerary_id,status');

        $nextStatus = self::STATUS_PENDING;
        if ($this->quotation) {
            $quotationStatus = (string) ($this->quotation->status ?? '');
            $nextStatus = $quotationStatus === Quotation::FINAL_STATUS
                ? self::STATUS_FINAL
                : self::STATUS_PROCESSED;
        }

        if ((string) ($this->status ?? '') === $nextStatus) {
            return;
        }

        $this->update([
            'status' => $nextStatus,
        ]);
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

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function hotels()
    {
        if (Schema::hasTable('hotel_itinerary')) {
            return $this->belongsToMany(Hotel::class, 'hotel_itinerary', 'itinerary_id', 'hotel_id')
                ->withPivot(['day_number', 'night_count', 'room_count'])
                ->withTimestamps()
                ->orderByPivot('day_number')
                ->orderBy('name');
        }

        return $this->hasMany(Hotel::class, 'id', 'id')->whereRaw('1 = 0');
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


