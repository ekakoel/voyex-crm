<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class BookingAdjustment extends Model
{
    use HasAudit, LogsActivity;

    public const TYPE_OPTIONS = [
        'add_item',
        'cancel_item',
        'replace_item',
        'price_change',
        'cancellation_fee',
        'discount',
        'refund',
        'extra_charge',
        'additional_service',
        'service_upgrade',
        'service_downgrade',
        'discount_adjustment',
        'pax_change',
        'date_change',
        'vendor_change',
        'manual_adjustment',
    ];

    public const STATUS_OPTIONS = [
        'draft',
        'pending_approval',
        'approved',
        'rejected',
        'applied',
        'void',
        'cancelled',
    ];

    public const IMPACT_OPTIONS = [
        'charge',
        'credit',
        'refund',
        'non_financial',
        'fixed',
        'percentage',
    ];

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'quotation_id',
        'invoice_id',
        'payment_id',
        'type',
        'adjustment_number',
        'adjustment_type',
        'amount_type',
        'status',
        'title',
        'description',
        'reason',
        'amount',
        'percentage',
        'calculated_amount',
        'currency_code',
        'impact_type',
        'created_by',
        'requested_by',
        'requested_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'applied_by',
        'applied_at',
        'generated_invoice_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'applied_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function generatedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'generated_invoice_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function applier()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function isDraft(): bool { return (string) $this->status === 'draft'; }
    public function isPendingApproval(): bool { return (string) $this->status === 'pending_approval'; }
    public function isApproved(): bool { return (string) $this->status === 'approved'; }
    public function isRejected(): bool { return (string) $this->status === 'rejected'; }
    public function isApplied(): bool { return (string) $this->status === 'applied'; }

    public function isFinancial(): bool
    {
        return in_array((string) $this->impact_type, ['charge', 'credit', 'refund'], true);
    }

    public function canSubmit(): bool { return $this->isDraft(); }
    public function canApprove(): bool { return $this->isPendingApproval(); }
    public function canReject(): bool { return $this->isPendingApproval(); }
    public function canApply(): bool { return $this->isApproved(); }
    public function canCancel(): bool { return in_array((string) $this->status, ['draft', 'pending_approval', 'approved'], true); }
}
