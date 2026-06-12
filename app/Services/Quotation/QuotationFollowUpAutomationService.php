<?php

namespace App\Services\Quotation;

use App\Models\Quotation;
use App\Models\QuotationCustomerResponse;
use App\Models\QuotationFollowUp;
use App\Models\QuotationFollowUpNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuotationFollowUpAutomationService
{
    private const OPEN_AUTOMATION_STATUSES = ['sent', 'pending'];
    private const CLOSED_STATUSES = ['customer_approved', 'approved', 'booking_created', 'converted_to_booking', 'completed', 'cancelled', 'rejected', 'lost'];

    public function __construct(
        private readonly QuotationWorkflowService $workflowService,
        private readonly QuotationFollowUpNotificationService $notificationService
    ) {
    }

    public function ensureInitialFollowUpNotification(Quotation $quotation): void
    {
        $quotation->refresh();
        if (! $quotation->isStatus(Quotation::STATUS_SENT)) {
            return;
        }

        $this->ensureInitialFollowUpRecord($quotation);

        $dueAt = $quotation->next_follow_up_at ?? now()->addDays(3);

        $this->notificationService->createForQuotation(
            $quotation,
            QuotationFollowUpNotification::TYPE_FOLLOW_UP_DUE,
            'Quotation follow-up scheduled',
            'Quotation has been marked as sent. Please follow up with the customer/agent in 3 days.',
            'info',
            $dueAt
        );
    }

    private function ensureInitialFollowUpRecord(Quotation $quotation): void
    {
        if (! Schema::hasTable('quotation_follow_ups')) {
            return;
        }

        $quotation->loadMissing('inquiry');
        $handledBy = $this->notificationService->resolveNotificationUserId($quotation, null);
        $nextFollowUpAt = now()->addDays(3);
        $revisionNumber = max(1, (int) ($quotation->revision_number ?? 1));
        $revisionLabel = $revisionNumber > 1
            ? 'Quotation Revision ' . $revisionNumber
            : 'Quotation';

        $exists = QuotationFollowUp::query()
            ->where('quotation_id', (int) $quotation->id)
            ->where('follow_up_type', 'quotation_sent')
            ->exists();
        if ($exists) {
            return;
        }

        QuotationFollowUp::query()->create([
            'quotation_id' => (int) $quotation->id,
            'customer_id' => (int) ($quotation->inquiry?->customer_id ?? 0) ?: null,
            'handled_by' => $handledBy,
            'channel' => 'system',
            'follow_up_type' => 'quotation_sent',
            'follow_up_note' => $revisionLabel . ' sent to customer. Waiting for response.',
            'follow_up_at' => now(),
            'next_follow_up_at' => $nextFollowUpAt,
            'created_by' => $handledBy,
        ]);

        $this->safeQuotationUpdate($quotation, [
            'next_follow_up_at' => $nextFollowUpAt,
            'follow_up_status' => 'follow_up_scheduled',
        ]);
    }

    public function run(?int $userId = null): int
    {
        if (! Schema::hasTable('quotations')) {
            return 0;
        }

        $processed = 0;
        Quotation::query()
            ->with('inquiry')
            ->whereIn('status', ['sent', 'pending', 'need_revalidation', 'pending_revalidation', 'revision_requested', 'under_revision'])
            ->chunkById(100, function ($quotations) use (&$processed, $userId): void {
                foreach ($quotations as $quotation) {
                    if ($userId && $this->notificationService->resolveNotificationUserId($quotation) !== $userId) {
                        continue;
                    }

                    $processed += $this->processQuotation($quotation);
                }
            });

        return $processed;
    }

    public function processQuotation(Quotation $quotation): int
    {
        $quotation->refresh();
        $status = Quotation::normalizeStatus((string) ($quotation->status ?? ''));
        if (in_array($status, self::CLOSED_STATUSES, true)) {
            return 0;
        }

        $changes = 0;
        $changes += $this->processFollowUpDue($quotation);
        $changes += $this->processNoResponseWarning($quotation);
        $changes += $this->processPendingNoResponse($quotation);
        $changes += $this->processValidityExpired($quotation);
        $changes += $this->processServiceDateRisk($quotation);
        $changes += $this->processServiceDatePassed($quotation);
        $changes += $this->processAutoStatusAfterReviewWarning($quotation);

        return $changes;
    }

    private function processFollowUpDue(Quotation $quotation): int
    {
        if (! $quotation->isStatus('sent', 'pending') || empty($quotation->next_follow_up_at) || $quotation->next_follow_up_at->isFuture()) {
            return 0;
        }

        if ($this->hasFollowedUpToday($quotation)) {
            if ((string) ($quotation->follow_up_status ?? '') !== 'followed_up') {
                $this->safeQuotationUpdate($quotation, ['follow_up_status' => 'followed_up']);
                return 1;
            }

            return 0;
        }

        $isOverdue = $quotation->next_follow_up_at->lt(now()->startOfDay());
        $notificationType = $isOverdue
            ? QuotationFollowUpNotification::TYPE_FOLLOW_UP_OVERDUE
            : QuotationFollowUpNotification::TYPE_FOLLOW_UP_DUE;
        $statusLabel = $isOverdue ? 'follow_up_overdue' : 'follow_up_due';
        $title = $isOverdue ? 'Quotation follow-up overdue' : 'Follow-up quotation due today';
        $message = $isOverdue
            ? 'Scheduled quotation follow-up was missed and now needs immediate attention.'
            : 'Scheduled quotation follow-up is due.';
        $severity = $isOverdue ? 'warning' : 'info';

        $this->safeQuotationUpdate($quotation, ['follow_up_status' => $statusLabel]);
        $this->notificationService->createForQuotation(
            $quotation,
            $notificationType,
            $title,
            $message,
            $severity,
            $quotation->next_follow_up_at
        );

        return 1;
    }

    private function processNoResponseWarning(Quotation $quotation): int
    {
        if (! $quotation->isStatus('sent', 'pending') || empty($quotation->last_sent_at) || $quotation->last_sent_at->gt(now()->subDays(3))) {
            return 0;
        }
        if (! empty($quotation->no_response_warning_at) || $this->hasFinalCustomerResponse($quotation)) {
            return 0;
        }

        $this->safeQuotationUpdate($quotation, ['no_response_warning_at' => now()]);
        $this->notificationService->createForQuotation(
            $quotation,
            QuotationFollowUpNotification::TYPE_NO_RESPONSE_WARNING,
            'No response for 3 days',
            'Review this quotation before it is moved to pending no response.',
            'warning',
            now()
        );

        return 1;
    }

    private function processPendingNoResponse(Quotation $quotation): int
    {
        if (empty($quotation->no_response_warning_at) || $quotation->no_response_warning_at->gt(now()->subDay())) {
            return 0;
        }
        if ((bool) ($quotation->auto_status_locked ?? false) || $this->hasFinalCustomerResponse($quotation)) {
            return 0;
        }
        if (! $quotation->isStatus('sent')) {
            return 0;
        }

        $this->workflowService->transition($quotation, 'pending', null, 'automation_pending_no_response');
        $this->safeQuotationUpdate($quotation, [
            'follow_up_status' => 'pending_no_response',
            'auto_status_reason' => 'no_response_after_warning',
            'auto_status_updated_at' => now(),
        ]);

        return 1;
    }

    private function processValidityExpired(Quotation $quotation): int
    {
        if (empty($quotation->validity_date) || $quotation->validity_date->toDateString() >= now()->toDateString()) {
            return 0;
        }
        if (in_array(Quotation::normalizeStatus((string) $quotation->status), self::CLOSED_STATUSES, true)) {
            return 0;
        }

        if ($this->workflowService->canTransition($quotation, Quotation::STATUS_NEED_REVALIDATION)) {
            $this->workflowService->transition($quotation, Quotation::STATUS_NEED_REVALIDATION, null, 'automation_validity_expired');
        }
        $this->safeQuotationUpdate($quotation, [
            'validation_status' => 'needs_revalidation',
            'next_action' => 'revalidate_quotation',
        ]);
        $this->notificationService->createForQuotation(
            $quotation,
            QuotationFollowUpNotification::TYPE_VALIDITY_EXPIRED,
            'Quotation validity expired',
            'Quotation validity date has passed. Please review or revalidate.',
            'warning',
            now()
        );

        return 1;
    }

    private function processServiceDateRisk(Quotation $quotation): int
    {
        if (empty($quotation->service_date) || $quotation->service_date->gt(now()->addDays(2)) || $quotation->service_date->lt(now()->startOfDay())) {
            return 0;
        }

        $this->notificationService->createForQuotation(
            $quotation,
            QuotationFollowUpNotification::TYPE_SERVICE_DATE_RISK,
            'Service date is approaching',
            'Quotation service date is within the next 2 days.',
            'warning',
            $quotation->service_date
        );

        return 1;
    }

    private function processServiceDatePassed(Quotation $quotation): int
    {
        if (empty($quotation->service_date) || $quotation->service_date->toDateString() >= now()->toDateString() || ! empty($quotation->service_date_warning_at)) {
            return 0;
        }

        $this->safeQuotationUpdate($quotation, ['service_date_warning_at' => now()]);
        $this->notificationService->createForQuotation(
            $quotation,
            QuotationFollowUpNotification::TYPE_AUTO_STATUS_REVIEW_REQUIRED,
            'Review required before auto lost/cancelled',
            'Service date has passed. Review quotation before automation changes status.',
            'danger',
            now()
        );

        return 1;
    }

    private function processAutoStatusAfterReviewWarning(Quotation $quotation): int
    {
        if (empty($quotation->service_date_warning_at) || $quotation->service_date_warning_at->gt(now()->subDay())) {
            return 0;
        }
        if ((bool) ($quotation->auto_status_locked ?? false) || $this->hasFinalCustomerResponse($quotation)) {
            return 0;
        }
        if (! $this->workflowService->canTransition($quotation, Quotation::STATUS_LOST)) {
            return 0;
        }

        $this->workflowService->transition($quotation, Quotation::STATUS_LOST, null, 'automation_service_date_passed');
        $this->safeQuotationUpdate($quotation, [
            'auto_status_reason' => 'service_date_passed_after_warning',
            'auto_status_updated_at' => now(),
        ]);

        return 1;
    }

    private function hasFinalCustomerResponse(Quotation $quotation): bool
    {
        if (! Schema::hasTable('quotation_customer_responses')) {
            return false;
        }

        return QuotationCustomerResponse::query()
            ->where('quotation_id', (int) $quotation->id)
            ->whereIn('response_status', [
                QuotationCustomerResponse::STATUS_APPROVED,
                QuotationCustomerResponse::STATUS_REVISION_REQUESTED,
                QuotationCustomerResponse::STATUS_CANCELLED,
                QuotationCustomerResponse::STATUS_REJECTED,
            ])
            ->exists();
    }

    private function hasFollowedUpToday(Quotation $quotation): bool
    {
        if (empty($quotation->last_followed_up_at)) {
            return false;
        }

        return $quotation->last_followed_up_at->isSameDay(now());
    }

    private function safeQuotationUpdate(Quotation $quotation, array $patch): void
    {
        $safePatch = [];
        foreach ($patch as $column => $value) {
            if (Schema::hasColumn('quotations', $column)) {
                $safePatch[$column] = $value;
            }
        }

        if ($safePatch !== []) {
            DB::table('quotations')->where('id', (int) $quotation->id)->update($safePatch);
            $quotation->refresh();
        }
    }
}
