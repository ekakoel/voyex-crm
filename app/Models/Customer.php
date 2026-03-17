<?php

namespace App\Models;

use App\Models\ActivityLog;
use App\Models\Concerns\HasAudit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use SoftDeletes;
    use HasFactory, HasAudit, LogsActivity;
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

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}






