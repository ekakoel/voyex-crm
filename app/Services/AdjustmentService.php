<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAdjustment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class AdjustmentService
{
    public function __construct(private readonly InvoiceService $invoiceService)
    {
    }

    public function createDraft(Booking $booking, array $payload, int $actorId): BookingAdjustment
    {
        return DB::transaction(function () use ($booking, $payload, $actorId) {
            $data = $this->normalizePayload($payload);
            $this->assertOwnership($booking, $data);
            $data['booking_id'] = $booking->id;
            $data['adjustment_number'] = $this->generateAdjustmentNumber();
            $data['status'] = 'draft';
            $data['requested_by'] = $actorId ?: null;
            $data['requested_at'] = now();

            $adjustment = BookingAdjustment::query()->create($data);
            $booking->logActivity('adjustment.created', $booking, [
                'adjustment_id' => $adjustment->id,
                'adjustment_number' => $adjustment->adjustment_number,
            ]);

            return $adjustment->fresh();
        });
    }

    public function updateDraft(BookingAdjustment $adjustment, array $payload, int $actorId): BookingAdjustment
    {
        if (! $adjustment->isDraft()) {
            throw new \RuntimeException('Only draft adjustment can be edited.');
        }

        $data = $this->normalizePayload($payload);
        $this->assertOwnership($adjustment->booking()->firstOrFail(), $data);
        $adjustment->update($data);
        $adjustment->booking?->logActivity('adjustment.updated', $adjustment->booking, [
            'adjustment_id' => $adjustment->id,
            'adjustment_number' => $adjustment->adjustment_number,
            'updated_by' => $actorId,
        ]);

        return $adjustment->fresh();
    }

    public function submitForApproval(BookingAdjustment $adjustment, int $actorId): BookingAdjustment
    {
        if (! $adjustment->canSubmit()) {
            throw new \RuntimeException('Adjustment cannot be submitted.');
        }

        $adjustment->update([
            'status' => 'pending_approval',
            'requested_by' => $actorId ?: $adjustment->requested_by,
            'requested_at' => now(),
        ]);

        $adjustment->booking?->logActivity('adjustment.submitted', $adjustment->booking, [
            'adjustment_id' => $adjustment->id,
            'adjustment_number' => $adjustment->adjustment_number,
        ]);

        return $adjustment->fresh();
    }

    public function approve(BookingAdjustment $adjustment, int $actorId): BookingAdjustment
    {
        if (! $adjustment->canApprove()) {
            throw new \RuntimeException('Adjustment cannot be approved.');
        }

        $adjustment->update([
            'status' => 'approved',
            'approved_by' => $actorId ?: null,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $adjustment->booking?->logActivity('adjustment.approved', $adjustment->booking, [
            'adjustment_id' => $adjustment->id,
            'adjustment_number' => $adjustment->adjustment_number,
        ]);

        return $adjustment->fresh();
    }

    public function reject(BookingAdjustment $adjustment, int $actorId, ?string $reason = null): BookingAdjustment
    {
        if (! $adjustment->canReject()) {
            throw new \RuntimeException('Adjustment cannot be rejected.');
        }

        $adjustment->update([
            'status' => 'rejected',
            'rejected_by' => $actorId ?: null,
            'rejected_at' => now(),
            'rejection_reason' => trim((string) $reason) ?: null,
        ]);

        $adjustment->booking?->logActivity('adjustment.rejected', $adjustment->booking, [
            'adjustment_id' => $adjustment->id,
            'adjustment_number' => $adjustment->adjustment_number,
            'rejection_reason' => $adjustment->rejection_reason,
        ]);

        return $adjustment->fresh();
    }

    public function cancel(BookingAdjustment $adjustment, int $actorId, ?string $reason = null): BookingAdjustment
    {
        if (! $adjustment->canCancel()) {
            throw new \RuntimeException('Adjustment cannot be cancelled.');
        }

        $metadata = (array) ($adjustment->metadata ?? []);
        if (trim((string) $reason) !== '') {
            $metadata['cancel_reason'] = trim((string) $reason);
        }

        $adjustment->update([
            'status' => 'cancelled',
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);

        $adjustment->booking?->logActivity('adjustment.cancelled', $adjustment->booking, [
            'adjustment_id' => $adjustment->id,
            'adjustment_number' => $adjustment->adjustment_number,
        ]);

        return $adjustment->fresh();
    }

    public function apply(BookingAdjustment $adjustment, int $actorId): BookingAdjustment
    {
        if (! $adjustment->canApply()) {
            throw new \RuntimeException('Adjustment cannot be applied.');
        }

        return DB::transaction(function () use ($adjustment, $actorId) {
            $adjustment->refresh();
            if (! $adjustment->canApply()) {
                throw new \RuntimeException('Adjustment cannot be applied.');
            }

            $generatedInvoiceId = $this->applyFinancialImpact($adjustment, $actorId);

            $adjustment->update([
                'status' => 'applied',
                'applied_by' => $actorId ?: null,
                'applied_at' => now(),
                'generated_invoice_id' => $generatedInvoiceId,
            ]);

            $adjustment->booking?->logActivity('adjustment.applied', $adjustment->booking, [
                'adjustment_id' => $adjustment->id,
                'adjustment_number' => $adjustment->adjustment_number,
                'generated_invoice_id' => $generatedInvoiceId,
            ]);

            return $adjustment->fresh();
        });
    }

    public function applyFinancialImpact(BookingAdjustment $adjustment, int $actorId): ?int
    {
        if (! $adjustment->isFinancial()) {
            return null;
        }

        $booking = $adjustment->booking;
        if (! $booking) {
            throw new \RuntimeException('Adjustment booking relation is missing.');
        }

        $type = 'additional_charge';
        if ((string) $adjustment->adjustment_type === 'cancellation_fee') {
            $type = 'cancellation_fee';
        } elseif ((string) $adjustment->impact_type === 'refund' || (string) $adjustment->adjustment_type === 'refund') {
            $type = 'refund';
        } elseif ((string) $adjustment->impact_type === 'credit') {
            $type = 'refund';
        }

        $amount = abs((float) ($adjustment->amount ?? 0));
        if ($amount <= 0) {
            throw new \RuntimeException('Adjustment amount must be greater than zero for financial impact.');
        }

        $amounts = $this->invoiceService->computeAmounts($amount, 0, 0, 0);
        $invoice = Invoice::query()->create(array_merge($amounts, [
            'invoice_number' => 'INV-ADJ-' . now()->format('Ymd') . '-' . random_int(1000, 9999),
            'booking_id' => $booking->id,
            'invoice_type' => $type,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'generated_by' => $actorId ?: null,
            'notes' => trim((string) $adjustment->title) . ' | ' . trim((string) ($adjustment->reason ?? '')),
        ]));

        $booking->logActivity('adjustment.invoice_generated', $booking, [
            'adjustment_id' => $adjustment->id,
            'invoice_id' => $invoice->id,
            'invoice_type' => $invoice->invoice_type,
            'amount' => (float) $invoice->total_amount,
        ]);

        return (int) $invoice->id;
    }

    public function generateAdjustmentNumber(): string
    {
        do {
            $number = 'ADJ-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (BookingAdjustment::query()->where('adjustment_number', $number)->exists());

        return $number;
    }

    private function normalizePayload(array $payload): array
    {
        return [
            'booking_item_id' => ! empty($payload['booking_item_id']) ? (int) $payload['booking_item_id'] : null,
            'quotation_id' => ! empty($payload['quotation_id']) ? (int) $payload['quotation_id'] : null,
            'invoice_id' => ! empty($payload['invoice_id']) ? (int) $payload['invoice_id'] : null,
            'payment_id' => ! empty($payload['payment_id']) ? (int) $payload['payment_id'] : null,
            'adjustment_type' => (string) ($payload['adjustment_type'] ?? 'manual_adjustment'),
            'type' => (string) ($payload['type'] ?? $payload['adjustment_type'] ?? 'manual_adjustment'),
            'title' => trim((string) ($payload['title'] ?? '')),
            'description' => trim((string) ($payload['description'] ?? '')) ?: null,
            'reason' => trim((string) ($payload['reason'] ?? '')) ?: null,
            'amount' => (float) ($payload['amount'] ?? 0),
            'amount_type' => (string) ($payload['amount_type'] ?? 'fixed'),
            'percentage' => isset($payload['percentage']) ? (float) $payload['percentage'] : null,
            'calculated_amount' => isset($payload['calculated_amount']) ? (float) $payload['calculated_amount'] : (float) ($payload['amount'] ?? 0),
            'currency_code' => $payload['currency_code'] ?? null,
            'impact_type' => (string) ($payload['impact_type'] ?? 'non_financial'),
            'created_by' => ! empty($payload['created_by']) ? (int) $payload['created_by'] : null,
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : null,
        ];
    }

    private function assertOwnership(Booking $booking, array $data): void
    {
        if (! empty($data['booking_item_id']) && ! $booking->items()->whereKey((int) $data['booking_item_id'])->exists()) {
            throw new \RuntimeException('Selected booking item does not belong to this booking.');
        }

        if (! empty($data['quotation_id']) && (int) $data['quotation_id'] !== (int) $booking->quotation_id) {
            throw new \RuntimeException('Selected quotation does not belong to this booking.');
        }

        if (! empty($data['invoice_id']) && ! $booking->invoices()->whereKey((int) $data['invoice_id'])->exists()) {
            throw new \RuntimeException('Selected invoice does not belong to this booking.');
        }

        if (! empty($data['payment_id'])) {
            $paymentExists = $booking->invoices()
                ->whereHas('payments', fn ($paymentQuery) => $paymentQuery->whereKey((int) $data['payment_id']))
                ->exists();
            if (! $paymentExists) {
                throw new \RuntimeException('Selected payment does not belong to this booking invoices.');
            }
        }
    }
}
