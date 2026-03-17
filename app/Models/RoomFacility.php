<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomFacility extends Model
{
    protected $fillable = [
        'rooms_id',
        'wifi',
        'single_bed',
        'double_bed',
        'extra_bed',
        'air_conditioning',
        'pool',
        'tv_channel',
        'water_heater',
        'bathtub',
    ];

    public function room()
    {
        return $this->belongsTo(HotelRoom::class, 'rooms_id');
    }
}



