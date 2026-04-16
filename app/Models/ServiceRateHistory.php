<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRateHistory extends Model
{
    protected $fillable = [
        'quotation_id',
        'quotation_item_id',
        'serviceable_type',
        'serviceable_id',
        'contract_rate',
        'markup_type',
        'markup',
        'publish_rate',
        'start_date',
        'end_date',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'contract_rate' => 'decimal:2',
        'markup' => 'decimal:2',
        'publish_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function quotationItem()
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}