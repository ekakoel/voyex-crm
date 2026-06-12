<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @deprecated Controller-driven quotation revisions now use in-place versioning
 * on the same quotation row to preserve the one-to-one inquiry constraint.
 * This service is retained only for legacy tests/experiments that explicitly
 * need to clone a historical quotation snapshot.
 */
class QuotationRevisionService
{
    public function __construct(
        private readonly ActivityAuditLogger $activityAuditLogger,
        private readonly QuotationValidationService $quotationValidationService
    ) {
    }

    public function createRevisionFromQuotation(Quotation $quotation, array $payload = []): Quotation
    {
        $quotation->loadMissing('items');

        return DB::transaction(function () use ($quotation, $payload): Quotation {
            $revisionRootId = $this->resolveRevisionRootId($quotation);
            $nextRevisionNumber = $this->nextRevisionNumber($revisionRootId);
            $revisionQuotationNumber = $this->buildRevisionQuotationNumber((string) ($quotation->quotation_number ?? ''), $nextRevisionNumber);

            $revisionPayload = $this->buildRevisionPayload(
                $quotation,
                $payload,
                $revisionRootId,
                $nextRevisionNumber,
                $revisionQuotationNumber
            );

            $revision = Quotation::withoutActivityLogging(
                fn () => Quotation::query()->create($revisionPayload)
            );

            foreach ($quotation->items as $item) {
                $itemPayload = $item->only([
                    'description',
                    'qty',
                    'contract_rate',
                    'markup_type',
                    'markup',
                    'unit_price',
                    'discount_type',
                    'discount',
                    'total',
                    'serviceable_type',
                    'serviceable_id',
                    'day_number',
                    'serviceable_meta',
                    'itinerary_item_type',
                    'service_date',
                    'status',
                    'cancellation_fee_type',
                    'cancellation_fee_value',
                    'cancellation_fee_amount',
                    'cancellation_reason',
                    'actual_used_at',
                    'replaced_by_item_id',
                    'is_validation_required',
                    'is_validated',
                    'validated_at',
                    'validated_by',
                    'validation_notes',
                    'last_validated_contract_rate',
                    'last_validated_markup_type',
                    'last_validated_markup',
                ]);
                $revision->items()->create($itemPayload);
            }

            $this->markChainAsNotCurrent($revisionRootId);
            $this->markAsCurrent($revision);

            $this->quotationValidationService->syncValidationRequirementsAndMasterRates($revision);
            $revision->load('items');
            $this->activityAuditLogger->logCreated($revision, $this->buildQuotationAuditSnapshot($revision), 'Quotation Revision');

            return $revision;
        });
    }

    private function buildRevisionPayload(
        Quotation $source,
        array $payload,
        int $revisionRootId,
        int $nextRevisionNumber,
        string $revisionQuotationNumber
    ): array {
        $base = [
            'quotation_number' => $revisionQuotationNumber,
            'order_number' => $payload['order_number'] ?? $source->order_number,
            'inquiry_id' => array_key_exists('inquiry_id', $payload) ? $payload['inquiry_id'] : $source->inquiry_id,
            'itinerary_id' => $payload['itinerary_id'] ?? $source->itinerary_id,
            'service_date' => $payload['service_date'] ?? $source->service_date,
            'pax_adult' => (int) ($payload['pax_adult'] ?? $source->pax_adult ?? 0),
            'pax_child' => (int) ($payload['pax_child'] ?? $source->pax_child ?? 0),
            'status' => $payload['status'] ?? Quotation::STATUS_NEED_VALIDATION,
            'validation_status' => $payload['validation_status'] ?? QuotationValidationService::STATUS_PENDING,
            'validity_date' => $payload['validity_date'] ?? $source->validity_date,
            'sub_total' => (float) ($payload['sub_total'] ?? $source->sub_total ?? 0),
            'discount_type' => $payload['discount_type'] ?? $source->discount_type,
            'discount_value' => (float) ($payload['discount_value'] ?? $source->discount_value ?? 0),
            'final_amount' => (float) ($payload['final_amount'] ?? $source->final_amount ?? 0),
            'approved_by' => null,
            'approved_at' => null,
            'approval_note' => null,
            'approval_note_by' => null,
            'approval_note_at' => null,
            'validated_at' => null,
            'validated_by' => null,
        ];

        if (Schema::hasColumn('quotations', 'revision_of_id')) {
            $base['revision_of_id'] = $revisionRootId;
        }
        if (Schema::hasColumn('quotations', 'revision_number')) {
            $base['revision_number'] = $nextRevisionNumber;
        }
        if (Schema::hasColumn('quotations', 'is_current_revision')) {
            $base['is_current_revision'] = true;
        }
        if (Schema::hasColumn('quotations', 'revision_reason')) {
            $base['revision_reason'] = $payload['revision_reason'] ?? null;
        }
        if (Schema::hasColumn('quotations', 'created_by')) {
            $base['created_by'] = (int) (auth()->id() ?? 0) ?: null;
        }
        if (Schema::hasColumn('quotations', 'updated_by')) {
            $base['updated_by'] = (int) (auth()->id() ?? 0) ?: null;
        }

        return $base;
    }

