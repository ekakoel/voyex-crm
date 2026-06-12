<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAdjustment;
use App\Models\BookingItem;
use App\Models\QuotationItem;

class BookingAdjustmentService
{
    public function __construct(private readonly AdjustmentService $adjustmentService)
    {
    }

    public function createCancellationFeeFromItem(BookingItem $bookingItem, float $feeAmount, ?string $reason = null): ?BookingAdjustment
    {
        if ($feeAmount <= 0) {
            return null;
        }

        $booking = $bookingItem->booking;
        if (! $booking) {
            return null;
        }

        $autoKey = 'cancellation_fee:item:' . (int) $bookingItem->id;
        $existing = $this->findDraftByAutoKey($booking, $autoKey);
        if ($existing) {
            return $existing;
        }

        return $this->createDraft($booking, [
            'booking_item_id' => $bookingItem->id,
            'adjustment_type' => 'cancellation_fee',
            'type' => 'cancellation_fee',
            'title' => 'Cancellation Fee - ' . trim((string) ($bookingItem->description ?? 'Item')),
            'description' => 'Auto-generated from service item cancellation with charge.',
            'reason' => trim((string) ($reason ?? '')) ?: 'Cancellation policy charge applied.',
            'amount_type' => 'fixed',
            'amount' => round($feeAmount, 2),
            'calculated_amount' => round($feeAmount, 2),
            'impact_type' => 'charge',
            'metadata' => [
                'auto_generated' => true,
                'auto_key' => $autoKey,
                'source' => 'booking_item_cancelled_with_charge',
            ],
        ]);
    }

    public function createAddItemFromQuotationItem(Booking $booking, BookingItem $bookingItem, QuotationItem $quotationItem): ?BookingAdjustment
    {
        $autoKey = 'add_item:booking_item:' . (int) $bookingItem->id;
        $existing = $this->findDraftByAutoKey($booking, $autoKey);
        if ($existing) {
            return $existing;
        }

        $amount = round(max(0, (float) ($bookingItem->total ?? 0)), 2);

        return $this->createDraft($booking, [
            'booking_item_id' => $bookingItem->id,
            'adjustment_type' => 'add_item',
            'type' => 'add_item',
            'title' => 'Additional Item - ' . trim((string) ($bookingItem->description ?? 'Item')),
            'description' => 'Auto-generated for item added after quotation approval.',
            'reason' => 'Quotation item status is added_after_approval.',
            'amount_type' => 'fixed',
            'amount' => $amount,
            'calculated_amount' => $amount,
            'impact_type' => 'charge',
            'metadata' => [
                'auto_generated' => true,
                'auto_key' => $autoKey,
                'source' => 'quotation_item_added_after_approval',
                'quotation_item_id' => (int) $quotationItem->id,
            ],
        ]);
    }

    public function createReplaceItem(Booking $booking, BookingItem $newBookingItem, QuotationItem $oldQuotationItem): ?BookingAdjustment
    {
        $autoKey = 'replace_item:new_booking_item:' . (int) $newBookingItem->id . ':old_quotation_item:' . (int) $oldQuotationItem->id;
        $existing = $this->findDraftByAutoKey($booking, $autoKey);
        if ($existing) {
            return $existing;
        }

        return $this->createDraft($booking, [
            'booking_item_id' => $newBookingItem->id,
            'adjustment_type' => 'replace_item',
            'type' => 'replace_item',
            'title' => 'Replacement Item - ' . trim((string) ($newBookingItem->description ?? 'Item')),
            'description' => 'Auto-generated for replacement item flow.',
            'reason' => 'Old quotation item #' . (int) $oldQuotationItem->id . ' replaced by quotation item #' . (int) ($newBookingItem->quotation_item_id ?? 0) . '.',
            'amount_type' => 'fixed',
            'amount' => 0,
            'calculated_amount' => 0,
            'impact_type' => 'non_financial',
            'metadata' => [
                'auto_generated' => true,
                'auto_key' => $autoKey,
                'source' => 'quotation_item_replacement',
                'old_quotation_item_id' => (int) $oldQuotationItem->id,
                'new_quotation_item_id' => (int) ($newBookingItem->quotation_item_id ?? 0),
            ],
        ]);
    }

    private function createDraft(Booking $booking, array $payload): BookingAdjustment
    {
        $payload['quotation_id'] = (int) ($booking->quotation_id ?? 0) ?: null;
        $payload['created_by'] = auth()->id();

        return $this->adjustmentService->createDraft($booking, $payload, (int) (auth()->id() ?? 0));
    }

    private function findDraftByAutoKey(Booking $booking, string $autoKey): ?BookingAdjustment
    {
        return $booking->adjustments()
            ->whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->whereJsonContains('metadata->auto_key', $autoKey)
            ->latest('id')
            ->first();
    }
}

