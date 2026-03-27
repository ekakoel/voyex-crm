<?php

namespace App\Models;

use App\Models\ActivityLog;
use App\Models\Concerns\HasAudit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use HasAudit, SoftDeletes, LogsActivity;

    protected $fillable = [
        'destination_id',
        'name',
        'code',
        'city',
        'province',
        'country',
        'region',
        'address',
        'airport_duration',
        'airport_distance',
        'contact_person',
        'phone',
        'description',
        'description_traditional',
        'description_simplified',
        'facility',
        'facility_traditional',
        'facility_simplified',
        'additional_info',
        'additional_info_traditional',
        'additional_info_simplified',
        'wedding_info',
        'entrance_fee',
        'wedding_cancellation_policy',
        'status',
        'cover',
        'author_id',
        'web',
        'min_stay',
        'max_stay',
        'check_in_time',
        'check_out_time',
        'map',
        'latitude',
        'longitude',
        'cancellation_policy',
        'cancellation_policy_traditional',
        'cancellation_policy_simplified',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function rooms()
    {
        return $this->hasMany(HotelRoom::class, 'hotels_id');
    }

    public function images()
    {
        return $this->hasMany(HotelImage::class, 'hotels_id');
    }

    public function types()
    {
        return $this->hasMany(HotelType::class, 'hotels_id');
    }

    public function prices()
    {
        return $this->hasMany(HotelPrice::class, 'hotels_id');
    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}



