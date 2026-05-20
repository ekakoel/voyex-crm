<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAdjustment;
use App\Models\BookingSettlement;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function generateSettlementNumber(): string
    {
        do {
            $number = 'STL-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (BookingSettlement::query()->where('settlement_number', $number)->exists());

        return $number;
    }

    public function reviewBooking(Booking $booking, int $actorId, ?string $notes = null): BookingSettlement
    {
        return DB::transaction(function () use ($booking, $actorId, $notes): BookingSettlement {
            $booking->loadMissing(['invoices.payments', 'adjustments']);
            $summary = $this->calculateSettlementSummary($booking);
            $blockers = $this->detectBlockingIssues($booking, $summary);
            $status = $this->resolveStatusFromBlockers($blockers, $summary);

            $settlement = BookingSettlement::query()->firstOrNew(['booking_id' => $booking->id]);
            if (! $settlement->exists) {
                $settlement->settlement_number = $this->generateSettlementNumber();
            }

            $settlement->fill([
                'status' => $status,
                'service_completed_check' => ! in_array('service_not_completed', $blockers, true),
                'invoice_check' => ! in_array('outstanding_balance', $blockers, true),
                'payment_check' => ! in_array('pending_payment', $blockers, true),
                'adjustment_check' => ! in_array('pending_adjustment', $blockers, true),
                'overpayment_check' => ! in_array('overpayment_unresolved', $blockers, true),
                'total_invoice_amount' => $summary['total_invoice_amount'],
                'total_paid_amount' => $summary['total_paid_amount'],
                'outstanding_amount' => $summary['outstanding_amount'],
                'overpaid_amount' => $summary['overpaid_amount'],
                'settlement_notes' => $notes !== null ? trim($notes) : $settlement->settlement_notes,
                'reviewed_by' => $actorId ?: null,
                'reviewed_at' => now(),
                'metadata' => [
                    'blockers' => $blockers,
                    'pending_payment_ids' => $summary['pending_payment_ids'],
                    'pending_adjustment_ids' => $summary['pending_adjustment_ids'],
                    'active_invoice_ids' => $summary['active_invoice_ids'],
                ],
            ]);
            $settlement->save();

            $booking->logActivity('settlement.reviewed', $booking, [
                'settlement_id' => $settlement->id,
                'settlement_number' => $settlement->settlement_number,
                'status' => $settlement->status,
                'blockers' => $blockers,
            ]);

            if ($blockers !== []) {
                $booking->logActivity('settlement.blocked', $booking, [
                    'settlement_id' => $settlement->id,
                    'settlement_number' => $settlement->settlement_number,
                    'blockers' => $blockers,
                ]);
            }

            return $settlement->fresh(['reviewer', 'finalizer']);
        });
    }

    public function calculateSettlementSummary(Booking $booking): array
    {
        $activeInvoices = $booking->invoices
            ->filter(fn ($invoice) => ! in_array((string) ($invoice->status ?? ''), ['void', 'cancelled'], true))
            ->values();

        $totalInvoiceAmount = (float) $activeInvoices->sum(fn ($invoice) => (float) ($invoice->total_amount ?? 0));
        $confirmedPayments = $activeInvoices->flatMap(function ($invoice) {
            return $invoice->payments->filter(fn ($payment) => (string) ($payment->status ?? '') === 'confirmed');
        });
        $totalPaidAmount = (float) $confirmedPayments->sum(fn ($payment) => (float) ($payment->amount ?? 0));

        $pendingPayments = $activeInvoices->flatMap(function ($invoice) {
            return $invoice->payments->filter(fn ($payment) => in_array((string) ($payment->status ?? ''), ['pending', 'waiting_confirmation'], true));
        })->values();

        $pendingAdjustments = $booking->adjustments
            ->filter(fn ($adjustment) => in_array((string) ($adjustment->status ?? ''), ['draft', 'pending_approval', 'approved'], true))
            ->values();

        $outstandingAmount = max($totalInvoiceAmount - $totalPaidAmount, 0);
        $overpaidAmount = max($totalPaidAmount - $totalInvoiceAmount, 0);

        return [
            'total_invoice_amount' => $totalInvoiceAmount,
            'total_paid_amount' => $totalPaidAmount,
            'outstanding_amount' => $outstandingAmount,
            'overpaid_amount' => $overpaidAmount,
            'pending_payment_ids' => $pendingPayments->pluck('id')->values()->all(),
            'pending_adjustment_ids' => $pendingAdjustments->pluck('id')->values()->all(),
            'active_invoice_ids' => $activeInvoices->pluck('id')->values()->all(),
        ];
    }

    public function detectBlockingIssues(Booking $booking, array $summary): array
    {
        $blockers = [];

        if (! in_array((string) ($booking->status ?? ''), ['service_completed', 'completed_settled'], true)) {
            $blockers[] = 'service_not_completed';
        }
        if ((float) ($summary['outstanding_amount'] ?? 0) > 0) {
            $blockers[] = 'outstanding_balance';
        }
        if (! empty($summary['pending_payment_ids'])) {
            $blockers[] = 'pending_payment';
        }
        if (! empty($summary['pending_adjustment_ids'])) {
            $blockers[] = 'pending_adjustment';
        }
        if ((float) ($summary['overpaid_amount'] ?? 0) > 0) {
            $blockers[] = 'overpayment_unresolved';
        }

        return $blockers;
    }

    public function markSettled(Booking $booking, int $actorId, ?string $notes = null): BookingSettlement
    {
        return DB::transaction(function () use ($booking, $actorId, $notes): BookingSettlement {
            $settlement = $this->reviewBooking($booking, $actorId, $notes);
            $blockers = (array) data_get($settlement->metadata, 'blockers', []);
            if ($blockers !== []) {
                throw new \RuntimeException('Settlement cannot be marked as settled because blockers still exist.');
            }

            $settlement->update([
                'status' => 'settled',
                'finalized_by' => $actorId ?: null,
                'finalized_at' => now(),
            ]);

            $booking->update(['status' => 'completed_settled']);
            $booking->logActivity('settlement.marked_settled', $booking, [
                'settlement_id' => $settlement->id,
                'settlement_number' => $settlement->settlement_number,
            ]);

            return $settlement->fresh(['reviewer', 'finalizer']);
        });
    }

    public function closeBooking(Booking $booking, int $actorId, ?string $notes = null): Booking
    {
        return DB::transaction(function () use ($booking, $actorId, $notes): Booking {
            $booking->refresh();
            $booking->loadMissing(['settlement']);
            $settlement = $booking->settlement;
            if (! $settlement || ! $settlement->isSettled()) {
                $booking->logActivity('booking.close_rejected', $booking, [
                    'reason' => 'settlement_not_passed',
                ]);
                throw new \RuntimeException('Booking cannot be closed because settlement is not settled yet.');
            }

            $refreshedSettlement = $this->reviewBooking($booking, $actorId, $notes);
            $blockers = (array) data_get($refreshedSettlement->metadata, 'blockers', []);
            if ($blockers !== []) {
                $booking->logActivity('booking.close_rejected', $booking, [
                    'reason' => 'settlement_blockers',
                    'blockers' => $blockers,
                ]);
                throw new \RuntimeException('Booking cannot be closed because settlement blockers still exist.');
            }

            $this->lockBookingAfterClose($booking, $actorId, $refreshedSettlement);

            return $booking->fresh(['settlement']);
        });
    }

    public function lockBookingAfterClose(Booking $booking, int $actorId, ?BookingSettlement $settlement = null): void
    {
        $booking->update(['status' => 'closed']);
        if ($settlement) {
            $settlement->update([
                'status' => 'settled',
                'finalized_by' => $settlement->finalized_by ?: ($actorId ?: null),
                'finalized_at' => $settlement->finalized_at ?: now(),
            ]);
        }

        $booking->logActivity('booking.closed', $booking, [
            'closed_by' => $actorId,
            'settlement_id' => $settlement?->id,
            'settlement_number' => $settlement?->settlement_number,
        ]);
    }

    private function resolveStatusFromBlockers(array $blockers, array $summary): string
    {
        if ($blockers === []) {
            return 'settled';
        }
        if (in_array('outstanding_balance', $blockers, true)) {
            return 'outstanding_balance';
        }
        if (in_array('pending_payment', $blockers, true)) {
            return 'pending_payment';
        }
        if (in_array('pending_adjustment', $blockers, true)) {
            return 'pending_adjustment';
        }
        if (in_array('overpayment_unresolved', $blockers, true)) {
            return ((float) ($summary['overpaid_amount'] ?? 0) > 0) ? 'overpaid' : 'refund_required';
        }

        return 'closed_blocked';
    }
}
