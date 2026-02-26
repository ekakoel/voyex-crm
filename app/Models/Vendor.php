<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'location',
        'google_maps_url',
        'latitude',
        'longitude',
        'city',
        'province',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
