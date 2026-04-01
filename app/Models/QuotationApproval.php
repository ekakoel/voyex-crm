<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationApproval extends Model
{
    protected $fillable = [
        'quotation_id',
        'user_id',
        'approval_role',
        'note',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

