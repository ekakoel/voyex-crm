<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodBeverage extends Model
{
    protected $fillable = [
        'vendor_id',
        'name',
        'service_type',
        'duration_minutes',
        'contract_price',
        'agent_price',
        'currency',
        'meal_period',
        'menu_highlights',
        'notes',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'contract_price' => 'decimal:2',
        'agent_price' => 'decimal:2',
        'gallery_images' => 'array',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
