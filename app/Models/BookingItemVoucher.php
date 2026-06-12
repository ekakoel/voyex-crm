<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItemVoucher extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_SENT_TO_VENDOR = 'sent_to_vendor';
    public const STATUS_CONFIRMED_BY_VENDOR = 'confirmed_by_vendor';
    public const STATUS_REISSUED = 'reissued';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_USED = 'used';

    protected $fillable = [
        'booking_item_id',
        'voucher_number',
        'revision_number',
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
        'revision_number' => 'integer',
        'service_date' => 'date',
        'issued_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }
}
