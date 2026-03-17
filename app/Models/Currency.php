<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'rate_to_idr',
        'decimal_places',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'rate_to_idr' => 'float',
        'decimal_places' => 'int',
        'is_active' => 'bool',
        'is_default' => 'bool',
    ];

    public function rateHistories()
    {
        return $this->hasMany(CurrencyRateHistory::class);
    }
}



