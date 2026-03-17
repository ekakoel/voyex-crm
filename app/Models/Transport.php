<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transport extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'code',
        'name',
        'transport_type',
        'provider_name',
        'service_scope',
        'location',
        'city',
        'province',
        'country',
        'timezone',
        'address',
        'google_maps_url',
        'latitude',
        'longitude',
        'destination_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'website',
        'description',
        'inclusions',
        'exclusions',
        'cancellation_policy',
        'notes',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function units()
    {
        return $this->hasMany(TransportUnit::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}






