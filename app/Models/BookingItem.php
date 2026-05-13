<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    protected $fillable = [
        'booking_id',
        'quotation_item_id',
        'description',
        'qty',
        'unit_price',
        'total',
        'serviceable_type',
        'serviceable_id',
        'day_number',
        'serviceable_meta',
        'notes',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'day_number' => 'integer',
        'serviceable_meta' => 'array',
    ];

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
