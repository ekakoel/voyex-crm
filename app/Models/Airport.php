<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Airport extends Model
{
    use HasAudit;
    use SoftDeletes;
    protected $fillable = [
        'code',
        'name',
        'location',
        'city',
        'province',
        'destination_id',
        'google_maps_url',
        'country',
        'timezone',
        'address',
        'latitude',
        'longitude',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}






