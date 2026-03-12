<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationComment extends Model
{
    protected $fillable = [
        'quotation_id',
        'user_id',
        'body',
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
