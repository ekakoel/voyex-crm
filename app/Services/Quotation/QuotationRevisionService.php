<?php

namespace App\Services\Quotation;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @deprecated Use App\Services\QuotationRevisionService for controller-driven revisions.
 * This compatibility service is retained for workflow experiments and delegates
 * validation/status refresh to the central quotation services.
 */
class QuotationRevisionService
{
    public function __construct(
        private readonly QuotationValidationService $validationService,
        private readonly QuotationWorkflowService $workflowService
    ) {
    }

    public function createRevisionFromQuotation(Quotation $quotation, array $payload = [], ?int $actorId = null): Quotation
    {
        $quotation->loadMissing('items');

        return DB::transaction(function () use ($quotation, $payload, $actorId): Quotation {
            $rootId = $this->resolveRevisionRootId($quotation);
            $revisionNumber = $this->nextRevisionNumber($rootId);
            $revision = $this->createRevisionQuotation($quotation, $payload, $rootId, $revisionNumber, $actorId);

            foreach ($quotation->items as $item) {
                $this->copyItemToRevision($item, $revision);
            }

            $this->markChainAsNotCurrent($rootId);
            $this->markAsCurrent($revision);
            $this->validationService->syncValidationStatus($revision, $actorId);
            $this->workflowService->syncDimensions($revision, $actorId, [
                'source_quotation_id' => (int) $quotation->id,
                'revision_number' => $revisionNumber,
            ]);
            $this->writeRevisionLog($quotation, $revision, $revisionNumber, $payload, $actorId);

            return $revision->fresh(['items']) ?? $revision;
        });
    }

