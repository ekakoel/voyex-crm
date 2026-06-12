<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingItemVoucher;
use App\Models\Invoice;
use Illuminate\Support\Arr;

class InvoiceService
{
    public function computeAmounts(
        float $subtotal,
        float $discountAmount = 0,
        float $taxAmount = 0,
        float $paidAmount = 0
    ): array {
        $normalizedSubtotal = max(0, $subtotal);
        $normalizedDiscount = max(0, $discountAmount);
        $normalizedTax = max(0, $taxAmount);
        $normalizedPaid = max(0, $paidAmount);
        $total = max(0, $normalizedSubtotal - $normalizedDiscount + $normalizedTax);
        $balance = max($total - $normalizedPaid, 0);

        return [
            'subtotal' => $normalizedSubtotal,
            'discount_amount' => $normalizedDiscount,
            'tax_amount' => $normalizedTax,
            'total_amount' => $total,
            'paid_amount' => $normalizedPaid,
            'balance_amount' => $balance,
        ];
    }

    public function generateForBooking(Booking $booking): ?Invoice
    {
        // backward-compatible wrapper: booking flow now defaults to proforma generation
        return $this->generateProformaForBooking($booking);
    }

    public function generateProformaForBooking(Booking $booking): ?Invoice
    {
        $booking->loadMissing(['items.voucher', 'invoices']);
        $eligibleSubtotal = (float) $booking->items
            ->filter(function ($item) {
                $voucherStatus = (string) ($item->voucher->status ?? '');
                return in_array($voucherStatus, [
                    BookingItemVoucher::STATUS_GENERATED,
                    BookingItemVoucher::STATUS_SENT_TO_VENDOR,
                    BookingItemVoucher::STATUS_CONFIRMED_BY_VENDOR,
                    BookingItemVoucher::STATUS_REISSUED,
                    BookingItemVoucher::STATUS_USED,
                ], true);
            })
            ->sum(fn ($item) => (float) ($item->total ?? 0));

        if ($eligibleSubtotal <= 0) {
            return null;
        }

        $amounts = $this->computeAmounts($eligibleSubtotal, 0, 0, 0);
        $invoice = Invoice::query()->firstOrCreate(
            [
                'booking_id' => $booking->id,
                'invoice_type' => 'proforma',
            ],
            array_merge($amounts, [
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'status' => 'draft',
                'generated_by' => auth()->id(),
                'notes' => 'Auto-generated from voucher-confirmed booking items (proforma).',
            ])
        );

        if ($invoice->isEditable()) {
            $invoice->fill($amounts);
            $invoice->recalculateBalance();
            $invoice->save();
        }

        return $invoice->fresh();
    }

    public function generateFinalForBooking(Booking $booking): ?Invoice
    {
        $booking->loadMissing(['items', 'adjustments', 'invoices']);

        if ((string) ($booking->status ?? '') !== 'reconciliation') {
            throw new \RuntimeException('Final invoice can only be generated after reconciliation is finalized.');
        }

        $hasPaidFinal = $booking->invoices()
            ->where('invoice_type', 'final')
            ->whereIn('status', ['paid', 'overpaid'])
            ->exists();
        if ($hasPaidFinal) {
            throw new \RuntimeException('Final invoice already paid. Use credit note or adjustment invoice for further changes.');
        }

        $breakdown = $this->computeFinalAmountFromReconciliation($booking);
        $amounts = $this->computeAmounts(
            (float) ($breakdown['subtotal'] ?? 0),
            (float) ($breakdown['discount_amount'] ?? 0),
            0,
            0
        );

        $invoice = Invoice::query()->firstOrCreate(
            [
                'booking_id' => $booking->id,
                'invoice_type' => 'final',
            ],
            array_merge($amounts, [
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'status' => 'draft',
                'generated_by' => auth()->id(),
                'notes' => $this->buildFinalBreakdownNote($breakdown),
            ])
        );

        if ($invoice->isEditable()) {
            $invoice->fill(array_merge($amounts, [
                'notes' => $this->buildFinalBreakdownNote($breakdown),
            ]));
            $invoice->recalculateBalance();
            $invoice->save();
        }

        return $invoice->fresh();
    }

