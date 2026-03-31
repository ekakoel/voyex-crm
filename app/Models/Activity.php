<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasAudit;
    use SoftDeletes;
    protected $fillable = [
        'vendor_id',
        'name',
        'activity_type',
        'activity_type_id',
        'duration_minutes',
        'benefits',
        'descriptions',
        'contract_price',
        'adult_contract_rate',
        'child_contract_rate',
        'adult_markup_type',
        'adult_markup',
        'child_markup_type',
        'child_markup',
        'adult_publish_rate',
        'child_publish_rate',
        'capacity_min',
        'capacity_max',
        'includes',
        'excludes',
        'cancellation_policy',
        'notes',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'contract_price' => 'decimal:0',
        'adult_contract_rate' => 'decimal:0',
        'child_contract_rate' => 'decimal:0',
        'adult_markup_type' => 'string',
        'adult_markup' => 'decimal:0',
        'child_markup_type' => 'string',
        'child_markup' => 'decimal:0',
        'adult_publish_rate' => 'decimal:0',
        'child_publish_rate' => 'decimal:0',
        'capacity_min' => 'integer',
        'capacity_max' => 'integer',
        'gallery_images' => 'array',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }
}






