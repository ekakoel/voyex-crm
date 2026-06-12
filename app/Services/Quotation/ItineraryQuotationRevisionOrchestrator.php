<?php

namespace App\Services\Quotation;

use App\Enums\QuotationStatus;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\ItineraryQuotationService;
use App\Services\QuotationValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ItineraryQuotationRevisionOrchestrator
{
    public function __construct(
        private readonly ItineraryQuotationService $itineraryQuotationService,
        private readonly QuotationValidationService $quotationValidationService,
        private readonly QuotationWorkflowService $quotationWorkflowService
    ) {
    }

    /**
     * Create a new itinerary revision from a quotation-linked itinerary.
     * This is additive and does not mutate the source itinerary row.
     */
    public function startRevisionFromQuotation(Quotation $quotation, array $overrides = [], ?int $userId = null): Itinerary
    {
        $quotation->loadMissing('itinerary');
        $source = $quotation->itinerary;
        if (! $source) {
            throw new \RuntimeException('Cannot start itinerary revision without linked itinerary.');
        }

        return DB::transaction(function () use ($source, $quotation, $overrides, $userId): Itinerary {
            $rootId = (int) ($source->revision_of_id ?: $source->id);
            $nextRevision = $this->nextItineraryRevisionNumber($rootId);

            $payload = array_merge($source->only([
                'inquiry_id',
                'created_by',
                'title',
                'destination',
                'destination_id',
                'arrival_transport_id',
                'departure_transport_id',
                'duration_days',
                'duration_nights',
                'description',
                'itinerary_include',
                'itinerary_exclude',
                'term_conditions',
                'is_active',
                'status',
            ]), [
                'status' => $overrides['status'] ?? 'revised',
                'revision_of_id' => $rootId,
                'revision_number' => $nextRevision,
                'revision_reason' => $overrides['revision_reason'] ?? 'quotation_revision',
                'revised_from_quotation_id' => (int) $quotation->id,
            ]);

            if (Schema::hasColumn('itineraries', 'created_by') && $userId) {
                $payload['created_by'] = $userId;
            }

            /** @var Itinerary $revision */
            $revision = Itinerary::query()->create($payload);
            $this->copyItineraryRelations($source, $revision);

            return $revision->fresh();
        });
    }

    /**
     * Sync quotation items from revised itinerary.
     * Existing validated items stay validated when the generated signature is unchanged.
     */
    public function finalizeItineraryRevision(Itinerary $itineraryRevision, Quotation $quotation, int $userId): array
    {
        return DB::transaction(function () use ($itineraryRevision, $quotation, $userId): array {
            $quotation->loadMissing('items');

            $generatedItems = collect($this->itineraryQuotationService->buildItems($itineraryRevision));
            $existingBySignature = $quotation->items->keyBy(fn (QuotationItem $item): string => $this->signatureFromQuotationItem($item));

            $keepIds = [];
            $newOrChanged = 0;

            foreach ($generatedItems as $generated) {
                $signature = $this->signatureFromGeneratedItem((array) $generated);
                /** @var QuotationItem|null $matched */
                $matched = $existingBySignature->get($signature);

                if ($matched) {
                    $keepIds[] = (int) $matched->id;
                    $matched->fill([
                        'description' => (string) ($generated['description'] ?? $matched->description),
                        'qty' => (int) ($generated['qty'] ?? $matched->qty),
                        'contract_rate' => (float) ($generated['contract_rate'] ?? $matched->contract_rate),
                        'markup_type' => (string) ($generated['markup_type'] ?? $matched->markup_type),
                        'markup' => (float) ($generated['markup'] ?? $matched->markup),
                        'unit_price' => (float) ($generated['unit_price'] ?? $matched->unit_price),
                        'discount_type' => (string) ($generated['discount_type'] ?? $matched->discount_type),
                        'discount' => (float) ($generated['discount'] ?? $matched->discount),
                        'total' => (float) ($generated['total'] ?? $matched->total),
                        'day_number' => $generated['day_number'] ?? $matched->day_number,
                        'serviceable_meta' => $generated['serviceable_meta'] ?? $matched->serviceable_meta,
                        'itinerary_item_type' => $generated['itinerary_item_type'] ?? $matched->itinerary_item_type,
                    ]);
                    $matched->save();
                    continue;
                }

                $newOrChanged++;
                $newItem = $quotation->items()->create([
                    'description' => (string) ($generated['description'] ?? ''),
                    'qty' => (int) ($generated['qty'] ?? 1),
                    'contract_rate' => (float) ($generated['contract_rate'] ?? 0),
                    'markup_type' => (string) ($generated['markup_type'] ?? 'fixed'),
                    'markup' => (float) ($generated['markup'] ?? 0),
                    'unit_price' => (float) ($generated['unit_price'] ?? 0),
                    'discount_type' => (string) ($generated['discount_type'] ?? 'fixed'),
                    'discount' => (float) ($generated['discount'] ?? 0),
                    'total' => (float) ($generated['total'] ?? 0),
                    'serviceable_type' => $generated['serviceable_type'] ?? null,
                    'serviceable_id' => $generated['serviceable_id'] ?? null,
                    'day_number' => $generated['day_number'] ?? null,
                    'serviceable_meta' => $generated['serviceable_meta'] ?? null,
                    'itinerary_item_type' => $generated['itinerary_item_type'] ?? null,
                    'is_validation_required' => true,
                    'is_validated' => false,
                    'validated_at' => null,
                    'validated_by' => null,
                    'validation_notes' => null,
                ]);
                $keepIds[] = (int) $newItem->id;
            }

            // Mark removed items instead of hard delete for traceability.
            $quotation->items()
                ->whereNotIn('id', $keepIds)
                ->update([
                    'status' => 'replaced',
                    'is_validated' => false,
                    'validated_at' => null,
                    'validated_by' => null,
                ]);

            $this->quotationValidationService->syncValidationRequirementsAndMasterRates($quotation->fresh());

            $targetStatus = $newOrChanged > 0
                ? QuotationStatus::PendingRevalidation->value
                : QuotationStatus::ReadyToSend->value;

            if ($this->quotationWorkflowService->canTransition($quotation->fresh(), $targetStatus)) {
                $this->quotationWorkflowService->transition($quotation->fresh(), $targetStatus, $userId, 'itinerary_revision_sync');
            } else {
                $this->quotationWorkflowService->syncDimensions($quotation->fresh(), $userId, [
                    'action' => 'itinerary_revision_sync',
                    'status' => Quotation::normalizeStatus((string) ($quotation->status ?? 'draft')),
                ]);
            }

            return [
                'quotation_id' => (int) $quotation->id,
                'itinerary_revision_id' => (int) $itineraryRevision->id,
                'new_or_changed_items' => $newOrChanged,
                'kept_items' => count($keepIds),
            ];
        });
    }

    private function nextItineraryRevisionNumber(int $rootId): int
    {
        $max = (int) Itinerary::query()
            ->where(function ($query) use ($rootId): void {
                $query->where('id', $rootId)
                    ->orWhere('revision_of_id', $rootId);
            })
            ->max('revision_number');

        return max(1, $max + 1);
    }

    private function copyItineraryRelations(Itinerary $source, Itinerary $target): void
    {
        $source->loadMissing([
            'touristAttractions',
            'itineraryActivities',
            'itineraryIslandTransfers',
            'itineraryFoodBeverages',
            'itineraryTransportUnits',
            'dayPoints',
            'inquiryReferences',
        ]);

        // Preserve inquiry references.
        if ($source->inquiryReferences->isNotEmpty()) {
            $target->inquiryReferences()->sync(
                $source->inquiryReferences->pluck('id')->all()
            );
        }

        // Duplicate child tables.
        foreach ($source->itineraryActivities as $row) {
            $target->itineraryActivities()->create($this->sanitizedChildPayload($row->getAttributes()));
        }
        foreach ($source->itineraryIslandTransfers as $row) {
            $target->itineraryIslandTransfers()->create($this->sanitizedChildPayload($row->getAttributes()));
        }
        foreach ($source->itineraryFoodBeverages as $row) {
            $target->itineraryFoodBeverages()->create($this->sanitizedChildPayload($row->getAttributes()));
        }
        foreach ($source->itineraryTransportUnits as $row) {
            $target->itineraryTransportUnits()->create($this->sanitizedChildPayload($row->getAttributes()));
        }
        foreach ($source->dayPoints as $row) {
            $target->dayPoints()->create($this->sanitizedChildPayload($row->getAttributes()));
        }

        // Duplicate attraction pivots.
        $pivotPayload = [];
        foreach ($source->touristAttractions as $attraction) {
            $pivotPayload[(int) $attraction->id] = [
                'day_number' => $attraction->pivot->day_number ?? null,
                'start_time' => $attraction->pivot->start_time ?? null,
                'end_time' => $attraction->pivot->end_time ?? null,
                'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next ?? null,
                'visit_order' => $attraction->pivot->visit_order ?? null,
            ];
        }
        if ($pivotPayload !== []) {
            $target->touristAttractions()->sync($pivotPayload);
        }
    }

    private function signatureFromGeneratedItem(array $item): string
    {
        $meta = is_array($item['serviceable_meta'] ?? null) ? $item['serviceable_meta'] : [];
        return implode('|', [
            (string) ($item['serviceable_type'] ?? ''),
            (string) ($item['serviceable_id'] ?? ''),
            (string) ($item['itinerary_item_type'] ?? ''),
            (string) ($item['day_number'] ?? ''),
            (string) ($meta['visit_order'] ?? ''),
            (string) ($meta['start_time'] ?? ''),
            (string) ($meta['pax_type'] ?? ''),
        ]);
    }

    private function signatureFromQuotationItem(QuotationItem $item): string
    {
        $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
        return implode('|', [
            (string) ($item->serviceable_type ?? ''),
            (string) ($item->serviceable_id ?? ''),
            (string) ($item->itinerary_item_type ?? ''),
            (string) ($item->day_number ?? ''),
            (string) ($meta['visit_order'] ?? ''),
            (string) ($meta['start_time'] ?? ''),
            (string) ($meta['pax_type'] ?? ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizedChildPayload(array $payload): array
    {
        unset(
            $payload['id'],
            $payload['itinerary_id'],
            $payload['created_at'],
            $payload['updated_at'],
            $payload['deleted_at']
        );

        return $payload;
    }
}
