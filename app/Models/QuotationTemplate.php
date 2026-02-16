<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationTemplate extends Model
{
    protected $fillable = [
        'name',
        'body_html',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
