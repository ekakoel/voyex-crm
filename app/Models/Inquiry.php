<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Inquiry extends Model
{
    use HasFactory;
    protected $fillable = [
        'inquiry_number',
        'customer_id',
        'source',
        'status',
        'priority',
        'deadline',
        'assigned_to',
        'notes',
        'reminder_enabled',
    ];
    protected $casts = [
        'deadline' => 'date',
    ];

    public function quotation()
    {
        return $this->hasOne(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function followUps()
    {
        return $this->hasMany(InquiryFollowUp::class);
    }

    public function communications()
    {
        return $this->hasMany(InquiryCommunication::class);
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->inquiry_number = 'INQ-' . now()->format('Ymd') . '-' . rand(1000,9999);
        });
    }
}
