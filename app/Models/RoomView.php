<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomView extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function rooms()
    {
        return $this->hasMany(HotelRoom::class, 'room_view_id');
    }
}



