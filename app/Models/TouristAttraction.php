<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TouristAttraction extends Model
{
    protected $fillable = [
        'name',
        'ideal_visit_minutes',
        'entrance_fee_per_pax',
        'other_fee_per_pax',
        'other_fee_label',
        'currency',
        'location',
        'city',
        'province',
        'country',
        'timezone',
        'address',
        'destination_id',
        'google_maps_url',
        'latitude',
        'longitude',
        'description',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ideal_visit_minutes' => 'integer',
        'entrance_fee_per_pax' => 'decimal:2',
        'other_fee_per_pax' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
        'gallery_images' => 'array',
    ];

    public function itineraries()
    {
        return $this->belongsToMany(Itinerary::class)->withTimestamps();
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}
