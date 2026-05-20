<?php

namespace Tests\Feature\Settlement;

use App\Models\Booking;
use App\Models\User;
use App\Services\SettlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettlementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Settlement integration tests require non-sqlite DB because legacy migrations use MySQL MODIFY syntax.');
        }
    }

    public function test_cannot_close_if_service_not_completed(): void
    {
        [$booking, $actorId] = $this->makeBookingGraph('confirmed', 1000, 1000);
        $service = app(SettlementService::class);
        $service->reviewBooking($booking, $actorId);

        $this->expectException(\RuntimeException::class);
        $service->closeBooking($booking, $actorId);
    }

    public function test_cannot_close_if_invoice_outstanding(): void
    {
        [$booking, $actorId] = $this->makeBookingGraph('service_completed', 1000, 600);
        $service = app(SettlementService::class);
        $service->reviewBooking($booking, $actorId);

        $this->expectException(\RuntimeException::class);
        $service->closeBooking($booking, $actorId);
    }

    public function test_cannot_close_if_payment_pending(): void
    {
        [$booking, $actorId] = $this->makeBookingGraph('service_completed', 1000, 1000, withPendingPayment: true);
        $service = app(SettlementService::class);
        $service->reviewBooking($booking, $actorId);

        $this->expectException(\RuntimeException::class);
        $service->closeBooking($booking, $actorId);
    }

    public function test_cannot_close_if_adjustment_pending(): void
    {
        [$booking, $actorId] = $this->makeBookingGraph('service_completed', 1000, 1000, withPendingPayment: false, withPendingAdjustment: true);
        $service = app(SettlementService::class);
        $service->reviewBooking($booking, $actorId);

        $this->expectException(\RuntimeException::class);
        $service->closeBooking($booking, $actorId);
    }

    public function test_can_close_when_all_settlement_checks_pass(): void
    {
        [$booking, $actorId] = $this->makeBookingGraph('service_completed', 1000, 1000);
        $service = app(SettlementService::class);

        $service->markSettled($booking, $actorId);
        $rechecked = $service->reviewBooking($booking->fresh(), $actorId);
        $this->assertSame([], (array) data_get($rechecked->metadata, 'blockers', []));
        $closed = $service->closeBooking($booking, $actorId);

        $this->assertSame('closed', (string) $closed->status);
    }

    private function makeBookingGraph(
        string $bookingStatus,
        float $invoiceTotal,
        float $confirmedPaid,
        bool $withPendingPayment = false,
        bool $withPendingAdjustment = false
    ): array {
        $user = User::factory()->create();
        $actorId = (int) $user->id;
        $now = now();

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Settlement QA Customer',
            'company_name' => null,
            'code' => 'STL-' . Str::uuid()->toString(),
            'email' => null,
            'phone' => null,
            'address' => null,
            'customer_type' => 'individual',
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inquiryId = DB::table('inquiries')->insertGetId([
            'inquiry_number' => 'INQ-STL-' . mt_rand(100000, 999999),
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
            'quotation_number' => 'QTN-STL-' . mt_rand(100000, 999999),
            'inquiry_id' => $inquiryId,
            'status' => 'accepted',
            'validity_date' => $now->copy()->addDays(7)->toDateString(),
            'sub_total' => $invoiceTotal,
            'discount_type' => null,
            'discount_value' => 0,
            'final_amount' => $invoiceTotal,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bookingId = DB::table('bookings')->insertGetId([
            'booking_number' => 'BKG-STL-' . mt_rand(100000, 999999),
            'quotation_id' => $quotationId,
            'travel_date' => $now->copy()->addDays(5)->toDateString(),
            'status' => $bookingStatus,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-STL-' . mt_rand(100000, 999999),
            'booking_id' => $bookingId,
            'invoice_type' => 'full_payment',
            'invoice_date' => $now->toDateString(),
            'due_date' => $now->copy()->addDays(14)->toDateString(),
            'subtotal' => $invoiceTotal,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $invoiceTotal,
            'paid_amount' => $confirmedPaid,
            'balance_amount' => max($invoiceTotal - $confirmedPaid, 0),
            'status' => $confirmedPaid >= $invoiceTotal ? 'paid' : 'partially_paid',
            'notes' => null,
            'generated_by' => $actorId,
            'paid_at' => $confirmedPaid > 0 ? $now : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($confirmedPaid > 0) {
            DB::table('payments')->insert([
                'invoice_id' => $invoiceId,
                'payment_number' => 'PAY-STL-' . mt_rand(100000, 999999),
                'payment_type' => 'full_payment',
                'payment_date' => $now->toDateString(),
                'amount' => $confirmedPaid,
                'currency_code' => 'IDR',
                'method' => 'bank_transfer',
                'reference_number' => 'REF-STL-' . mt_rand(1000, 9999),
                'status' => 'confirmed',
                'notes' => null,
                'confirmed_by' => $actorId,
                'confirmed_at' => $now,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($withPendingPayment) {
            DB::table('payments')->insert([
                'invoice_id' => $invoiceId,
                'payment_number' => 'PAY-STL-P-' . mt_rand(100000, 999999),
                'payment_type' => 'additional_payment',
                'payment_date' => $now->toDateString(),
                'amount' => 50,
                'currency_code' => 'IDR',
                'method' => 'bank_transfer',
                'reference_number' => 'REF-STL-P-' . mt_rand(1000, 9999),
                'status' => 'pending',
                'notes' => null,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($withPendingAdjustment) {
            DB::table('booking_adjustments')->insert([
                'booking_id' => $bookingId,
                'adjustment_number' => 'ADJ-STL-' . mt_rand(100000, 999999),
                'adjustment_type' => 'manual_adjustment',
                'status' => 'pending_approval',
                'title' => 'Pending adjustment',
                'description' => null,
                'reason' => null,
                'amount' => 0,
                'currency_code' => 'IDR',
                'impact_type' => 'non_financial',
                'requested_by' => $actorId,
                'requested_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return [Booking::query()->findOrFail($bookingId), $actorId];
    }
}
