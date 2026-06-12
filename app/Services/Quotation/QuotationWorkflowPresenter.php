<?php

namespace App\Services\Quotation;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Support\Workflow\QuotationStatusNormalizer;

class QuotationWorkflowPresenter
{
    public function __construct(
        private readonly QuotationWorkflowService $workflowService
    ) {
    }

    public function currentStageLabel(Quotation $quotation): string
    {
        return $this->label((string) ($quotation->current_stage ?: QuotationStatusNormalizer::currentStage($quotation->status)));
    }

    public function nextActionLabel(Quotation $quotation): string
    {
        return $this->label((string) ($quotation->next_action ?: 'review_quotation'));
    }

    /**
     * @return array<int, array{status:string, label:string}>
     */
    public function availableActions(Quotation $quotation): array
    {
        $current = QuotationStatus::normalize((string) ($quotation->status ?? ''));

        return collect($this->workflowService->allowedTransitions()[$current] ?? [])
            ->map(fn (string $status): array => [
                'status' => $status,
                'label' => $this->label($status),
            ])
            ->values()
            ->all();
    }

    public function badgeColor(?string $status): string
    {
        return match (QuotationStatus::normalize($status)) {
            QuotationStatus::Completed->value,
            QuotationStatus::Approved->value,
            QuotationStatus::CustomerApproved->value,
            QuotationStatus::Validated->value => 'success',
            QuotationStatus::Sent->value,
            QuotationStatus::ReadyToSend->value,
            QuotationStatus::BookingCreated->value,
            QuotationStatus::BookingInProgress->value,
            QuotationStatus::Invoiced->value,
            QuotationStatus::WaitingPayment->value,
            QuotationStatus::InOperation->value => 'info',
            QuotationStatus::PendingValidation->value,
            QuotationStatus::PendingRevalidation->value,
            QuotationStatus::UnderRevision->value,
            QuotationStatus::OperationAdjustment->value => 'warning',
            QuotationStatus::Cancelled->value,
            QuotationStatus::Lost->value,
            QuotationStatus::BookingIssue->value => 'danger',
            default => 'secondary',
        };
    }

    private function label(string $value): string
    {
        return str($value)->replace('_', ' ')->title()->toString();
    }
}
