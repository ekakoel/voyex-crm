<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationFollowUp extends Model
{
    protected $fillable = [
        'quotation_id',
        'customer_id',
        'handled_by',
        'channel',
        'follow_up_type',
        'follow_up_note',
        'follow_up_at',
        'next_follow_up_at',
        'created_by',
    ];

    protected $casts = [
        'follow_up_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
