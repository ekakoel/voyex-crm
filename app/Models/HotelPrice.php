<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelPrice extends Model
{
    protected $fillable = [
        'hotels_id',
        'rooms_id',
        'start_date',
        'end_date',
        'markup_type',
        'markup',
        'kick_back',
        'contract_rate',
        'publish_rate',
        'author',
    ];

    protected $casts = [
        'contract_rate' => 'decimal:0',
        'markup_type' => 'string',
        'markup' => 'decimal:0',
        'publish_rate' => 'decimal:0',
        'kick_back' => 'decimal:0',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotels_id');
    }

    public function room()
    {
        return $this->belongsTo(HotelRoom::class, 'rooms_id');
    }
}



