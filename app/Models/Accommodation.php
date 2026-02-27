<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accommodation extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'star_rating',
        'location',
        'city',
        'province',
        'address',
        'latitude',
        'longitude',
        'check_in_time',
        'check_out_time',
        'contact_name',
        'contact_phone',
        'contact_email',
        'website',
        'main_facilities',
        'description',
        'cancellation_policy',
        'notes',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'star_rating' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'gallery_images' => 'array',
        'is_active' => 'boolean',
    ];

    public function rooms()
    {
        return $this->hasMany(AccommodationRoom::class);
    }
}

