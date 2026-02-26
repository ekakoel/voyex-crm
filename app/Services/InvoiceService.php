<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;

class InvoiceService
{
    public function generateForBooking(Booking $booking): ?Invoice
    {
        $booking->loadMissing(['quotation']);

        if ($booking->status !== 'completed' || ! $booking->quotation) {
            return null;
        }

        $quotation = $booking->quotation;
        if ($quotation->approval_status !== 'approved' || ! $quotation->approved_by) {
            return null;
        }

        $approver = User::query()->find($quotation->approved_by);
        if (! $approver || ! $approver->hasRole('Director')) {
            return null;
        }

        return Invoice::query()->firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'total_amount' => (float) ($quotation->final_amount ?? 0),
                'status' => 'issued',
                'generated_by' => auth()->id() ?: $approver->id,
            ]
        );
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
