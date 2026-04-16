<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItemValidation extends Model
{
    protected $fillable = [
        'quotation_id',
        'quotation_item_id',
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
        'source_rate_type',
        'source_rate_id',
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

    public function item()
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }
}