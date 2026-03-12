<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, HasAudit;
    protected $fillable = [
        'name',
        'company_name',
        'code',
        'email',
        'phone',
        'address',
        'country',
        'customer_type',
        'created_by',
    ];
}
