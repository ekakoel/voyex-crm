<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasAudit;

    protected $fillable = [
        'booking_number',
        'quotation_id',
        'travel_date',
        'status',
        'notes',
    ];
    protected $casts = [
        'travel_date' => 'date',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
