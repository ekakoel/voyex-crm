<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\BookingAdjustment;
use App\Models\BookingItem;
use App\Services\InvoiceService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class InvoiceServiceReconciliationTest extends TestCase
{
    public function test_compute_final_amount_from_reconciliation_uses_used_items_and_approved_adjustments(): void
    {
        $booking = new Booking();

        $usedItem = new BookingItem();
        $usedItem->status = BookingItem::STATUS_USED;
        $usedItem->total = 1_000_000;

        $notUsedItem = new BookingItem();
        $notUsedItem->status = BookingItem::STATUS_NOT_USED;
        $notUsedItem->total = 400_000;

        $addItemAdj = new BookingAdjustment();
        $addItemAdj->status = 'approved';
        $addItemAdj->type = 'add_item';
        $addItemAdj->calculated_amount = 200_000;

        $cancelFeeAdj = new BookingAdjustment();
        $cancelFeeAdj->status = 'applied';
        $cancelFeeAdj->type = 'cancellation_fee';
        $cancelFeeAdj->calculated_amount = 100_000;

        $discountAdj = new BookingAdjustment();
        $discountAdj->status = 'approved';
        $discountAdj->type = 'discount';
        $discountAdj->calculated_amount = 50_000;

        $draftAdjIgnored = new BookingAdjustment();
        $draftAdjIgnored->status = 'draft';
        $draftAdjIgnored->type = 'extra_charge';
        $draftAdjIgnored->calculated_amount = 999_999;

        $booking->setRelation('items', new Collection([$usedItem, $notUsedItem]));
        $booking->setRelation('adjustments', new Collection([$addItemAdj, $cancelFeeAdj, $discountAdj, $draftAdjIgnored]));

        $service = new InvoiceService();
        $result = $service->computeFinalAmountFromReconciliation($booking);

        $this->assertSame(1_000_000.0, (float) $result['used_items_total']);
        $this->assertSame(200_000.0, (float) $result['added_items_amount']);
        $this->assertSame(100_000.0, (float) $result['cancellation_fee_amount']);
        $this->assertSame(50_000.0, (float) $result['discount_amount']);
        $this->assertSame(1_300_000.0, (float) $result['subtotal']);
        $this->assertSame(1_250_000.0, (float) $result['total']);
    }
}

