<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('PaymentService integration tests require non-sqlite DB because legacy migrations use MySQL MODIFY syntax.');
        }
    }

    public function test_create_pending_payment_does_not_change_invoice_amounts(): void
    {
        [$invoice, $actorId] = $this->makeInvoiceGraph(1000, 'issued');
        $service = app(PaymentService::class);

        $service->createPayment($invoice, $this->payload(250, 'pending'), null, $actorId);

        $invoice->refresh();
        $this->assertSame('issued', $invoice->status);
        $this->assertEquals(0.0, (float) $invoice->paid_amount);
        $this->assertEquals(1000.0, (float) $invoice->balance_amount);
    }

    public function test_confirm_partial_payment_sets_invoice_partially_paid(): void
    {
        [$invoice, $actorId] = $this->makeInvoiceGraph(1000, 'issued');
        $service = app(PaymentService::class);
        $payment = $service->createPayment($invoice, $this->payload(400, 'pending'), null, $actorId);

        $service->confirmPayment($payment, $actorId);

        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertEquals(400.0, (float) $invoice->paid_amount);
        $this->assertEquals(600.0, (float) $invoice->balance_amount);
    }

    public function test_confirm_full_payment_sets_invoice_paid(): void
    {
        [$invoice, $actorId] = $this->makeInvoiceGraph(1000, 'issued');
        $service = app(PaymentService::class);
        $payment = $service->createPayment($invoice, $this->payload(1000, 'pending'), null, $actorId);

        $service->confirmPayment($payment, $actorId);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(1000.0, (float) $invoice->paid_amount);
        $this->assertEquals(0.0, (float) $invoice->balance_amount);
    }

    public function test_confirm_overpayment_sets_invoice_overpaid(): void
    {
        [$invoice, $actorId] = $this->makeInvoiceGraph(1000, 'issued');
        $service = app(PaymentService::class);
        $payment = $service->createPayment($invoice, $this->payload(1200, 'pending', 'additional_payment'), null, $actorId);

        $service->confirmPayment($payment, $actorId);

        $invoice->refresh();
        $this->assertSame('overpaid', $invoice->status);
        $this->assertEquals(1200.0, (float) $invoice->paid_amount);
        $this->assertEquals(0.0, (float) $invoice->balance_amount);
    }

    public function test_reject_or_cancel_payment_does_not_change_invoice_amounts(): void
    {
        [$invoice, $actorId] = $this->makeInvoiceGraph(1000, 'issued');
        $service = app(PaymentService::class);
        $paymentRejected = $service->createPayment($invoice, $this->payload(300, 'pending'), null, $actorId);
        $paymentCancelled = $service->createPayment($invoice, $this->payload(350, 'pending'), null, $actorId);

        $service->rejectPayment($paymentRejected, $actorId, 'invalid transfer');
        $service->cancelPayment($paymentCancelled, $actorId, 'cancel request');

        $invoice->refresh();
        $this->assertSame('issued', $invoice->status);
        $this->assertEquals(0.0, (float) $invoice->paid_amount);
        $this->assertEquals(1000.0, (float) $invoice->balance_amount);
    }

    public function test_cannot_confirm_rejected_payment(): void
    {
        [$invoice, $actorId] = $this->makeInvoiceGraph(1000, 'issued');
        $service = app(PaymentService::class);

        $rejected = $service->createPayment($invoice, $this->payload(200, 'pending'), null, $actorId);
        $service->rejectPayment($rejected, $actorId, 'invalid');
        $this->assertConfirmThrows($service, $rejected->fresh(), $actorId);
    }

    public function test_cannot_confirm_cancelled_payment(): void
    {
        [$invoice, $actorId] = $this->makeInvoiceGraph(1000, 'issued');
        $service = app(PaymentService::class);

        $cancelled = $service->createPayment($invoice, $this->payload(220, 'pending'), null, $actorId);
        $service->cancelPayment($cancelled, $actorId, 'cancelled');
        $this->assertConfirmThrows($service, $cancelled->fresh(), $actorId);
    }

    public function test_cannot_confirm_confirmed_payment_twice(): void
    {
        [$invoice, $actorId] = $this->makeInvoiceGraph(1000, 'issued');
        $service = app(PaymentService::class);

        $confirmed = $service->createPayment($invoice, $this->payload(250, 'pending'), null, $actorId);
        $service->confirmPayment($confirmed, $actorId);
        $this->assertConfirmThrows($service, $confirmed->fresh(), $actorId);
    }

    public function test_cannot_create_payment_for_draft_void_or_cancelled_invoice(): void
    {
        $service = app(PaymentService::class);
        [, $actorId, $draftInvoice] = $this->makeInvoiceGraphWithStatus(1000, 'draft');
        [, $actorId, $voidInvoice] = $this->makeInvoiceGraphWithStatus(1000, 'void');
        [, , $cancelledInvoice] = $this->makeInvoiceGraphWithStatus(1000, 'cancelled');

        $this->assertCreateThrows($service, $draftInvoice, $actorId);
        $this->assertCreateThrows($service, $voidInvoice, $actorId);
        $this->assertCreateThrows($service, $cancelledInvoice, $actorId);
    }

    private function payload(float $amount, string $status = 'pending', string $type = 'full_payment'): array
    {
        return [
            'payment_type' => $type,
            'payment_date' => now()->toDateString(),
            'amount' => $amount,
            'currency_code' => 'IDR',
            'method' => 'bank_transfer',
            'reference_number' => 'REF-' . mt_rand(1000, 9999),
            'status' => $status,
            'notes' => 'test',
        ];
    }

    private function makeInvoiceGraph(float $totalAmount, string $status = 'issued'): array
    {
        [, $actorId, $invoice] = $this->makeInvoiceGraphWithStatus($totalAmount, $status);

        return [$invoice, $actorId];
    }

    private function makeInvoiceGraphWithStatus(float $totalAmount, string $status): array
    {
        $user = User::factory()->create();
        $actorId = (int) $user->id;
        $now = now();

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'QA Customer',
            'company_name' => null,
            'code' => 'QAC-' . Str::uuid()->toString(),
            'email' => null,
            'phone' => null,
            'address' => null,
            'customer_type' => 'individual',
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inquiryId = DB::table('inquiries')->insertGetId([
            'inquiry_number' => 'INQ-QA-' . mt_rand(100000, 999999),
            'customer_id' => $customerId,
            'source' => 'web',
            'status' => 'registered',
            'priority' => 'normal',
            'assigned_to' => null,
            'deadline' => null,
            'notes' => null,
            'reminder_enabled' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $quotationId = DB::table('quotations')->insertGetId([
            'quotation_number' => 'QTN-QA-' . mt_rand(100000, 999999),
            'inquiry_id' => $inquiryId,
            'status' => 'accepted',
            'validity_date' => $now->copy()->addDays(7)->toDateString(),
            'sub_total' => $totalAmount,
            'discount_type' => null,
            'discount_value' => 0,
            'final_amount' => $totalAmount,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bookingId = DB::table('bookings')->insertGetId([
            'booking_number' => 'BKG-QA-' . mt_rand(100000, 999999),
            'quotation_id' => $quotationId,
            'travel_date' => $now->copy()->addDays(30)->toDateString(),
            'status' => 'confirmed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-QA-' . mt_rand(100000, 999999),
            'booking_id' => $bookingId,
            'invoice_type' => 'full_payment',
            'invoice_date' => $now->toDateString(),
            'due_date' => $now->copy()->addDays(14)->toDateString(),
            'subtotal' => $totalAmount,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'status' => $status,
            'notes' => null,
            'generated_by' => $actorId,
            'paid_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$user, $actorId, Invoice::query()->findOrFail($invoiceId)];
    }

    private function assertConfirmThrows(PaymentService $service, Payment $payment, int $actorId): void
    {
        try {
            $service->confirmPayment($payment, $actorId);
            $this->fail('Expected RuntimeException when confirming invalid payment state.');
        } catch (\RuntimeException $e) {
            $this->assertTrue(true);
        }
    }

    private function assertCreateThrows(PaymentService $service, Invoice $invoice, int $actorId): void
    {
        try {
            $service->createPayment($invoice, $this->payload(100, 'pending'), null, $actorId);
            $this->fail('Expected RuntimeException when creating payment on blocked invoice status.');
        } catch (\RuntimeException $e) {
            $this->assertTrue(true);
        }
    }
}
