<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'booking_id',
        'quotation_item_id',
        'description',
        'qty',
        'unit_price',
        'total',
        'status',
        'cancellation_fee',
        'cancellation_fee_calculated',
        'cancellation_fee_overridden',
        'cancelled_at',
        'cancellation_policy_snapshot',
        'serviceable_type',
        'serviceable_id',
        'day_number',
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
        'cancellation_policy_snapshot' => 'array',
        'day_number' => 'integer',
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
}
