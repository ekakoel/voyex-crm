<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    protected $fillable = [
        'year',
        'month',
        'target_amount'
    ];
}