    private function resolveRevisionRootId(Quotation $quotation): int
    {
        $revisionRootId = (int) ($quotation->revision_of_id ?? 0);

        return $revisionRootId > 0 ? $revisionRootId : (int) $quotation->id;
    }

    private function nextRevisionNumber(int $revisionRootId): int
    {
        if (! Schema::hasColumn('quotations', 'revision_number')) {
            return 1;
        }

        $maxRevision = (int) Quotation::query()
            ->where(function (Builder $query) use ($revisionRootId): void {
                $query->where('id', $revisionRootId);
                if (Schema::hasColumn('quotations', 'revision_of_id')) {
                    $query->orWhere('revision_of_id', $revisionRootId);
                }
            })
            ->max('revision_number');

        return max(1, $maxRevision + 1);
    }

    private function buildRevisionQuotationNumber(string $baseQuotationNumber, int $revisionNumber): string
    {
        $baseQuotationNumber = trim($baseQuotationNumber);
        if ($baseQuotationNumber === '') {
            $baseQuotationNumber = 'QTN-' . now()->format('ymdHis');
        }

        $rootNumber = preg_replace('/-R\d+$/', '', $baseQuotationNumber) ?: $baseQuotationNumber;
        $candidate = $rootNumber . '-R' . $revisionNumber;
        while (Quotation::query()->where('quotation_number', $candidate)->exists()) {
            $revisionNumber++;
            $candidate = $rootNumber . '-R' . $revisionNumber;
        }

        return $candidate;
    }

    private function markChainAsNotCurrent(int $revisionRootId): void
    {
        if (! Schema::hasColumn('quotations', 'is_current_revision')) {
            return;
        }

        Quotation::query()
            ->where(function (Builder $query) use ($revisionRootId): void {
                $query->where('id', $revisionRootId);
                if (Schema::hasColumn('quotations', 'revision_of_id')) {
                    $query->orWhere('revision_of_id', $revisionRootId);
                }
            })
            ->update(['is_current_revision' => false]);
    }

    private function markAsCurrent(Quotation $quotation): void
    {
        if (! Schema::hasColumn('quotations', 'is_current_revision')) {
            return;
        }

        DB::table('quotations')
            ->where('id', (int) $quotation->id)
            ->update(['is_current_revision' => true]);
        $quotation->forceFill(['is_current_revision' => true]);
    }

    private function buildQuotationAuditSnapshot(Quotation $quotation): array
    {
        return [
            'id' => (int) $quotation->id,
            'quotation_number' => (string) ($quotation->quotation_number ?? ''),
            'order_number' => (string) ($quotation->order_number ?? ''),
            'status' => (string) ($quotation->status ?? ''),
            'inquiry_id' => $quotation->inquiry_id ? (int) $quotation->inquiry_id : null,
            'itinerary_id' => $quotation->itinerary_id ? (int) $quotation->itinerary_id : null,
            'service_date' => optional($quotation->service_date)->format('Y-m-d'),
            'validity_date' => optional($quotation->validity_date)->format('Y-m-d'),
            'pax_adult' => (int) ($quotation->pax_adult ?? 0),
            'pax_child' => (int) ($quotation->pax_child ?? 0),
            'sub_total' => (float) ($quotation->sub_total ?? 0),
            'discount_type' => $quotation->discount_type ? (string) $quotation->discount_type : null,
            'discount_value' => (float) ($quotation->discount_value ?? 0),
            'final_amount' => (float) ($quotation->final_amount ?? 0),
            'revision_of_id' => $quotation->revision_of_id ? (int) $quotation->revision_of_id : null,
            'revision_number' => $quotation->revision_number ? (int) $quotation->revision_number : null,
            'is_current_revision' => (bool) ($quotation->is_current_revision ?? false),
            'items' => $quotation->items
                ->map(fn ($item): array => [
                    'description' => (string) ($item->description ?? ''),
                    'qty' => (int) ($item->qty ?? 0),
                    'contract_rate' => (float) ($item->contract_rate ?? 0),
                    'markup_type' => (string) ($item->markup_type ?? 'fixed'),
                    'markup' => (float) ($item->markup ?? 0),
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'discount_type' => (string) ($item->discount_type ?? 'fixed'),
                    'discount' => (float) ($item->discount ?? 0),
                    'total' => (float) ($item->total ?? 0),
                    'status' => (string) ($item->status ?? ''),
                ])
                ->values()
                ->all(),
        ];
    }
}
