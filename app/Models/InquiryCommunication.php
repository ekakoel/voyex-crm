<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryCommunication extends Model
{
    protected $fillable = [
        'inquiry_id',
        'created_by',
        'channel',
        'summary',
        'contact_at',
    ];

    protected $casts = [
        'contact_at' => 'datetime',
    ];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
