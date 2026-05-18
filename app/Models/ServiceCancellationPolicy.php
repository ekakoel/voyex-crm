<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCancellationPolicy extends Model
{
    protected $fillable = [
        'serviceable_type',
        'serviceable_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function serviceable()
    {
        return $this->morphTo();
    }

    public function rules()
    {
        return $this->hasMany(ServiceCancellationPolicyRule::class, 'policy_id')->orderBy('sort_order');
    }
}

