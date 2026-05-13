<?php

namespace App\Services;

use App\Models\BookingItem;
use App\Models\BookingItemVoucher;

class BookingVoucherService
{
    public function generateOrRefresh(BookingItem $bookingItem, bool $force = false): BookingItemVoucher
    {
        $bookingItem->loadMissing([
            'booking.quotation.inquiry.customer',
            'booking.quotation.itinerary',
            'serviceable',
            'latestBookingLog',
            'voucher',
        ]);

        $voucher = $bookingItem->voucher;
        if (! $voucher) {
            $voucher = new BookingItemVoucher();
            $voucher->booking_item_id = $bookingItem->id;
            $voucher->voucher_number = $this->generateVoucherNumber();
            $voucher->created_by = auth()->id();
        }

        if (in_array((string) $voucher->status, ['used', 'cancelled'], true) && ! $force) {
            return $voucher;
        }

        $snapshot = $this->draftPayloadFromItem($bookingItem);
        $voucher->fill($snapshot);
        $autoStatus = $this->resolveStatusFromBooking($bookingItem);
        $voucher->status = $voucher->status ?: $autoStatus;
        if ($voucher->status !== $autoStatus) {
            $voucher->status = $autoStatus;
        }
        if ($voucher->status === 'issued' && ! $voucher->issued_at) {
            $voucher->issued_at = now();
        }
        if ($voucher->status !== 'used') {
            $voucher->used_at = null;
        }
        $voucher->source_hash = $this->computeSourceHash($bookingItem);
        $voucher->updated_by = auth()->id();
        $voucher->save();

        return $voucher;
    }

    public function computeSourceHash(BookingItem $bookingItem): string
    {
        $bookingItem->loadMissing([
            'booking.quotation.inquiry.customer',
            'booking.quotation.itinerary',
            'serviceable',
        ]);

        $quotation = $bookingItem->booking?->quotation;
        $inquiry = $quotation?->inquiry;
        $customer = $inquiry?->customer;
        $itinerary = $quotation?->itinerary;

        $payload = [
            'booking_item_id' => (int) $bookingItem->id,
            'description' => (string) ($bookingItem->description ?? ''),
            'qty' => (int) ($bookingItem->qty ?? 0),
            'unit_price' => (float) ($bookingItem->unit_price ?? 0),
            'serviceable_type' => (string) ($bookingItem->serviceable_type ?? ''),
            'serviceable_id' => (int) ($bookingItem->serviceable_id ?? 0),
            'day_number' => (int) ($bookingItem->day_number ?? 0),
            'booking_number' => (string) ($bookingItem->booking?->booking_number ?? ''),
            'travel_date' => optional($bookingItem->booking?->travel_date)->format('Y-m-d'),
            'order_number' => (string) ($quotation?->order_number ?? ''),
            'quotation_number' => (string) ($quotation?->quotation_number ?? ''),
            'inquiry_number' => (string) ($inquiry?->inquiry_number ?? ''),
            'customer_name' => (string) ($customer?->name ?? ''),
            'itinerary_title' => (string) ($itinerary?->title ?? ''),
            'vendor_name' => method_exists($bookingItem->serviceable, 'vendor') ? (string) ($bookingItem->serviceable?->vendor?->name ?? '') : '',
        ];

        return hash('sha256', json_encode($payload));
    }

    public function isSourceUpdated(BookingItem $bookingItem): bool
    {
        $voucher = $bookingItem->voucher;
        if (! $voucher || ! $voucher->source_hash) {
            return false;
        }

        return $voucher->source_hash !== $this->computeSourceHash($bookingItem);
    }

    public function draftPayloadFromItem(BookingItem $bookingItem): array
    {
        return $this->buildPayloadFromSources($bookingItem);
    }

    public function resolveStatusFromBooking(BookingItem $bookingItem): string
    {
        $bookingStatus = (string) ($bookingItem->booking?->status ?? '');

        if (in_array($bookingStatus, ['rejected', 'cancelled'], true)) {
            return 'cancelled';
        }
        if (in_array($bookingStatus, ['draft', 'pending'], true)) {
            return 'draft';
        }

        return 'issued';
    }

    private function buildPayloadFromSources(BookingItem $bookingItem): array
    {
        $quotation = $bookingItem->booking?->quotation;
        $inquiry = $quotation?->inquiry;
        $customer = $inquiry?->customer;
        $itinerary = $quotation?->itinerary;

        $vendorName = null;
        $vendorPhone = null;
        $vendorEmail = null;
        if (method_exists($bookingItem->serviceable, 'vendor')) {
            $vendorName = $bookingItem->serviceable?->vendor?->name;
            $vendorPhone = $bookingItem->serviceable?->vendor?->phone;
            $vendorEmail = $bookingItem->serviceable?->vendor?->email;
        }
        $latestLog = $bookingItem->latestBookingLog;

        $customerAgentName = $this->resolveCustomerAgentName($bookingItem);
        $orderNumber = trim((string) ($quotation?->order_number ?? ''));
        $tourName = $customerAgentName !== '' && $orderNumber !== ''
            ? trim($orderNumber . ' - ' . $customerAgentName)
            : null;

        return [
            'tour_name' => $tourName,
            'service_date' => $bookingItem->booking?->travel_date,
            'service_time' => null,
            'vendor_contact_name' => $vendorName,
            'vendor_contact_phone' => $vendorPhone,
            'vendor_contact_email' => $vendorEmail,
            'pickup_location' => null,
            'notes' => trim((string) ($bookingItem->notes ?? '')) ?: null,
            'confirmation_code' => trim((string) ($latestLog?->confirmation_number ?? '')) !== ''
                ? trim((string) $latestLog?->confirmation_number)
                : strtoupper(substr(hash('sha1', (string) $bookingItem->id . '|' . (string) ($quotation?->order_number ?? '')), 0, 10)),
        ];
    }

    private function resolveCustomerAgentName(BookingItem $bookingItem): string
    {
        $quotation = $bookingItem->booking?->quotation;
        $customerName = trim((string) ($quotation?->inquiry?->customer?->name ?? ''));
        return $customerName;
    }

    private function generateVoucherNumber(): string
    {
        do {
            $number = 'VCH-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (BookingItemVoucher::query()->where('voucher_number', $number)->exists());

        return $number;
    }
}
