<?php

namespace App\Models;

use App\Models\ActivityLog;
use App\Models\Concerns\HasAudit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasAudit, LogsActivity;

    public const STATUS_OPTIONS = [
        'pending_confirmation',
        'confirmed',
        'awaiting_dp',
        'dp_received',
        'awaiting_balance',
        'ready_to_operate',
        'in_operation',
        'service_completed',
        'completed_unsettled',
        'completed_settled',
        'closed',
        'cancelled',
    ];

    public const FINAL_STATUS = 'closed';
    protected $fillable = [
        'booking_number',
        'quotation_id',
        'travel_date',
        'pax_adult',
        'pax_child',
        'status',
        'itinerary_snapshot',
    ];
    protected $casts = [
        'travel_date' => 'date',
        'pax_adult' => 'integer',
        'pax_child' => 'integer',
        'itinerary_snapshot' => 'array',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class)->latestOfMany('id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function adjustments()
    {
        return $this->hasMany(BookingAdjustment::class);
    }

    public function settlement()
    {
        return $this->hasOne(BookingSettlement::class)->latestOfMany('id');
    }

    public function isFinal(): bool
    {
        return $this->status === self::FINAL_STATUS;
    }
}

