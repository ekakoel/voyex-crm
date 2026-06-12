<?php

namespace App\Services\Quotation;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Support\Workflow\QuotationStatusNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class QuotationWorkflowService
{
    public function __construct(
        private readonly QuotationStatusService $quotationStatusService
    ) {
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function allowedTransitions(): array
    {
        return [
            QuotationStatus::Draft->value => [
                QuotationStatus::NeedValidation->value,
                QuotationStatus::PendingValidation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::NeedValidation->value => [
                QuotationStatus::ReadyToSend->value,
                QuotationStatus::NeedRevalidation->value,
                QuotationStatus::PendingRevalidation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::PendingValidation->value => [
                QuotationStatus::Validated->value,
                QuotationStatus::ReadyToSend->value,
                QuotationStatus::NeedRevalidation->value,
                QuotationStatus::PendingRevalidation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::Validated->value => [
                QuotationStatus::ReadyToSend->value,
                QuotationStatus::NeedValidation->value,
                QuotationStatus::PendingRevalidation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::ReadyToSend->value => [
                QuotationStatus::Sent->value,
                QuotationStatus::UnderRevision->value,
                QuotationStatus::NeedValidation->value,
                QuotationStatus::PendingRevalidation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::Sent->value => [
                QuotationStatus::RevisionRequested->value,
                QuotationStatus::UnderRevision->value,
                QuotationStatus::Pending->value,
                QuotationStatus::NeedRevalidation->value,
                QuotationStatus::PendingRevalidation->value,
                QuotationStatus::CustomerApproved->value,
                QuotationStatus::Approved->value,
                QuotationStatus::Lost->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::Pending->value => [
                QuotationStatus::RevisionRequested->value,
                QuotationStatus::UnderRevision->value,
                QuotationStatus::NeedRevalidation->value,
                QuotationStatus::PendingRevalidation->value,
                QuotationStatus::CustomerApproved->value,
                QuotationStatus::Approved->value,
                QuotationStatus::Lost->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::RevisionRequested->value => [
                QuotationStatus::UnderRevision->value,
                QuotationStatus::Lost->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::UnderRevision->value => [
                QuotationStatus::NeedRevalidation->value,
                QuotationStatus::PendingRevalidation->value,
                QuotationStatus::Validated->value,
                QuotationStatus::ReadyToSend->value,
                QuotationStatus::CustomerApproved->value,
                QuotationStatus::Approved->value,
                QuotationStatus::Lost->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::NeedRevalidation->value => [
                QuotationStatus::UnderRevision->value,
                QuotationStatus::Validated->value,
                QuotationStatus::ReadyToSend->value,
                QuotationStatus::CustomerApproved->value,
                QuotationStatus::Approved->value,
                QuotationStatus::Lost->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::PendingRevalidation->value => [
                QuotationStatus::UnderRevision->value,
                QuotationStatus::Validated->value,
                QuotationStatus::ReadyToSend->value,
                QuotationStatus::CustomerApproved->value,
                QuotationStatus::Approved->value,
                QuotationStatus::Lost->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::CustomerApproved->value => [
                QuotationStatus::ConvertedToBooking->value,
                QuotationStatus::BookingCreated->value,
                QuotationStatus::BookingInProgress->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::Approved->value => [
                QuotationStatus::ConvertedToBooking->value,
                QuotationStatus::BookingCreated->value,
                QuotationStatus::BookingInProgress->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::ConvertedToBooking->value => [
                QuotationStatus::BookingInProgress->value,
                QuotationStatus::Invoiced->value,
                QuotationStatus::InOperation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::BookingCreated->value => [
                QuotationStatus::BookingInProgress->value,
                QuotationStatus::Invoiced->value,
                QuotationStatus::InOperation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::BookingInProgress->value => [
                QuotationStatus::BookingIssue->value,
                QuotationStatus::Invoiced->value,
                QuotationStatus::InOperation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::BookingIssue->value => [
                QuotationStatus::UnderRevision->value,
                QuotationStatus::NeedRevalidation->value,
                QuotationStatus::PendingRevalidation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::Invoiced->value => [
                QuotationStatus::WaitingPayment->value,
                QuotationStatus::InOperation->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::WaitingPayment->value => [
                QuotationStatus::InOperation->value,
                QuotationStatus::Completed->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::InOperation->value => [
                QuotationStatus::OperationAdjustment->value,
                QuotationStatus::Finalized->value,
                QuotationStatus::Completed->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::OperationAdjustment->value => [
                QuotationStatus::InOperation->value,
                QuotationStatus::Finalized->value,
                QuotationStatus::Cancelled->value,
            ],
            QuotationStatus::Finalized->value => [
                QuotationStatus::Completed->value,
            ],
        ];
    }

    public function canTransition(Quotation $quotation, string $targetStatus): bool
    {
        $current = QuotationStatus::normalize((string) ($quotation->status ?? ''));
        $target = QuotationStatus::normalize($targetStatus);

        if (in_array($target, [QuotationStatus::ReadyToSend->value, QuotationStatus::Sent->value], true)
            && ! (bool) ($this->quotationStatusService->validationProgress($quotation)['is_complete'] ?? false)) {
            return false;
        }

        if (in_array($target, [QuotationStatus::ReadyToSend->value, QuotationStatus::Sent->value], true)
            && $this->quotationStatusService->hasPendingRevisionResponse($quotation)) {
            return false;
        }

        if ($current === $target) {
            return true;
        }

        return in_array($target, $this->allowedTransitions()[$current] ?? [], true);
    }

    public function canUsePostSentAction(Quotation $quotation, string $targetStatus): bool
    {
        return QuotationStatus::normalize((string) ($quotation->status ?? '')) === QuotationStatus::Sent->value
            && in_array(QuotationStatus::normalize($targetStatus), [
                QuotationStatus::CustomerApproved->value,
                QuotationStatus::Approved->value,
                QuotationStatus::RevisionRequested->value,
                QuotationStatus::UnderRevision->value,
                QuotationStatus::Pending->value,
                QuotationStatus::Lost->value,
                QuotationStatus::Cancelled->value,
            ], true)
            && $this->canTransition($quotation, $targetStatus);
    }

    public function transition(
        Quotation $quotation,
        string $targetStatus,
        ?int $actorId = null,
        ?string $reason = null,
        array $metadata = []
    ): Quotation {
        return DB::transaction(function () use ($quotation, $targetStatus, $actorId, $reason, $metadata): Quotation {
            $quotation->refresh();
            $oldStatus = QuotationStatus::normalize((string) ($quotation->status ?? ''));
            $newStatus = QuotationStatus::normalize($targetStatus);

            if (! $this->canTransition($quotation, $newStatus)) {
                if (in_array($newStatus, [QuotationStatus::ReadyToSend->value, QuotationStatus::Sent->value], true)) {
                    $this->quotationStatusService->assertReadyToSend($quotation);
                }

                throw ValidationException::withMessages([
                    'status' => "Quotation status cannot transition from {$oldStatus} to {$newStatus}.",
                ]);
            }

            $dimensions = $this->dimensionsFor($quotation, $newStatus);
            $patch = array_merge(['status' => $newStatus], $dimensions);
            $isNewSend = $newStatus === QuotationStatus::Sent->value && $oldStatus !== QuotationStatus::Sent->value;

            if ($isNewSend && Schema::hasColumn('quotations', 'last_sent_at')) {
                $patch['last_sent_at'] = now();
            }
            if ($isNewSend && Schema::hasColumn('quotations', 'sent_at')) {
                $patch['sent_at'] = now();
            }
            if ($isNewSend && Schema::hasColumn('quotations', 'sent_by')) {
                $patch['sent_by'] = $this->validUserId($actorId);
            }
            if ($isNewSend && Schema::hasColumn('quotations', 'sent_count')) {
                $patch['sent_count'] = DB::raw('COALESCE(sent_count, 0) + 1');
            }
            if ($isNewSend && Schema::hasColumn('quotations', 'follow_up_status')) {
                $patch['follow_up_status'] = 'follow_up_due';
            }
            if (
                $isNewSend
                && Schema::hasColumn('quotations', 'next_follow_up_at')
                && empty($quotation->next_follow_up_at)
            ) {
                $patch['next_follow_up_at'] = now()->addDays(3);
            }
            if (
                in_array($newStatus, [
                    QuotationStatus::CustomerApproved->value,
                    QuotationStatus::Approved->value,
                    QuotationStatus::ConvertedToBooking->value,
                    QuotationStatus::BookingCreated->value,
                    QuotationStatus::BookingInProgress->value,
                    QuotationStatus::Invoiced->value,
                    QuotationStatus::WaitingPayment->value,
                    QuotationStatus::InOperation->value,
                    QuotationStatus::Finalized->value,
                    QuotationStatus::Completed->value,
                    QuotationStatus::Cancelled->value,
                    QuotationStatus::Rejected->value,
                    QuotationStatus::Lost->value,
                ], true)
            ) {
                if (Schema::hasColumn('quotations', 'next_follow_up_at')) {
                    $patch['next_follow_up_at'] = null;
                }
                if (Schema::hasColumn('quotations', 'follow_up_status')) {
                    $patch['follow_up_status'] = 'not_required';
                }
            }
            if ($newStatus === QuotationStatus::Cancelled->value && Schema::hasColumn('quotations', 'cancelled_at')) {
                $patch['cancelled_at'] = now();
            }
            if ($newStatus === QuotationStatus::Completed->value && Schema::hasColumn('quotations', 'completed_at')) {
                $patch['completed_at'] = now();
            }

            $this->updateQuotationColumns($quotation, $patch);
            $quotation->refresh();
            $this->writeStatusLog($quotation, $oldStatus, $newStatus, $actorId, $reason, $metadata);
            $this->writeSendLog($quotation, $oldStatus, $newStatus, $actorId, $reason, $metadata);

            return $quotation;
        });
    }

    public function syncDimensions(Quotation $quotation, ?int $actorId = null, array $metadata = []): Quotation
    {
        return DB::transaction(function () use ($quotation, $actorId, $metadata): Quotation {
            $quotation->refresh();
            $status = QuotationStatus::normalize((string) ($quotation->status ?? ''));
            $dimensions = $this->dimensionsFor($quotation, $status);

            $this->updateQuotationColumns($quotation, $dimensions);
            $quotation->refresh();
            $this->writeStatusLog($quotation, $status, $status, $actorId, 'sync_workflow_dimensions', $metadata);

            return $quotation;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function dimensionsFor(Quotation $quotation, ?string $status = null): array
    {
        $status = QuotationStatus::normalize($status ?? (string) ($quotation->status ?? ''));
        $booking = $this->latestBooking($quotation);
        $invoice = $booking ? $this->latestInvoice((int) $booking->id) : null;
        $paymentStatus = $invoice ? $this->derivePaymentStatus((int) $invoice->id, (string) ($invoice->status ?? '')) : 'not_invoiced';
        $bookingStatus = $booking ? (string) ($booking->status ?? 'created') : 'not_created';

        return [
            'send_status' => QuotationStatusNormalizer::send($status),
            'approval_status' => QuotationStatusNormalizer::approval($status),
            'booking_status' => $bookingStatus,
            'invoice_status' => $invoice ? (string) ($invoice->status ?? 'issued') : 'not_created',
            'payment_status' => $paymentStatus,
            'operation_status' => $this->deriveOperationStatus($bookingStatus),
            'current_stage' => $this->deriveCurrentStage($status, $bookingStatus, (bool) $invoice, $paymentStatus),
            'next_action' => $this->deriveNextAction($status, $bookingStatus, (bool) $invoice, $paymentStatus),
        ];
    }

    private function updateQuotationColumns(Quotation $quotation, array $patch): void
    {
        $allowed = [];
        foreach ($patch as $column => $value) {
            if (Schema::hasColumn('quotations', $column)) {
                $allowed[$column] = $value;
            }
        }

        if ($allowed !== []) {
            DB::table('quotations')->where('id', (int) $quotation->id)->update($allowed);
        }
    }

    private function writeStatusLog(
        Quotation $quotation,
        string $oldStatus,
        string $newStatus,
        ?int $actorId,
        ?string $reason,
        array $metadata
    ): void {
        if (! Schema::hasTable('quotation_status_logs')) {
            return;
        }

        $actorId = $this->validUserId($actorId);

        DB::table('quotation_status_logs')->insert([
            'quotation_id' => $quotation->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_stage' => QuotationStatusNormalizer::currentStage($oldStatus),
            'new_stage' => (string) ($quotation->current_stage ?? QuotationStatusNormalizer::currentStage($newStatus)),
            'action' => $reason ?: ($oldStatus === $newStatus ? 'sync' : 'transition'),
            'reason' => $reason,
            'changed_by' => $actorId,
            'changed_at' => now(),
            'metadata' => $metadata === [] ? null : json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function writeSendLog(
        Quotation $quotation,
        string $oldStatus,
        string $newStatus,
        ?int $actorId,
        ?string $reason,
        array $metadata
    ): void {
        if ($newStatus !== QuotationStatus::Sent->value || $oldStatus === QuotationStatus::Sent->value || ! Schema::hasTable('quotation_send_logs')) {
            return;
        }

        $actorId = $this->validUserId($actorId);

        DB::table('quotation_send_logs')->insert([
            'quotation_id' => $quotation->id,
            'send_status' => 'sent',
            'channel' => $metadata['channel'] ?? null,
            'recipient' => $metadata['recipient'] ?? null,
            'sent_by' => $actorId,
            'sent_at' => now(),
            'notes' => $reason,
            'metadata' => $metadata === [] ? null : json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validUserId(?int $userId): ?int
    {
        if (! $userId || ! Schema::hasTable('users')) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->exists() ? $userId : null;
    }

    private function latestBooking(Quotation $quotation): ?object
    {
        if (! Schema::hasTable('bookings')) {
            return null;
        }

        return DB::table('bookings')
            ->where('quotation_id', (int) $quotation->id)
            ->orderByDesc('id')
            ->first();
    }

    private function latestInvoice(int $bookingId): ?object
    {
        if (! Schema::hasTable('invoices')) {
            return null;
        }

        return DB::table('invoices')
            ->where('booking_id', $bookingId)
            ->orderByDesc('id')
            ->first();
    }

    private function derivePaymentStatus(int $invoiceId, string $invoiceStatus): string
    {
        if (in_array($invoiceStatus, ['paid', 'overpaid'], true)) {
            return $invoiceStatus;
        }

        if (! Schema::hasTable('payments')) {
            return 'unpaid';
        }

        $confirmedAmount = (float) DB::table('payments')
            ->where('invoice_id', $invoiceId)
            ->where('status', 'confirmed')
            ->sum('amount');

        if ($confirmedAmount > 0) {
            return 'partially_paid';
        }

        $hasPendingPayment = DB::table('payments')
            ->where('invoice_id', $invoiceId)
            ->whereIn('status', ['pending', 'waiting_confirmation'])
            ->exists();

        return $hasPendingPayment ? 'waiting_confirmation' : 'unpaid';
    }

    private function deriveOperationStatus(string $bookingStatus): string
    {
        return match ($bookingStatus) {
            'ready_to_operate' => 'ready_to_operate',
            'in_operation' => 'in_operation',
            'service_completed' => 'service_completed',
            'reconciliation' => 'reconciliation',
            'completed_settled', 'closed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'not_started',
        };
    }

    private function deriveCurrentStage(string $status, string $bookingStatus, bool $hasInvoice, string $paymentStatus): string
    {
        if (in_array($status, QuotationStatus::terminalStatuses(), true)) {
            return $status;
        }
        if (in_array($bookingStatus, ['in_operation', 'service_completed', 'reconciliation', 'completed_settled', 'closed'], true)) {
            return 'operation';
        }
        if (! in_array($paymentStatus, ['not_invoiced', 'unpaid'], true)) {
            return 'payment';
        }
        if ($hasInvoice) {
            return 'invoice';
        }
        if ($bookingStatus !== 'not_created') {
            return 'booking';
        }

        return QuotationStatusNormalizer::currentStage($status);
    }

    private function deriveNextAction(string $status, string $bookingStatus, bool $hasInvoice, string $paymentStatus): string
    {
        if (in_array($status, QuotationStatus::terminalStatuses(), true)) {
            return 'none';
        }
        if (in_array($bookingStatus, ['in_operation', 'service_completed', 'reconciliation'], true)) {
            return 'continue_operation';
        }
        if ($hasInvoice && ! in_array($paymentStatus, ['paid', 'overpaid'], true)) {
            return 'follow_up_payment';
        }
        if ($bookingStatus !== 'not_created') {
            return 'continue_booking';
        }

        return match ($status) {
            QuotationStatus::Draft->value => 'submit_for_validation',
            QuotationStatus::NeedValidation->value,
            QuotationStatus::PendingValidation->value,
            QuotationStatus::NeedRevalidation->value,
            QuotationStatus::PendingRevalidation->value => 'validate_items',
            QuotationStatus::Validated->value,
            QuotationStatus::ReadyToSend->value => 'send_quotation',
            QuotationStatus::Sent->value,
            QuotationStatus::Pending->value => 'follow_up_customer',
            QuotationStatus::RevisionRequested->value,
            QuotationStatus::UnderRevision->value => 'revise_quotation',
            QuotationStatus::CustomerApproved->value,
            QuotationStatus::Approved->value => 'create_booking',
            QuotationStatus::ConvertedToBooking->value,
            QuotationStatus::BookingCreated->value,
            QuotationStatus::BookingInProgress->value => 'continue_booking',
            default => 'review_quotation',
        };
    }
}
