<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasAudit, LogsActivity;

    public const STATUS_OPTIONS = [
        'pending',
        'waiting_confirmation',
        'confirmed',
        'rejected',
        'cancelled',
        'refunded',
        'allocated_as_deposit',
    ];

    public const TYPE_OPTIONS = [
        'down_payment',
        'balance_payment',
        'full_payment',
        'additional_payment',
        'refund',
        'deposit',
    ];

    protected $fillable = [
        'invoice_id',
        'payment_number',
        'payment_type',
        'payment_date',
        'amount',
        'currency_code',
        'method',
        'reference_number',
        'proof_path',
        'status',
        'notes',
        'confirmed_by',
        'confirmed_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function adjustments()
    {
        return $this->hasMany(BookingAdjustment::class);
    }

    public function isPending(): bool
    {
        return in_array((string) $this->status, ['pending', 'waiting_confirmation'], true);
    }

    public function isConfirmed(): bool
    {
        return (string) $this->status === 'confirmed';
    }

    public function isRejected(): bool
    {
        return (string) $this->status === 'rejected';
    }

    public function isRefunded(): bool
    {
        return (string) $this->status === 'refunded';
    }

    public function isCancellable(): bool
    {
        return in_array((string) $this->status, ['pending', 'waiting_confirmation'], true);
    }

    public function canBeConfirmed(): bool
    {
        return in_array((string) $this->status, ['pending', 'waiting_confirmation'], true);
    }

    public function canBeRejected(): bool
    {
        return in_array((string) $this->status, ['pending', 'waiting_confirmation'], true);
    }
}
