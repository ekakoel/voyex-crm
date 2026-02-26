<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\QuotationItem;
use App\Models\QuotationTemplate;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_number',
        'inquiry_id',
        'status',
        'validity_date',
        'template_id',
        'sub_total',
        'discount_type',
        'discount_value',
        'final_amount',
        'approval_status',
        'approved_by',
        'approved_at',
    ];
    
    protected $casts = [
        'validity_date' => 'date',
        'sub_total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];


    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function booking()
    {
        return $this->hasOne(Booking::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function template()
    {
        return $this->belongsTo(QuotationTemplate::class, 'template_id');
    }
}
