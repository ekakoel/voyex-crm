<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FoodBeverage extends Model
{
    use HasAudit, SoftDeletes;
    protected $fillable = [
        'vendor_id',
        'name',
        'service_type',
        'duration_minutes',
        'contract_rate',
        'markup_type',
        'markup',
        'publish_rate',
        'meal_period',
        'menu_highlights',
        'notes',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'contract_rate' => 'decimal:0',
        'markup_type' => 'string',
        'markup' => 'decimal:0',
        'publish_rate' => 'decimal:0',
        'gallery_images' => 'array',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}






