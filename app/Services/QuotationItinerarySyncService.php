<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class QuotationItinerarySyncService
{
    public function __construct(
        private readonly ItineraryQuotationService $itineraryQuotationService,
        private readonly QuotationValidationService $quotationValidationService
    ) {
    }

    public function syncLinkedQuotationFromItinerary(\App\Models\Itinerary $itinerary): bool
    {
        $itinerary->loadMissing('quotation.items');
        $quotation = $itinerary->quotation;
        if (! $quotation instanceof Quotation) {
            return false;
        }

        $status = (string) ($quotation->status ?? '');
        if (! in_array($status, ['draft', 'processed', 'pending'], true)) {
            return false;
        }

        $generatedItems = $this->itineraryQuotationService->buildItems($itinerary);
        $manualItems = $quotation->items
            ->filter(fn ($item): bool => (string) ($item->itinerary_item_type ?? '') === 'manual')
            ->map(function ($item): array {
                $row = [
                    'description' => (string) ($item->description ?? ''),
                    'qty' => max(1, (int) ($item->qty ?? 1)),
                    'contract_rate' => (float) ($item->contract_rate ?? 0),
                    'markup_type' => ((string) ($item->markup_type ?? 'fixed')) === 'percent' ? 'percent' : 'fixed',
                    'markup' => (float) ($item->markup ?? 0),
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'discount_type' => ((string) ($item->discount_type ?? 'fixed')) === 'percent' ? 'percent' : 'fixed',
                    'discount' => (float) ($item->discount ?? 0),
                    'itinerary_item_type' => 'manual',
                ];

                if (! empty($item->serviceable_type) && (int) ($item->serviceable_id ?? 0) > 0) {
                    $row['serviceable_type'] = (string) $item->serviceable_type;
                    $row['serviceable_id'] = (int) $item->serviceable_id;
                }
                if ((int) ($item->day_number ?? 0) > 0) {
                    $row['day_number'] = (int) $item->day_number;
                }
                $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : null;
                if (! empty($meta)) {
                    $row['serviceable_meta'] = $meta;
                }

                return $row;
            })
            ->values()
            ->all();

        $totals = $this->computeTotals(
            array_merge($generatedItems, $manualItems),
            $quotation->discount_type,
            (float) ($quotation->discount_value ?? 0)
        );

        DB::transaction(function () use ($quotation, $totals): void {
            $quotation->items()->delete();
            foreach ($totals['items'] as $item) {
                $quotation->items()->create($item);
            }

            $quotation->approvals()->delete();
            Quotation::withoutActivityLogging(function () use ($quotation, $totals): void {
                $quotation->update([
                    'status' => 'pending',
                    'sub_total' => (float) ($totals['sub_total'] ?? 0),
                    'final_amount' => (float) ($totals['final_amount'] ?? 0),
                    'approved_by' => null,
                    'approved_at' => null,
                    'approval_note' => null,
                    'approval_note_by' => null,
                    'approval_note_at' => null,
                    'validation_status' => QuotationValidationService::STATUS_PENDING,
                    'validated_at' => null,
                    'validated_by' => null,
                ]);
            });

            $quotation->refresh();
            $this->quotationValidationService->syncValidationRequirementsAndMasterRates($quotation);
        });

        return true;
    }

    private function computeTotals(array $items, ?string $discountType, float $discountValue): array
    {
        $subTotal = 0.0;
        $normalizedItems = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $contractRate = max(0, (float) ($item['contract_rate'] ?? 0));
            $markupType = ((string) ($item['markup_type'] ?? 'fixed')) === 'percent' ? 'percent' : 'fixed';
            $markup = max(0, (float) ($item['markup'] ?? 0));
            $providedUnitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $providedRate = max(0, (float) ($item['rate'] ?? 0));
            $itemType = (string) ($item['itinerary_item_type'] ?? '');
            $isManualItem = $itemType === 'manual';

            $unitPriceFromMarkup = $markupType === 'percent'
                ? ($contractRate + ($contractRate * ($markup / 100)))
                : ($contractRate + $markup);
            $unitPrice = $isManualItem ? $providedRate : $unitPriceFromMarkup;
            if ($unitPrice <= 0 && $providedUnitPrice > 0) {
                $unitPrice = $isManualItem && $qty > 0 ? ($providedUnitPrice / $qty) : $providedUnitPrice;
            }
            $unitPrice = max(0, $unitPrice);

            $discount = max(0, (float) ($item['discount'] ?? 0));
            $itemDiscountType = ((string) ($item['discount_type'] ?? 'fixed')) === 'percent' ? 'percent' : 'fixed';
            $discountAmount = $itemDiscountType === 'percent'
                ? (($qty * $unitPrice) * ($discount / 100))
                : $discount;

            $total = max(0, ($qty * $unitPrice) - $discountAmount);
            $subTotal += $total;

            $row = [
                'description' => (string) ($item['description'] ?? ''),
                'qty' => $qty,
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'unit_price' => $unitPrice,
                'discount_type' => $itemDiscountType,
                'discount' => $discount,
                'total' => $total,
            ];

            foreach (['serviceable_type', 'serviceable_id', 'day_number', 'itinerary_item_type'] as $key) {
                if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                    $row[$key] = $item[$key];
                }
            }
            if (isset($item['serviceable_meta']) && is_array($item['serviceable_meta']) && $item['serviceable_meta'] !== []) {
                $row['serviceable_meta'] = $item['serviceable_meta'];
            }

            $normalizedItems[] = $row;
        }

        $discountAmount = 0.0;
        if ($discountType === 'percent') {
            $discountAmount = $subTotal * (max(0, min(100, $discountValue)) / 100);
        } elseif ($discountType === 'fixed') {
            $discountAmount = max(0, $discountValue);
        }

        return [
            'items' => $normalizedItems,
            'sub_total' => $subTotal,
            'final_amount' => max(0, $subTotal - $discountAmount),
        ];
    }
}

