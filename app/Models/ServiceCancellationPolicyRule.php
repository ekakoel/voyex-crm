<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCancellationPolicyRule extends Model
{
    protected $fillable = [
        'policy_id',
        'min_days_before',
        'max_days_before',
        'fee_type',
        'fee_value',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'min_days_before' => 'integer',
        'max_days_before' => 'integer',
        'fee_value' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function policy()
    {
        return $this->belongsTo(ServiceCancellationPolicy::class, 'policy_id');
    }
}

