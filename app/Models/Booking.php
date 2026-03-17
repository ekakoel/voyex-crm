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
        'draft',
        'processed',
        'pending',
        'approved',
        'rejected',
        'final',
    ];

    public const FINAL_STATUS = 'final';
    protected $fillable = [
        'booking_number',
        'quotation_id',
        'travel_date',
        'status',
        'notes',
    ];
    protected $casts = [
        'travel_date' => 'date',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function isFinal(): bool
    {
        return $this->status === self::FINAL_STATUS;
    }
}



