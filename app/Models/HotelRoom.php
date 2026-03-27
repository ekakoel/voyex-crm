<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelRoom extends Model
{
    protected $fillable = [
        'hotels_id',
        'room_view_id',
        'cover',
        'rooms',
        'capacity_adult',
        'capacity_child',
        'view',
        'beds',
        'size',
        'amenities',
        'amenities_traditional',
        'amenities_simplified',
        'additional_info',
        'additional_info_traditional',
        'additional_info_simplified',
        'include',
        'include_traditional',
        'include_simplified',
        'status',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotels_id');
    }

    public function prices()
    {
        return $this->hasMany(HotelPrice::class, 'rooms_id');
    }

    public function facilities()
    {
        return $this->hasOne(RoomFacility::class, 'rooms_id');
    }

    public function roomView()
    {
        return $this->belongsTo(RoomView::class, 'room_view_id');
    }
}



