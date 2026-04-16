<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\ActivityLog;
use App\Models\Concerns\HasAudit;
use App\Models\Inquiry;
use App\Models\QuotationComment;
use App\Models\QuotationApproval;
use App\Models\QuotationItem;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;
    use HasAudit, LogsActivity;

    public const STATUS_OPTIONS = [
        'draft',
        'processed',
        'pending',
        'approved',
        'rejected',
        'final',
    ];
    public const VALIDATION_STATUS_OPTIONS = [
        'pending',
        'partial',
        'valid',
    ];

    public const FINAL_STATUS = 'final';
    protected $fillable = [
        'quotation_number',
        'inquiry_id',
        'itinerary_id',
        'status',
        'validation_status',
        'validity_date',
        'sub_total',
        'discount_type',
        'discount_value',
        'final_amount',
        'approval_note',
        'approval_note_by',
        'approval_note_at',
        'validated_at',
        'validated_by',
        'approved_by',
        'approved_at',
    ];
    
    protected $casts = [
        'validity_date' => 'date',
        'sub_total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'approval_note_at' => 'datetime',
        'validated_at' => 'datetime',
    ];


    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function booking()
    {
        return $this->hasOne(Booking::class);
    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function comments()
    {
        return $this->hasMany(QuotationComment::class)->latest();
    }

    public function approvals()
    {
        return $this->hasMany(QuotationApproval::class)->latest('approved_at');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvalNoteBy()
    {
        return $this->belongsTo(User::class, 'approval_note_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isFinal(): bool
    {
        return $this->status === self::FINAL_STATUS;
    }

}





