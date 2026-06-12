<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BookingItem extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_USED = 'used';
    public const STATUS_NOT_USED = 'not_used';
    public const STATUS_CANCELLED = 'cancelled';
    public const VENDOR_CONFIRMATION_PENDING = 'pending_vendor';
    public const VENDOR_CONFIRMATION_CONFIRMED = 'confirmed_by_vendor';
    public const VENDOR_CONFIRMATION_NOT_AVAILABLE = 'not_available';
    public const VENDOR_CONFIRMATION_REPLACED = 'replaced';
    public const VENDOR_CONFIRMATION_CANCELLED = 'cancelled';
    public const DISPATCH_PENDING = 'pending';
    public const DISPATCH_READY = 'ready';
    public const DISPATCH_COMPLETED = 'completed';
    public const DISPATCH_ISSUE = 'issue_reported';

    protected $fillable = [
        'booking_id',
        'quotation_item_id',
        'description',
        'qty',
        'unit_price',
        'total',
        'status',
        'vendor_confirmation_status',
        'vendor_confirmed_at',
        'vendor_confirmed_by',
        'vendor_unavailable_reason',
        'assigned_driver_name',
        'assigned_driver_phone',
        'assigned_guide_name',
        'assigned_guide_phone',
        'operation_notes',
        'dispatch_status',
        'issue_note',
        'cancellation_fee',
        'cancellation_fee_calculated',
        'cancellation_fee_overridden',
        'cancelled_at',
        'cancellation_policy_snapshot',
        'serviceable_type',
        'serviceable_id',
        'vendor_id',
        'day_number',
        'service_date',
        'sell_price',
        'contract_rate',
        'markup_type',
        'markup',
        'serviceable_meta',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'cancellation_fee_calculated' => 'decimal:2',
        'cancellation_fee_overridden' => 'boolean',
        'cancelled_at' => 'datetime',
        'vendor_confirmed_at' => 'datetime',
        'vendor_unavailable_reason' => 'string',
        'cancellation_policy_snapshot' => 'array',
        'day_number' => 'integer',
        'service_date' => 'date',
        'sell_price' => 'decimal:2',
        'contract_rate' => 'decimal:2',
        'markup' => 'decimal:2',
        'serviceable_meta' => 'array',
    ];

    public function isCancelled(): bool
    {
        return (string) $this->status === self::STATUS_CANCELLED;
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function quotationItem()
    {
        return $this->belongsTo(QuotationItem::class);
    }

    public function serviceable()
    {
        return $this->morphTo();
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function voucher()
    {
        return $this->hasOne(BookingItemVoucher::class);
    }

    public function bookingLogs()
    {
        return $this->hasMany(BookingItemBookingLog::class)->latest('booked_at');
    }

    public function latestBookingLog()
    {
        return $this->hasOne(BookingItemBookingLog::class)->latestOfMany('booked_at');
    }

    public function vendorConfirmer()
    {
        return $this->belongsTo(User::class, 'vendor_confirmed_by');
    }

    public function isVendorConfirmed(): bool
    {
        return (string) ($this->vendor_confirmation_status ?? '') === self::VENDOR_CONFIRMATION_CONFIRMED;
    }

    public function adjustments()
    {
        return $this->hasMany(BookingAdjustment::class);
    }
}
