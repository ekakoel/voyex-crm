<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportUnit extends Model
{
    use SoftDeletes;

    protected $table = 'transports';

    protected $fillable = [
        'code',
        'name',
        'transport_type',
        'vendor_id',
        'description',
        'inclusions',
        'exclusions',
        'cancellation_policy',
        'brand_model',
        'seat_capacity',
        'luggage_capacity',
        'contract_rate',
        'publish_rate',
        'overtime_rate',
        'fuel_type',
        'transmission',
        'air_conditioned',
        'with_driver',
        'images',
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
        return $this->hasOne(Transport::class, 'id', 'id');
    }
}



