<?php

namespace App\Models;

use App\Models\ActivityLog;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'accommodation_id',
        'name',
        'code',
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
        'benefits',
        'optional_rate',
        'cancellation_policy',
        'cancellation_policy_traditional',
        'cancellation_policy_simplified',
    ];

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
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

    public function promos()
    {
        return $this->hasMany(HotelPromo::class, 'hotels_id');
    }

    public function packages()
    {
        return $this->hasMany(HotelPackage::class, 'hotels_id');
    }

    public function extraBeds()
    {
        return $this->hasMany(ExtraBed::class, 'hotels_id');
    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}



