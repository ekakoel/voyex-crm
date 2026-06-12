<?php

namespace App\Services\Quotation;

use App\Models\Quotation;
use App\Models\QuotationCustomerResponse;
use App\Models\QuotationFollowUpNotification;
use App\Support\Workflow\QuotationStatusNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class QuotationCustomerResponseService
{
    public const ALLOWED_STATUSES = [
        'ready_to_send',
        'sent',
        'revision_requested',
        'under_revision',
        'need_revalidation',
    ];

    public function __construct(
        private readonly QuotationWorkflowService $workflowService,
        private readonly QuotationFollowUpNotificationService $notificationService
    ) {
    }

    public function canRecord(Quotation $quotation): bool
    {
        return in_array(QuotationStatusNormalizer::normalize((string) ($quotation->status ?? '')), self::ALLOWED_STATUSES, true);
    }

    public function record(Quotation $quotation, array $data, ?int $actorId = null): QuotationCustomerResponse
    {
        return DB::transaction(function () use ($quotation, $data, $actorId): QuotationCustomerResponse {
            $quotation->refresh();
            if (! $this->canRecord($quotation)) {
                throw ValidationException::withMessages([
                    'status' => 'Customer response can only be recorded for active quotation before approval or closure.',
                ]);
            }

            $responseStatus = (string) ($data['response_status'] ?? QuotationCustomerResponse::STATUS_PENDING_DECISION);
            $requiresRevision = $responseStatus === QuotationCustomerResponse::STATUS_REVISION_REQUESTED;
            $responseNote = trim((string) ($data['response_note'] ?? ''));

            $quotation->loadMissing('inquiry');
            $handledBy = $this->notificationService->resolveNotificationUserId($quotation, null);

            $response = QuotationCustomerResponse::query()->create([
                'quotation_id' => (int) $quotation->id,
                'customer_id' => (int) ($quotation->inquiry?->customer_id ?? 0) ?: null,
                'handled_by' => $handledBy,
                'response_channel' => $data['response_channel'] ?? null,
                'response_status' => $responseStatus,
                'response_note' => $responseNote === '' ? null : $responseNote,
                'requires_revision' => $requiresRevision,
                'revision_type' => null,
                'revision_priority' => null,
                'requested_changes' => $requiresRevision && $responseNote !== ''
                    ? ['summary' => $responseNote]
                    : null,
                'response_at' => now(),
                'created_by' => $actorId,
            ]);

            $this->applyResponseWorkflow($quotation, $response, $actorId);

            return $response;
        });
    }

    public function markManyUsedForRevision(Quotation $quotation, array $responseIds, ?int $actorId = null): int
    {
        $responseIds = collect($responseIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($responseIds->isEmpty()) {
            throw ValidationException::withMessages([
                'customer_response_ids' => 'Please select at least one customer response to handle.',
            ]);
        }

        $query = QuotationCustomerResponse::query()
            ->where('quotation_id', (int) $quotation->id)
            ->where('requires_revision', true)
            ->where(function ($builder): void {
                $builder->where('is_used_for_revision', false)
                    ->orWhereNull('is_used_for_revision');
            })
            ->whereIn('id', $responseIds->all());

        $matched = (clone $query)->count();
        if ($matched !== $responseIds->count()) {
            throw ValidationException::withMessages([
                'customer_response_ids' => 'One or more selected customer responses are not available for this revision.',
            ]);
        }

        return (int) $query->update($this->usedForRevisionPatch($quotation, $actorId));
    }

    private function usedForRevisionPatch(Quotation $quotation, ?int $actorId = null): array
    {
        $patch = [
            'is_used_for_revision' => true,
            'used_for_revision_at' => now(),
            'used_for_revision_by' => $actorId,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('quotation_customer_responses', 'quotation_revision_id')) {
            $patch['quotation_revision_id'] = (int) $quotation->id;
        }

        return $patch;
    }

    private function applyResponseWorkflow(Quotation $quotation, QuotationCustomerResponse $response, ?int $actorId): void
    {
        $status = (string) $response->response_status;

        if ($status === QuotationCustomerResponse::STATUS_APPROVED) {
            if ($this->workflowService->canTransition($quotation, Quotation::STATUS_APPROVED)) {
                $this->markQuotationApproved($quotation, $actorId);
                $this->workflowService->transition($quotation, Quotation::STATUS_APPROVED, $actorId, 'customer_response_approved');
            } else {
                $this->syncResponseWithoutStatusTransition($quotation, $response, $actorId, 'customer_response_approved_recorded');
            }

            return;
        }

        if ($status === QuotationCustomerResponse::STATUS_CANCELLED) {
            if ($this->workflowService->canTransition($quotation, Quotation::STATUS_CANCELLED)) {
                $this->workflowService->transition($quotation, Quotation::STATUS_CANCELLED, $actorId, 'customer_response_cancelled');
            } else {
                $this->syncResponseWithoutStatusTransition($quotation, $response, $actorId, 'customer_response_cancelled_recorded');
            }

            return;
        }

        if ($status === QuotationCustomerResponse::STATUS_REJECTED) {
            if ($this->workflowService->canTransition($quotation, Quotation::STATUS_LOST)) {
                $this->workflowService->transition($quotation, Quotation::STATUS_LOST, $actorId, 'customer_response_rejected');
            } else {
                $this->syncResponseWithoutStatusTransition($quotation, $response, $actorId, 'customer_response_rejected_recorded');
            }

            return;
        }

        if ((bool) $response->requires_revision || $status === QuotationCustomerResponse::STATUS_REVISION_REQUESTED) {
            $currentStatus = QuotationStatusNormalizer::normalize((string) ($quotation->status ?? ''));
            if (in_array($currentStatus, [Quotation::STATUS_UNDER_REVISION, Quotation::STATUS_NEED_REVALIDATION], true)) {
                $this->workflowService->syncDimensions($quotation, $actorId, [
                    'action' => 'customer_response_revision_added',
                    'customer_response_id' => (int) $response->id,
                    'requested_changes_present' => trim((string) ($response->response_note ?? '')) !== '',
                ]);
            } elseif ($this->workflowService->canTransition($quotation, Quotation::STATUS_REVISION_REQUESTED)) {
                $this->workflowService->transition($quotation, Quotation::STATUS_REVISION_REQUESTED, $actorId, 'customer_response_revision_requested', [
                    'customer_response_id' => (int) $response->id,
                    'requested_changes_present' => trim((string) ($response->response_note ?? '')) !== '',
                ]);
            } else {
                $this->syncResponseWithoutStatusTransition($quotation, $response, $actorId, 'customer_response_revision_recorded');
            }
            $this->notificationService->createForQuotation(
                $quotation,
                QuotationFollowUpNotification::TYPE_RESPONSE_NEEDS_REVISION,
                'Customer requested quotation revision',
                (string) ($response->response_note ?? 'Customer requested changes.'),
                'warning',
                now(),
                (int) ($response->handled_by ?? 0) ?: null
            );
            return;
        }

        $currentStatus = Quotation::normalizeStatus((string) ($quotation->status ?? ''));
        $this->workflowService->transition($quotation, $currentStatus, $actorId, 'customer_response_recorded', [
            'customer_response_id' => (int) $response->id,
            'response_status' => $status,
        ]);

        $nextAction = $status === QuotationCustomerResponse::STATUS_NEED_MORE_INFORMATION
            ? 'provide_more_information'
            : 'follow_up_customer';

        $patch = [
            'approval_status' => 'waiting_customer_response',
            'current_stage' => 'customer_follow_up',
            'next_action' => $nextAction,
        ];
        $safePatch = [];
        foreach ($patch as $column => $value) {
            if (Schema::hasColumn('quotations', $column)) {
                $safePatch[$column] = $value;
            }
        }
        if ($safePatch !== []) {
            DB::table('quotations')->where('id', (int) $quotation->id)->update($safePatch);
        }
    }

    private function syncResponseWithoutStatusTransition(
        Quotation $quotation,
        QuotationCustomerResponse $response,
        ?int $actorId,
        string $action
    ): void {
        $this->workflowService->syncDimensions($quotation, $actorId, [
            'action' => $action,
            'customer_response_id' => (int) $response->id,
            'response_status' => (string) $response->response_status,
            'status_transition_skipped' => true,
        ]);
    }

    private function markQuotationApproved(Quotation $quotation, ?int $actorId): void
    {
        $patch = [
            'approved_at' => now(),
            'approved_by' => $actorId,
            'approval_note_by' => $actorId,
            'approval_note_at' => now(),
        ];

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
