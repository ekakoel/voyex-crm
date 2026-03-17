<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
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
        'agent_price',
        'currency',
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
        'contract_price' => 'decimal:2',
        'agent_price' => 'decimal:2',
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






