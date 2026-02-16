<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
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
}
