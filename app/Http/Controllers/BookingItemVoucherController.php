<?php

namespace App\Http\Controllers;

use App\Models\BookingItem;
use App\Models\BookingItemVoucher;
use App\Services\BookingVoucherService;
use Illuminate\Http\Request;
use PDF;

class BookingItemVoucherController extends Controller
{
    public function __construct(private readonly BookingVoucherService $voucherService)
    {
    }

    public function edit(BookingItem $bookingItem)
    {
        $bookingItem->load(['booking.quotation.inquiry.customer', 'serviceable', 'voucher']);
        $voucher = $bookingItem->voucher;
        $autoStatus = $this->voucherService->resolveStatusFromBooking($bookingItem);
        $prefill = $this->voucherService->draftPayloadFromItem($bookingItem);

        return view('modules.bookings.voucher-form', compact('bookingItem', 'voucher', 'autoStatus', 'prefill'));
    }

    public function generate(BookingItem $bookingItem)
    {
        $voucher = $this->voucherService->generateOrRefresh($bookingItem);

        return redirect()
            ->route('bookings.show', $bookingItem->booking_id)
            ->with('success', ui_phrase('Voucher generated successfully: :number', ['number' => $voucher->voucher_number]));
    }

    public function upsert(Request $request, BookingItem $bookingItem)
    {
        $validated = $request->validate([
            'tour_name' => ['nullable', 'string', 'max:255'],
            'service_date' => ['nullable', 'date'],
            'service_time' => ['nullable', 'string', 'max:20'],
            'vendor_contact_name' => ['nullable', 'string', 'max:255'],
            'vendor_contact_phone' => ['nullable', 'string', 'max:100'],
            'vendor_contact_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'confirmation_code' => ['nullable', 'string', 'max:255'],
        ]);

        $voucher = $bookingItem->voucher;
        if (! $voucher) {
            $voucher = new BookingItemVoucher();
            $voucher->booking_item_id = $bookingItem->id;
            $voucher->voucher_number = $this->generateVoucherNumber();
            $voucher->created_by = auth()->id();
        }

        $voucher->fill($this->voucherService->draftPayloadFromItem($bookingItem));
        $voucher->fill($validated);
        if (! in_array((string) $voucher->status, ['used', 'cancelled'], true)) {
            $voucher->status = $this->voucherService->resolveStatusFromBooking($bookingItem);
        }
        $voucher->updated_by = auth()->id();
        if ($voucher->status === 'issued' && ! $voucher->issued_at) {
            $voucher->issued_at = now();
        }
        if ($voucher->status === 'used' && ! $voucher->used_at) {
            $voucher->used_at = now();
        }
        $voucher->source_hash = $this->voucherService->computeSourceHash($bookingItem);
        $voucher->save();

        return redirect()
            ->route('bookings.show', $bookingItem->booking_id)
            ->with('success', ui_phrase('Voucher saved successfully.'));
    }

    public function pdf(BookingItem $bookingItem)
    {
        $bookingItem->load(['booking.quotation.inquiry.customer', 'serviceable', 'voucher']);
        $voucher = $bookingItem->voucher;
        if (! $voucher) {
            return redirect()
                ->route('bookings.show', $bookingItem->booking_id)
                ->with('error', ui_phrase('Voucher is not available yet for this item.'));
        }

        $pdf = PDF::loadView('pdf.booking-item-voucher', [
            'bookingItem' => $bookingItem,
            'booking' => $bookingItem->booking,
            'quotation' => $bookingItem->booking?->quotation,
            'voucher' => $voucher,
        ])->setPaper('a5', 'portrait');

        return $pdf->download($voucher->voucher_number . '.pdf');
    }

    private function generateVoucherNumber(): string
    {
        do {
            $number = 'VCH-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (BookingItemVoucher::query()->where('voucher_number', $number)->exists());

        return $number;
    }
}
