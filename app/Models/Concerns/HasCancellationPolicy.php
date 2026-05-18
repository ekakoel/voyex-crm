<?php

namespace App\Models\Concerns;

use App\Models\ServiceCancellationPolicy;

trait HasCancellationPolicy
{
    public function cancellationPolicy()
    {
        return $this->morphOne(ServiceCancellationPolicy::class, 'serviceable')
            ->where('is_active', true)
            ->latest('id');
    }
}

