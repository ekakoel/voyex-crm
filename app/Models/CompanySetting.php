<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'legal_name',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'website',
        'address',
        'city',
        'country',
        'timezone',
        'currency',
        'usd_rate',
        'footer_note',
        'favicon_path',
        'logo_path',
    ];
}
