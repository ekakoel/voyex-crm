<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $fillable = [
        'code',
        'name',
        'transport_type',
        'provider_name',
        'service_scope',
        'location',
        'city',
        'province',
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
        'is_active' => 'boolean',
    ];

    public function units()
    {
        return $this->hasMany(TransportUnit::class);
    }
}

