<?php

namespace Tests\Feature\Modules;

use App\Models\Booking;
use App\Models\Invoice;

class InvoicesSmokeTest extends ModuleSmokeTestCase
{
    public function test_invoices_get_smoke(): void
    {
        $customer = $this->createCustomer();
        $inquiry = $this->createInquiry($customer);
        $quotation = $this->createQuotation($inquiry);

        $booking = Booking::query()->create([
            'booking_number' => 'BK-SMK-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'quotation_id' => $quotation->id,
            'travel_date' => now()->subDay()->format('Y-m-d'),
            'status' => 'completed',
            'notes' => 'Smoke booking for invoice',
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-SMK-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'booking_id' => $booking->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 100000,
            'status' => 'issued',
            'generated_by' => auth()->id(),
        ]);

        $this->get(route('invoices.index'))->assertOk();
        $this->get(route('invoices.show', $invoice))->assertOk();
    }
}

