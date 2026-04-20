<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ItineraryIslandTransfer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'itinerary_id',
        'island_transfer_id',
        'day_number',
        'pax',
        'start_time',
        'end_time',
        'travel_minutes_to_next',
        'visit_order',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function islandTransfer()
    {
        return $this->belongsTo(IslandTransfer::class);
    }
}

