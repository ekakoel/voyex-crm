<?php

namespace App\Services;

use App\Models\BookingItem;
use App\Models\BookingItemVoucher;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingVoucherService
{
    public function generateOrRefresh(BookingItem $bookingItem, bool $force = false): BookingItemVoucher
    {
        $this->assertVendorConfirmed($bookingItem);

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
            $voucher->revision_number = 1;
            $voucher->created_by = auth()->id();
        }

        if (in_array((string) $voucher->status, [BookingItemVoucher::STATUS_USED, BookingItemVoucher::STATUS_CANCELLED], true) && ! $force) {
            return $voucher;
        }

        if ($voucher->exists && $this->isSourceUpdated($bookingItem) && ! $force) {
            throw ValidationException::withMessages([
                'booking_item_id' => ui_phrase('Voucher source has changed. Please reissue voucher to apply latest data.'),
            ]);
        }

        $snapshot = $this->draftPayloadFromItem($bookingItem);
        $voucher->fill($snapshot);
        $autoStatus = $this->resolveStatusFromBooking($bookingItem);
        $voucher->status = $voucher->status ?: $autoStatus;
        if ($voucher->status !== $autoStatus) {
            $voucher->status = $autoStatus;
        }
        if (in_array((string) $voucher->status, [BookingItemVoucher::STATUS_GENERATED, BookingItemVoucher::STATUS_REISSUED], true) && ! $voucher->issued_at) {
            $voucher->issued_at = now();
        }
        if ($voucher->status !== BookingItemVoucher::STATUS_USED) {
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

    public function assertVendorConfirmed(BookingItem $bookingItem): void
    {
        if (! $bookingItem->isVendorConfirmed()) {
            throw ValidationException::withMessages([
                'booking_item_id' => ui_phrase('Voucher can only be generated after vendor confirmation.'),
            ]);
        }
    }

    public function resolveStatusFromBooking(BookingItem $bookingItem): string
    {
        $bookingStatus = (string) ($bookingItem->booking?->status ?? '');

        if (in_array($bookingStatus, ['cancelled'], true)) {
            return BookingItemVoucher::STATUS_CANCELLED;
        }
        if (in_array($bookingStatus, ['pending_confirmation', 'awaiting_dp', 'awaiting_balance'], true)) {
            return BookingItemVoucher::STATUS_DRAFT;
        }

        return BookingItemVoucher::STATUS_GENERATED;
    }

    public function reissue(BookingItem $bookingItem): BookingItemVoucher
    {
        $this->assertVendorConfirmed($bookingItem);
        $bookingItem->loadMissing(['voucher']);

        $voucher = $this->generateOrRefresh($bookingItem, true);
        $voucher->revision_number = max(1, (int) ($voucher->revision_number ?? 1)) + 1;
        $voucher->voucher_number = $this->buildReissuedVoucherNumber((string) ($voucher->voucher_number ?? ''), (int) $voucher->revision_number);
        $voucher->status = BookingItemVoucher::STATUS_REISSUED;
        $voucher->issued_at = now();
        $voucher->updated_by = auth()->id();
        $voucher->save();

        return $voucher;
    }

    private function buildPayloadFromSources(BookingItem $bookingItem): array
    {
        $quotation = $bookingItem->booking?->quotation;
        $inquiry = $quotation?->inquiry;
        $customer = $inquiry?->customer;
        $itinerary = $quotation?->itinerary;

        $latestLog = $bookingItem->latestBookingLog;
        $vendorName = trim((string) ($latestLog?->vendor_provider_item_name ?? ''));
        $vendorPhone = null;
        $vendorEmail = null;
        $channel = strtolower(trim((string) ($latestLog?->contact_channel ?? '')));
        $contactValue = trim((string) ($latestLog?->contact_value ?? ''));
        if ($contactValue !== '') {
            if ($channel === 'email') {
                $vendorEmail = $contactValue;
            } else {
                $vendorPhone = $contactValue;
            }
        }

        $customerAgentName = $this->resolveCustomerAgentName($bookingItem);
        $orderNumber = trim((string) ($quotation?->order_number ?? ''));
        $tourName = $customerAgentName !== '' && $orderNumber !== ''
            ? trim($orderNumber . ' - ' . $customerAgentName)
            : null;

        return [
            'tour_name' => $tourName,
            'service_date' => $latestLog?->service_date ?? $bookingItem->booking?->travel_date,
            'service_time' => null,
            'vendor_contact_name' => $vendorName,
            'vendor_contact_phone' => $vendorPhone,
            'vendor_contact_email' => $vendorEmail,
            'pickup_location' => null,
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

    private function buildReissuedVoucherNumber(string $currentNumber, int $revisionNumber): string
    {
        $base = trim($currentNumber);
        if ($base === '') {
            return $this->generateVoucherNumber();
        }

        $base = preg_replace('/-R\d+$/', '', $base) ?: $base;
        $candidate = $base . '-R' . $revisionNumber;
        while (BookingItemVoucher::query()->where('voucher_number', $candidate)->exists()) {
            $revisionNumber++;
            $candidate = $base . '-R' . $revisionNumber;
        }

        return Str::upper($candidate);
    }
}
