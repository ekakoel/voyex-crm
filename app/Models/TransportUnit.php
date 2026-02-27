<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportUnit extends Model
{
    protected $fillable = [
        'transport_id',
        'name',
        'vehicle_type',
        'brand_model',
        'seat_capacity',
        'luggage_capacity',
        'contract_rate',
        'publish_rate',
        'overtime_rate',
        'currency',
        'fuel_type',
        'transmission',
        'air_conditioned',
        'with_driver',
        'images',
        'benefits',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'seat_capacity' => 'integer',
        'luggage_capacity' => 'integer',
        'contract_rate' => 'decimal:2',
        'publish_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'air_conditioned' => 'boolean',
        'with_driver' => 'boolean',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    public function transport()
    {
        return $this->belongsTo(Transport::class);
    }
}
