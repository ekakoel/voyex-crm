<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItemBookingLog extends Model
{
    protected $fillable = [
        'booking_item_id',
        'booked_at',
        'vendor_provider_item_name',
        'contact_channel',
        'contact_value',
        'contacted_person_name',
        'service_date',
        'confirmation_number',
        'pax_adult',
        'pax_child',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'service_date' => 'date',
        'pax_adult' => 'integer',
        'pax_child' => 'integer',
    ];

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
