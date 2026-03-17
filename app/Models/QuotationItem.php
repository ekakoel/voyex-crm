<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'serviceable_id',
        'serviceable_type',
        'description',
        'qty',
        'unit_price',
        'discount_type',
        'discount',
        'day_number',
        'serviceable_meta',
        'itinerary_item_type',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_type' => 'string',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'day_number' => 'integer',
        'serviceable_meta' => 'array',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function serviceable()
    {
        return $this->morphTo();
    }
}



