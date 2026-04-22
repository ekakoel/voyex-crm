<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Models\IslandTransfer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Destination extends Model
{
    use HasAudit, SoftDeletes;
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

    public function hotels()
    {
        return $this->hasMany(Hotel::class);
    }

    public function touristAttractions()
    {
        return $this->hasMany(TouristAttraction::class);
    }

    public function airports()
    {
        return $this->hasMany(Airport::class);
    }

    public function islandTransfers()
    {
        return $this->hasManyThrough(
            IslandTransfer::class,
            Vendor::class,
            'destination_id',
            'vendor_id',
            'id',
            'id'
        );
    }

}





