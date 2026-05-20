<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;

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
        $booking->loadMissing(['quotation', 'invoices']);

        if (! $booking->quotation) {
            return null;
        }

        $quotation = $booking->quotation;
        if (! in_array((string) ($quotation->status ?? ''), ['accepted', 'converted'], true)) {
            return null;
        }

        // In Phase 8A we create a stable base billing document and keep it editable as draft.
        $amounts = $this->computeAmounts((float) ($quotation->final_amount ?? 0), 0, 0, 0);
        $invoice = Invoice::query()->firstOrCreate(
            [
                'booking_id' => $booking->id,
                'invoice_type' => 'full_payment',
            ],
            array_merge($amounts, [
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'status' => 'draft',
                'generated_by' => auth()->id(),
            ])
        );

        if ($invoice->isEditable()) {
            $invoice->fill(array_merge($amounts, [
                'invoice_date' => $invoice->invoice_date ?: now()->toDateString(),
                'due_date' => $invoice->due_date ?: now()->addDays(7)->toDateString(),
            ]));
            $invoice->recalculateBalance();
            $invoice->save();
        }

        return $invoice->fresh();
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
}
