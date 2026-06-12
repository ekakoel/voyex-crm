<?php

namespace App\Services\Booking;

use App\Enums\QuotationStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\Quotation\QuotationWorkflowService;
use App\Support\Workflow\QuotationStatusNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BookingFromQuotationService
{
    public function __construct(
        private readonly QuotationWorkflowService $workflowService
    ) {
    }

    public function createFromApprovedQuotation(Quotation $quotation, array $payload = [], ?int $actorId = null): Booking
    {
        return DB::transaction(function () use ($quotation, $payload, $actorId): Booking {
            $quotation->refresh();

            $existingBooking = $quotation->booking()->first();
            if ($existingBooking) {
                $this->workflowService->syncDimensions($quotation, $actorId, ['booking_id' => (int) $existingBooking->id]);

                return $existingBooking;
            }

            $this->assertCanCreateBooking($quotation);
            $booking = $this->createBooking($quotation, $payload);
            $this->copyQuotationItems($booking, $quotation);

            if ($this->workflowService->canTransition($quotation, QuotationStatus::ConvertedToBooking->value)) {
                $this->workflowService->transition($quotation, QuotationStatus::ConvertedToBooking->value, $actorId, 'booking_created', [
                    'booking_id' => (int) $booking->id,
                ]);
            } else {
                $this->workflowService->syncDimensions($quotation, $actorId, ['booking_id' => (int) $booking->id]);
            }

            return $booking->fresh(['items']) ?? $booking;
        });
    }

    private function assertCanCreateBooking(Quotation $quotation): void
    {
        if (! QuotationStatusNormalizer::isApproved((string) ($quotation->status ?? ''))) {
            throw ValidationException::withMessages([
                'quotation_id' => 'Booking can only be created from an approved quotation.',
            ]);
        }

        if (Schema::hasColumn('quotations', 'validation_status') && ! in_array((string) ($quotation->validation_status ?? ''), ['valid', 'validated'], true)) {
            throw ValidationException::withMessages([
                'quotation_id' => 'Quotation validation must be valid before booking.',
            ]);
        }

        if (! $quotation->items()->exists()) {
            throw ValidationException::withMessages([
                'quotation_id' => 'Quotation must have at least one item before booking.',
            ]);
        }
    }

    private function createBooking(Quotation $quotation, array $payload): Booking
    {
        $booking = new Booking();
        $booking->forceFill($this->filterColumns('bookings', [
            'booking_number' => $payload['booking_number'] ?? $this->generateBookingNumber(),
            'quotation_id' => $quotation->id,
            'travel_date' => $payload['travel_date'] ?? $this->resolveTravelDate($quotation),
            'pax_adult' => (int) ($payload['pax_adult'] ?? $quotation->pax_adult ?? 0),
            'pax_child' => (int) ($payload['pax_child'] ?? $quotation->pax_child ?? 0),
            'status' => $payload['status'] ?? 'created',
            'itinerary_snapshot' => $payload['itinerary_snapshot'] ?? null,
        ]));
        $booking->save();

        return $booking;
    }

    private function copyQuotationItems(Booking $booking, Quotation $quotation): void
    {
        if (! Schema::hasTable('booking_items') || $booking->items()->exists()) {
            return;
        }

        $quotation->loadMissing('items.serviceable');
        foreach ($quotation->items as $quotationItem) {
            $this->copyQuotationItem($booking, $quotationItem);
        }
    }

    private function copyQuotationItem(Booking $booking, QuotationItem $quotationItem): BookingItem
    {
        $qty = max(1, (int) ($quotationItem->qty ?? 1));
        $unitPrice = max(0, (float) ($quotationItem->unit_price ?? 0));
        $vendorId = (int) ($quotationItem->serviceable?->vendor_id ?? 0);

        $bookingItem = new BookingItem();
        $bookingItem->forceFill($this->filterColumns('booking_items', [
            'booking_id' => $booking->id,
            'quotation_item_id' => $quotationItem->id,
            'description' => trim((string) ($quotationItem->description ?? '')),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'total' => max(0, (float) ($quotationItem->total ?? ($qty * $unitPrice))),
            'status' => BookingItem::STATUS_ACTIVE,
            'vendor_confirmation_status' => BookingItem::VENDOR_CONFIRMATION_PENDING,
            'dispatch_status' => BookingItem::DISPATCH_PENDING,
            'serviceable_type' => $quotationItem->serviceable_type,
            'serviceable_id' => $quotationItem->serviceable_id,
            'vendor_id' => $vendorId > 0 ? $vendorId : null,
            'day_number' => $quotationItem->day_number,
            'service_date' => $quotationItem->service_date ?? $booking->travel_date,
            'sell_price' => $unitPrice,
            'contract_rate' => max(0, (float) ($quotationItem->contract_rate ?? 0)),
            'markup_type' => (string) ($quotationItem->markup_type ?? 'fixed'),
            'markup' => max(0, (float) ($quotationItem->markup ?? 0)),
            'serviceable_meta' => $quotationItem->serviceable_meta,
            'cancellation_fee' => 0,
            'cancellation_fee_calculated' => 0,
            'cancellation_fee_overridden' => false,
        ]));
        $bookingItem->save();

        return $bookingItem;
    }

    private function generateBookingNumber(): string
    {
        $prefix = 'BKG-' . now()->format('ymd');
        $sequence = 1;

        do {
            $number = $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Booking::query()->where('booking_number', $number)->exists());

        return $number;
    }

    private function resolveTravelDate(Quotation $quotation): string
    {
        return optional($quotation->service_date)->format('Y-m-d')
            ?? optional($quotation->validity_date)->format('Y-m-d')
            ?? now()->toDateString();
    }

    private function filterColumns(string $table, array $payload): array
    {
        return array_filter(
            $payload,
            static fn (string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );
    }
}
