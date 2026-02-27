<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TouristAttraction extends Model
{
    protected $fillable = [
        'name',
        'ideal_visit_minutes',
        'location',
        'city',
        'province',
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
        'latitude' => 'float',
        'longitude' => 'float',
        'gallery_images' => 'array',
    ];

    public function itineraries()
    {
        return $this->belongsToMany(Itinerary::class)->withTimestamps();
    }
}
