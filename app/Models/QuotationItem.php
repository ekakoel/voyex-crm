<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'serviceable_id',
        'serviceable_type',
        'description',
        'qty',
        'contract_rate',
        'markup_type',
        'markup',
        'unit_price',
        'discount_type',
        'discount',
        'day_number',
        'serviceable_meta',
        'itinerary_item_type',
        'is_validation_required',
        'is_validated',
        'validated_at',
        'validated_by',
        'validation_notes',
        'last_validated_contract_rate',
        'last_validated_markup_type',
        'last_validated_markup',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'contract_rate' => 'decimal:2',
        'markup_type' => 'string',
        'markup' => 'decimal:2',
        'discount_type' => 'string',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'day_number' => 'integer',
        'serviceable_meta' => 'array',
        'is_validation_required' => 'boolean',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'last_validated_contract_rate' => 'decimal:2',
        'last_validated_markup_type' => 'string',
        'last_validated_markup' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function serviceable()
    {
        return $this->morphTo();
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}

