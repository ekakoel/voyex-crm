<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCancellationPolicy extends Model
{
    public const FEE_TYPE_FREE = 'free';
    public const FEE_TYPE_FIXED = 'fixed';
    public const FEE_TYPE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'vendor_id',
        'serviceable_type',
        'serviceable_id',
        'name',
        'season_type',
        'start_date',
        'end_date',
        'cancel_before_hours',
        'fee_type',
        'fee_value',
        'description',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cancel_before_hours' => 'integer',
        'fee_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function serviceable()
    {
        return $this->morphTo();
    }

    public function rules()
    {
        return $this->hasMany(ServiceCancellationPolicyRule::class, 'policy_id')->orderBy('sort_order');
    }
}
