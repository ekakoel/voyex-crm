<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ItineraryTransportUnit extends Model
{
    use LogsActivity;

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



