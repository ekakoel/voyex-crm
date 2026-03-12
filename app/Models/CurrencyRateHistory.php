<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRateHistory extends Model
{
    protected $fillable = [
        'currency_id',
        'old_rate_to_idr',
        'new_rate_to_idr',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'old_rate_to_idr' => 'float',
        'new_rate_to_idr' => 'float',
        'changed_at' => 'datetime',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
