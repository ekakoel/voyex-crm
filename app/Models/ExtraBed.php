<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraBed extends Model
{
    protected $fillable = [
        'hotels_id',
        'name',
        'type',
        'max_age',
        'min_age',
        'description',
        'contract_rate',
        'markup',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotels_id');
    }
}



