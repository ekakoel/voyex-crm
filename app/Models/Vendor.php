<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasAudit;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'location',
        'google_maps_url',
        'latitude',
        'longitude',
        'city',
        'province',
        'country',
        'timezone',
        'destination_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
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

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function foodBeverages()
    {
        return $this->hasMany(FoodBeverage::class);
    }


    public function transports()
    {
        return $this->hasMany(Transport::class);
    }

}



