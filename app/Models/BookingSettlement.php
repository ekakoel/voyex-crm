<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;

class BookingSettlement extends Model
{
    use HasAudit;

    public const STATUS_OPTIONS = [
        'pending_review',
        'outstanding_balance',
        'pending_payment',
        'pending_adjustment',
        'overpaid',
        'refund_required',
        'deposit_recorded',
        'settled',
        'closed_blocked',
    ];

    protected $fillable = [
        'booking_id',
        'settlement_number',
        'status',
        'service_completed_check',
        'invoice_check',
        'payment_check',
        'adjustment_check',
        'overpayment_check',
        'total_invoice_amount',
        'total_paid_amount',
        'outstanding_amount',
        'overpaid_amount',
        'settlement_notes',
        'reviewed_by',
        'reviewed_at',
        'finalized_by',
        'finalized_at',
        'metadata',
    ];

    protected $casts = [
        'service_completed_check' => 'boolean',
        'invoice_check' => 'boolean',
        'payment_check' => 'boolean',
        'adjustment_check' => 'boolean',
        'overpayment_check' => 'boolean',
        'total_invoice_amount' => 'decimal:2',
        'total_paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'overpaid_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'finalized_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isSettled(): bool
    {
        return (string) $this->status === 'settled';
    }

    public function isBlocked(): bool
    {
        return in_array((string) $this->status, ['closed_blocked', 'outstanding_balance', 'pending_payment', 'pending_adjustment', 'overpaid', 'refund_required'], true);
    }

    public function canReview(): bool
    {
        return ! $this->booking?->isFinal();
    }

    public function canFinalize(): bool
    {
        return $this->isSettled() && ! $this->booking?->isFinal();
    }

    public function hasOutstanding(): bool
    {
        return (float) ($this->outstanding_amount ?? 0) > 0;
    }

    public function hasOverpayment(): bool
    {
        return (float) ($this->overpaid_amount ?? 0) > 0;
    }
}
