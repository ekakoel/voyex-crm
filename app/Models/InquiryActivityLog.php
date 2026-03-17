<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryActivityLog extends Model
{
    protected $fillable = [
        'inquiry_id',
        'user_id',
        'action',
        'note',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}



