<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelPromo extends Model
{
    protected $fillable = [
        'hotels_id',
        'rooms_id',
        'promotion_type',
        'quotes',
        'name',
        'book_periode_start',
        'book_periode_end',
        'periode_start',
        'periode_end',
        'minimum_stay',
        'contract_rate',
        'markup',
        'booking_code',
        'benefits',
        'benefits_traditional',
        'benefits_simplified',
        'email_status',
        'send_to_specific_email',
        'specific_email',
        'status',
        'author',
        'include',
        'include_traditional',
        'include_simplified',
        'additional_info',
        'additional_info_traditional',
        'additional_info_simplified',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotels_id');
    }

    public function room()
    {
        return $this->belongsTo(HotelRoom::class, 'rooms_id');
    }
}



