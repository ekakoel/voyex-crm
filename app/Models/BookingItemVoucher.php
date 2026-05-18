<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItemVoucher extends Model
{
    protected $fillable = [
        'booking_item_id',
        'voucher_number',
        'status',
        'tour_name',
        'service_date',
        'service_time',
        'vendor_contact_name',
        'vendor_contact_phone',
        'vendor_contact_email',
        'pickup_location',
        'confirmation_code',
        'source_hash',
        'issued_at',
        'used_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'issued_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }
}
