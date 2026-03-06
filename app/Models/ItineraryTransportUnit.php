<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryTransportUnit extends Model
{
    protected $fillable = [
        'itinerary_id',
        'transport_unit_id',
        'day_number',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function transportUnit()
    {
        return $this->belongsTo(TransportUnit::class);
    }
}
