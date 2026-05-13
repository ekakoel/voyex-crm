<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceItemValidation extends Model
{
    protected $fillable = [
        'quotation_id',
        'quotation_item_id',
        'serviceable_type',
        'serviceable_id',
        'validator_id',
        'action',
        'is_validated',
        'validation_notes',
        'old_contract_rate',
        'new_contract_rate',
        'old_markup_type',
        'new_markup_type',
        'old_markup',
        'new_markup',
        'old_qty',
        'new_qty',
        'source_rate_snapshot',
    ];

    protected $casts = [
        'is_validated' => 'boolean',
        'old_contract_rate' => 'decimal:2',
        'new_contract_rate' => 'decimal:2',
        'old_markup' => 'decimal:2',
        'new_markup' => 'decimal:2',
        'source_rate_snapshot' => 'array',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function quotationItem()
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }
}