    private function createRevisionQuotation(
        Quotation $source,
        array $payload,
        int $rootId,
        int $revisionNumber,
        ?int $actorId
    ): Quotation {
        $revision = new Quotation();
        $revision->forceFill($this->filterQuotationColumns([
            'quotation_number' => $this->buildRevisionQuotationNumber((string) ($source->quotation_number ?? ''), $revisionNumber),
            'order_number' => $payload['order_number'] ?? $source->order_number,
            'inquiry_id' => array_key_exists('inquiry_id', $payload) ? $payload['inquiry_id'] : $source->inquiry_id,
            'itinerary_id' => $payload['itinerary_id'] ?? $source->itinerary_id,
            'revision_of_id' => $rootId,
            'revision_number' => $revisionNumber,
            'is_current_revision' => true,
            'revision_reason' => $payload['revision_reason'] ?? null,
            'status' => $payload['status'] ?? Quotation::STATUS_NEED_VALIDATION,
            'validation_status' => $payload['validation_status'] ?? QuotationValidationService::STATUS_PENDING,
            'validity_date' => $payload['validity_date'] ?? $source->validity_date,
            'service_date' => $payload['service_date'] ?? $source->service_date,
            'pax_adult' => (int) ($payload['pax_adult'] ?? $source->pax_adult ?? 0),
            'pax_child' => (int) ($payload['pax_child'] ?? $source->pax_child ?? 0),
            'sub_total' => (float) ($payload['sub_total'] ?? $source->sub_total ?? 0),
            'discount_type' => $payload['discount_type'] ?? $source->discount_type,
            'discount_value' => (float) ($payload['discount_value'] ?? $source->discount_value ?? 0),
            'final_amount' => (float) ($payload['final_amount'] ?? $source->final_amount ?? 0),
            'approval_note' => null,
            'approval_note_by' => null,
            'approval_note_at' => null,
            'validated_at' => null,
            'validated_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]));
        $revision->save();

        return $revision;
    }

    private function copyItemToRevision(QuotationItem $sourceItem, Quotation $revision): QuotationItem
    {
        $requiresValidation = (bool) ($sourceItem->is_validation_required ?? false);
        $isValidated = (bool) ($sourceItem->is_validated ?? false);
        $item = new QuotationItem();
        $item->forceFill($this->filterQuotationItemColumns([
            'quotation_id' => $revision->id,
            'serviceable_id' => $sourceItem->serviceable_id,
            'serviceable_type' => $sourceItem->serviceable_type,
            'description' => $sourceItem->description,
            'qty' => $sourceItem->qty,
            'contract_rate' => $sourceItem->contract_rate,
            'markup_type' => $sourceItem->markup_type,
            'markup' => $sourceItem->markup,
            'unit_price' => $sourceItem->unit_price,
            'discount_type' => $sourceItem->discount_type,
            'discount' => $sourceItem->discount,
            'day_number' => $sourceItem->day_number,
            'service_date' => $sourceItem->service_date,
            'serviceable_meta' => $sourceItem->serviceable_meta,
            'itinerary_item_type' => $sourceItem->itinerary_item_type,
            'status' => $sourceItem->status,
            'is_validation_required' => $requiresValidation,
            'is_validated' => $isValidated,
            'validated_at' => $isValidated ? $sourceItem->validated_at : null,
            'validated_by' => $isValidated ? $sourceItem->validated_by : null,
            'validation_notes' => $isValidated
                ? $sourceItem->validation_notes
                : ($requiresValidation ? 'Revision item requires validation.' : null),
            'last_validated_contract_rate' => $sourceItem->last_validated_contract_rate,
            'last_validated_markup_type' => $sourceItem->last_validated_markup_type,
            'last_validated_markup' => $sourceItem->last_validated_markup,
            'total' => $sourceItem->total,
        ]));
        $item->save();

        return $item;
    }

    private function writeRevisionLog(Quotation $source, Quotation $revision, int $version, array $payload, ?int $actorId): void
    {
        if (! Schema::hasTable('quotation_revisions')) {
            return;
        }

        $actorId = $this->validUserId($actorId);

        DB::table('quotation_revisions')->insert([
            'quotation_id' => $revision->id,
            'parent_quotation_id' => $this->resolveRevisionRootId($source),
            'created_from_revision_id' => $source->id,
            'quotation_number' => $revision->quotation_number,
            'version' => $version,
            'revision_reason' => $payload['revision_reason'] ?? null,
            'revision_requested_by' => $actorId,
            'revision_requested_at' => now(),
            'created_by' => $actorId,
            'metadata' => json_encode([
                'source_status' => (string) ($source->status ?? ''),
                'source_revision_number' => $source->revision_number ? (int) $source->revision_number : null,
            ]),
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

    private function filterQuotationColumns(array $payload): array
    {
        return array_filter(
            $payload,
            static fn (string $column): bool => Schema::hasColumn('quotations', $column),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function filterQuotationItemColumns(array $payload): array
    {
        return array_filter(
            $payload,
            static fn (string $column): bool => Schema::hasColumn('quotation_items', $column),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function resolveRevisionRootId(Quotation $quotation): int
    {
        $revisionRootId = (int) ($quotation->revision_of_id ?? 0);

        return $revisionRootId > 0 ? $revisionRootId : (int) $quotation->id;
    }

    private function nextRevisionNumber(int $rootId): int
    {
        if (! Schema::hasColumn('quotations', 'revision_number')) {
            return 1;
        }

        $maxRevision = (int) Quotation::query()
            ->where(function (Builder $query) use ($rootId): void {
                $query->where('id', $rootId);
                if (Schema::hasColumn('quotations', 'revision_of_id')) {
                    $query->orWhere('revision_of_id', $rootId);
                }
            })
            ->max('revision_number');

        return max(1, $maxRevision + 1);
    }

    private function buildRevisionQuotationNumber(string $baseQuotationNumber, int $revisionNumber): string
    {
        $baseQuotationNumber = trim($baseQuotationNumber) ?: 'QTN-' . now()->format('ymdHis');
        $rootNumber = preg_replace('/-R\d+$/', '', $baseQuotationNumber) ?: $baseQuotationNumber;
        $candidate = $rootNumber . '-R' . $revisionNumber;

        while (Quotation::query()->where('quotation_number', $candidate)->exists()) {
            $revisionNumber++;
            $candidate = $rootNumber . '-R' . $revisionNumber;
        }

        return $candidate;
    }

    private function markChainAsNotCurrent(int $rootId): void
    {
        if (! Schema::hasColumn('quotations', 'is_current_revision')) {
            return;
        }

        Quotation::query()
            ->where(function (Builder $query) use ($rootId): void {
                $query->where('id', $rootId);
                if (Schema::hasColumn('quotations', 'revision_of_id')) {
                    $query->orWhere('revision_of_id', $rootId);
                }
            })
            ->update(['is_current_revision' => false]);
    }

    private function markAsCurrent(Quotation $quotation): void
    {
        if (Schema::hasColumn('quotations', 'is_current_revision')) {
            DB::table('quotations')->where('id', (int) $quotation->id)->update(['is_current_revision' => true]);
        }
    }
}
