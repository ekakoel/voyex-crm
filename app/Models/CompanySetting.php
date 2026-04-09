<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'tagline',
        'legal_name',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'website',
        'address',
        'city',
        'province',
        'country',
        'destination_id',
        'google_maps_url',
        'latitude',
        'longitude',
        'timezone',
        'footer_note',
        'favicon_path',
        'logo_path',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}



