<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasAudit;

    public const TYPE_OPTIONS = [
        'proforma',
        'final',
        'adjustment',
        'credit_note',
        'down_payment',
        'balance_payment',
        'full_payment',
        'additional_charge',
        'cancellation_fee',
        'refund',
    ];

    public const STATUS_OPTIONS = [
        'draft',
        'issued',
        'partially_paid',
        'paid',
        // keep legacy status for backward compatibility with existing payment flow
        'overpaid',
        'revised',
        'void',
        'cancelled',
    ];

    public const FINAL_STATUS = 'paid';
    protected $fillable = [
        'invoice_number',
        'booking_id',
        'invoice_type',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'notes',
        'generated_by',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function confirmedPayments()
    {
        return $this->hasMany(Payment::class)->where('status', 'confirmed');
    }

    public function adjustments()
    {
        return $this->hasMany(BookingAdjustment::class);
    }

    public function isFinal(): bool
    {
        return $this->status === self::FINAL_STATUS;
    }

    public function isDraft(): bool
    {
        return (string) $this->status === 'draft';
    }

    public function isIssued(): bool
    {
        return in_array((string) $this->status, ['issued', 'partially_paid'], true);
    }

    public function isPaid(): bool
    {
        return in_array((string) $this->status, ['paid', 'overpaid'], true);
    }

    public function isEditable(): bool
    {
        return ! in_array((string) $this->status, ['paid', 'overpaid', 'void', 'cancelled'], true);
    }

    public function recalculateBalance(): void
    {
        $total = (float) ($this->total_amount ?? 0);
        $paid = (float) ($this->paid_amount ?? 0);
        $this->balance_amount = max($total - $paid, 0);

        if ($paid <= 0) {
            $this->status = $this->isDraft() ? 'draft' : 'issued';
        } elseif ($paid < $total) {
            $this->status = 'partially_paid';
        } elseif ($paid === $total) {
            $this->status = 'paid';
            $this->paid_at = $this->paid_at ?: now();
        } else {
            $this->status = 'overpaid';
            $this->paid_at = $this->paid_at ?: now();
        }
    }

    public function canReceivePayment(string $paymentType = 'full_payment'): bool
    {
        $status = (string) ($this->status ?? '');
        if (in_array($status, ['void', 'cancelled', 'draft'], true)) {
            return false;
        }

        if (in_array($status, ['paid', 'overpaid'], true)) {
            return $paymentType === 'additional_payment';
        }

        return in_array($status, ['issued', 'partially_paid'], true);
    }
}


