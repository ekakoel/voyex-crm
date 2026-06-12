<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Models\Concerns\HasCancellationPolicy;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FoodBeverage extends Model
{
    use HasAudit, HasCancellationPolicy, LogsActivity, SoftDeletes;
    protected $fillable = [
        'vendor_id',
        'name',
        'service_type',
        'duration_minutes',
        'adult_contract_rate',
        'child_contract_rate',
        'adult_markup_type',
        'adult_markup',
        'child_markup_type',
        'child_markup',
        'adult_publish_rate',
        'child_publish_rate',
        'contract_rate',
        'markup_type',
        'markup',
        'publish_rate',
        'meal_period',
        'menu_highlights',
        'cancellation_policy',
        'notes',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'adult_contract_rate' => 'decimal:0',
        'child_contract_rate' => 'decimal:0',
        'adult_markup_type' => 'string',
        'adult_markup' => 'decimal:0',
        'child_markup_type' => 'string',
        'child_markup' => 'decimal:0',
        'adult_publish_rate' => 'decimal:0',
        'child_publish_rate' => 'decimal:0',
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