    public function computeFinalAmountFromReconciliation(Booking $booking): array
    {
        $booking->loadMissing(['items', 'adjustments']);

        $usedItemsTotal = (float) $booking->items
            ->filter(fn ($item) => in_array((string) ($item->status ?? ''), [BookingItem::STATUS_USED], true))
            ->sum(fn ($item) => (float) ($item->total ?? 0));

        $approvedAdjustments = $booking->adjustments
            ->filter(fn ($adj) => in_array((string) ($adj->status ?? ''), ['approved', 'applied'], true));

        $sumByType = function (array $types) use ($approvedAdjustments): float {
            return (float) $approvedAdjustments
                ->filter(function ($adj) use ($types) {
                    $type = (string) ($adj->type ?: $adj->adjustment_type);
                    return in_array($type, $types, true);
                })
                ->sum(fn ($adj) => (float) ($adj->calculated_amount ?? $adj->amount ?? 0));
        };

        $addedItemsAmount = $sumByType(['add_item', 'additional_service']);
        $cancellationFeeAmount = $sumByType(['cancellation_fee']);
        $extraChargeAmount = $sumByType(['extra_charge', 'price_change', 'service_upgrade']);
        $discountAmount = $sumByType(['discount', 'discount_adjustment']);
        $refundAmount = $sumByType(['refund']);

        $subtotal = max(0, $usedItemsTotal + $addedItemsAmount + $cancellationFeeAmount + $extraChargeAmount);
        $discountTotal = max(0, $discountAmount + $refundAmount);
        $total = max(0, $subtotal - $discountTotal);

        return [
            'used_items_total' => round($usedItemsTotal, 2),
            'added_items_amount' => round($addedItemsAmount, 2),
            'cancellation_fee_amount' => round($cancellationFeeAmount, 2),
            'extra_charge_amount' => round($extraChargeAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'refund_amount' => round($refundAmount, 2),
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total' => round($total, 2),
        ];
    }

    public function issue(Invoice $invoice): Invoice
    {
        if (! $invoice->isEditable()) {
            return $invoice;
        }
        $invoice->status = 'issued';
        $invoice->save();

        return $invoice->fresh();
    }

    public function markVoid(Invoice $invoice, ?string $note = null): Invoice
    {
        if ($invoice->isPaid()) {
            return $invoice;
        }
        $invoice->status = 'void';
        if ($note !== null) {
            $invoice->notes = trim($note) !== '' ? trim($note) : $invoice->notes;
        }
        $invoice->save();

        return $invoice->fresh();
    }

    public function cancel(Invoice $invoice, ?string $note = null): Invoice
    {
        if ($invoice->isPaid()) {
            return $invoice;
        }
        $invoice->status = 'cancelled';
        if ($note !== null) {
            $invoice->notes = trim($note) !== '' ? trim($note) : $invoice->notes;
        }
        $invoice->save();

        return $invoice->fresh();
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    private function buildFinalBreakdownNote(array $breakdown): string
    {
        return 'Final invoice reconciliation breakdown: '
            . 'used=' . (float) Arr::get($breakdown, 'used_items_total', 0)
            . ', add=' . (float) Arr::get($breakdown, 'added_items_amount', 0)
            . ', cancel_fee=' . (float) Arr::get($breakdown, 'cancellation_fee_amount', 0)
            . ', extra=' . (float) Arr::get($breakdown, 'extra_charge_amount', 0)
            . ', discount=' . (float) Arr::get($breakdown, 'discount_amount', 0)
            . ', refund=' . (float) Arr::get($breakdown, 'refund_amount', 0)
            . ', total=' . (float) Arr::get($breakdown, 'total', 0);
    }
}
