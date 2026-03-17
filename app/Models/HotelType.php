<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelType extends Model
{
    protected $fillable = [
        'hotels_id',
        'type',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotels_id');
    }
}



