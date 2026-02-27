<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationRoom extends Model
{
    protected $fillable = [
        'accommodation_id',
        'name',
        'room_type',
        'bed_type',
        'view_type',
        'max_occupancy',
        'room_size_sqm',
        'contract_rate',
        'publish_rate',
        'currency',
        'meal_plan',
        'amenities',
        'benefits',
        'is_refundable',
        'quantity_available',
        'cancellation_policy',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'max_occupancy' => 'integer',
        'room_size_sqm' => 'decimal:2',
        'contract_rate' => 'decimal:2',
        'publish_rate' => 'decimal:2',
        'is_refundable' => 'boolean',
        'quantity_available' => 'integer',
        'is_active' => 'boolean',
    ];

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }
}

