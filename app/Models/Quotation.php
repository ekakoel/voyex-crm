<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\ActivityLog;
use App\Models\Concerns\HasAudit;
use App\Models\Inquiry;
use App\Models\QuotationApproval;
use App\Models\QuotationCustomerResponse;
use App\Models\QuotationFollowUp;
use App\Models\QuotationFollowUpNotification;
use App\Models\QuotationItem;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;
    use HasAudit, LogsActivity;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_NEED_VALIDATION = 'need_validation';
    public const STATUS_PENDING_VALIDATION = 'pending_validation';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_READY_TO_SEND = 'ready_to_send';
    public const STATUS_SENT = 'sent';
    public const STATUS_REVISION_REQUESTED = 'revision_requested';
    public const STATUS_UNDER_REVISION = 'under_revision';
    public const STATUS_NEED_REVALIDATION = 'need_revalidation';
    public const STATUS_PENDING_REVALIDATION = 'pending_revalidation';
    public const STATUS_CUSTOMER_APPROVED = 'customer_approved';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CONVERTED_TO_BOOKING = 'converted_to_booking';
    public const STATUS_BOOKING_CREATED = 'booking_created';
    public const STATUS_IN_OPERATION = 'in_operation';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_LOST = 'lost';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT,
        self::STATUS_NEED_VALIDATION,
        self::STATUS_READY_TO_SEND,
        self::STATUS_SENT,
        self::STATUS_REVISION_REQUESTED,
        self::STATUS_UNDER_REVISION,
        self::STATUS_NEED_REVALIDATION,
        self::STATUS_APPROVED,
        self::STATUS_CONVERTED_TO_BOOKING,
        self::STATUS_IN_OPERATION,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_LOST,
    ];
    public const VALIDATION_STATUS_OPTIONS = [
        'pending',
        'partial',
        'valid',
    ];

    public const FINAL_STATUS = self::STATUS_CONVERTED_TO_BOOKING;
    public const LEGACY_FINAL_STATUS = self::STATUS_BOOKING_CREATED;
    public const LEGACY_STATUS_OPTIONS = [
        self::STATUS_PENDING_VALIDATION,
        self::STATUS_VALIDATED,
        self::STATUS_PENDING_REVALIDATION,
        self::STATUS_CUSTOMER_APPROVED,
        self::STATUS_BOOKING_CREATED,
    ];
    public const LEGACY_STATUS_MAP = [
        self::STATUS_PENDING_VALIDATION => self::STATUS_NEED_VALIDATION,
        self::STATUS_PENDING_REVALIDATION => self::STATUS_NEED_REVALIDATION,
        self::STATUS_CUSTOMER_APPROVED => self::STATUS_APPROVED,
        self::STATUS_BOOKING_CREATED => self::STATUS_CONVERTED_TO_BOOKING,
        'accepted' => self::STATUS_APPROVED,
        'converted' => self::STATUS_CONVERTED_TO_BOOKING,
        'valid' => self::STATUS_READY_TO_SEND,
        'final' => self::STATUS_COMPLETED,
    ];
    protected $fillable = [
        'quotation_number',
        'order_number',
        'inquiry_id',
        'itinerary_id',
        'revision_of_id',
        'revision_number',
        'is_current_revision',
        'revision_reason',
        'status',
        'validation_status',
        'validity_date',
        'service_date',
        'pax_adult',
        'pax_child',
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
        'follow_up_status',
        'last_followed_up_at',
        'next_follow_up_at',
        'follow_up_count',
        'follow_up_until',
        'no_response_warning_at',
        'service_date_warning_at',
        'auto_status_reason',
        'auto_status_updated_at',
        'auto_status_locked',
        'sent_count',
    ];
    
    protected $casts = [
        'validity_date' => 'date',
        'service_date' => 'date',
        'pax_adult' => 'integer',
        'pax_child' => 'integer',
        'sub_total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'approval_note_at' => 'datetime',
        'validated_at' => 'datetime',
        'is_current_revision' => 'boolean',
        'last_followed_up_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'follow_up_until' => 'datetime',
        'no_response_warning_at' => 'datetime',
        'service_date_warning_at' => 'datetime',
        'auto_status_updated_at' => 'datetime',
        'auto_status_locked' => 'boolean',
        'follow_up_count' => 'integer',
        'sent_count' => 'integer',
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

    public function approvals()
    {
        return $this->hasMany(QuotationApproval::class)->latest('approved_at');
    }

    public function followUps()
    {
        return $this->hasMany(QuotationFollowUp::class)->latest('follow_up_at')->latest('id');
    }

    public function customerResponses()
    {
        return $this->hasMany(QuotationCustomerResponse::class)->latest('response_at')->latest('id');
    }

    public function pendingRevisionCustomerResponses()
    {
        return $this->hasMany(QuotationCustomerResponse::class)
            ->where('requires_revision', true)
            ->where('is_used_for_revision', false)
            ->latest('response_at')
            ->latest('id');
    }

    public function followUpNotifications()
    {
        return $this->hasMany(QuotationFollowUpNotification::class)->latest('due_at')->latest('id');
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
        return $this->isStatus(self::FINAL_STATUS);
    }

    public function isLockedForDirectEdit(): bool
    {
        return $this->isStatus(
            self::STATUS_SENT,
            self::STATUS_CUSTOMER_APPROVED,
            self::STATUS_APPROVED,
            self::STATUS_BOOKING_CREATED,
            self::STATUS_CONVERTED_TO_BOOKING,
            self::STATUS_IN_OPERATION,
            self::STATUS_COMPLETED
        );
    }

    public static function normalizeStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));
        if ($normalized === '') {
            return self::STATUS_DRAFT;
        }

        return self::LEGACY_STATUS_MAP[$normalized] ?? $normalized;
    }

    public function isStatus(string ...$statuses): bool
    {
        $current = self::normalizeStatus((string) ($this->status ?? ''));
        $normalizedStatuses = array_map(fn (string $status): string => self::normalizeStatus($status), $statuses);

        return in_array($current, $normalizedStatuses, true);
    }

    public function revisionOf()
    {
        return $this->belongsTo(self::class, 'revision_of_id');
    }

    public function revisions()
    {
        return $this->hasMany(self::class, 'revision_of_id');
    }

}
