<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    protected $fillable = [
        'code',
        'name',
        'slug',
        'google_maps_url',
        'location',
        'city',
        'province',
        'country',
        'timezone',
        'address',
        'latitude',
        'longitude',
        'description',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }

    public function accommodations()
    {
        return $this->hasMany(Accommodation::class);
    }

    public function touristAttractions()
    {
        return $this->hasMany(TouristAttraction::class);
    }

    public function airports()
    {
        return $this->hasMany(Airport::class);
    }

    public function transports()
    {
        return $this->hasMany(Transport::class);
    }
}
