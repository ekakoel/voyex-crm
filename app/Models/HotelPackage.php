<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hotels_id',
        'rooms_id',
        'name',
        'duration',
        'stay_period_start',
        'stay_period_end',
        'contract_rate',
        'markup',
        'booking_code',
        'benefits',
        'benefits_traditional',
        'benefits_simplified',
        'include',
        'include_traditional',
        'include_simplified',
        'additional_info',
        'additional_info_traditional',
        'additional_info_simplified',
        'status',
        'author',
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



