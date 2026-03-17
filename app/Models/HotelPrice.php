<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelPrice extends Model
{
    protected $fillable = [
        'hotels_id',
        'rooms_id',
        'start_date',
        'end_date',
        'markup',
        'kick_back',
        'contract_rate',
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



