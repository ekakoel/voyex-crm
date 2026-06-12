<?php

namespace App\Http\Controllers;

use App\Models\BookingItem;
use App\Models\BookingItemVoucher;
use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\HotelRoom;
use App\Models\IslandTransfer;
use App\Models\TransportUnit;
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
        return redirect()
            ->route('bookings.edit', $bookingItem->booking_id)
            ->with('info', ui_phrase('Voucher management is available in Edit Booking page.'));
    }

    public function generate(BookingItem $bookingItem)
    {
        $this->voucherService->assertVendorConfirmed($bookingItem);
        $voucher = $this->voucherService->generateOrRefresh($bookingItem);
        $bookingItem->booking?->logActivity('voucher.generated', $bookingItem->booking, [
            'booking_item_id' => (int) $bookingItem->id,
            'voucher_number' => (string) ($voucher->voucher_number ?? ''),
            'status' => (string) ($voucher->status ?? ''),
            'revision_number' => (int) ($voucher->revision_number ?? 1),
        ]);

        return redirect()
            ->route('bookings.edit', $bookingItem->booking_id)
            ->with('success', ui_phrase('Voucher generated successfully: :number', ['number' => $voucher->voucher_number]));
    }

    public function upsert(Request $request, BookingItem $bookingItem)
    {
        $this->voucherService->assertVendorConfirmed($bookingItem);

        $validated = $request->validate([
            'tour_name' => ['nullable', 'string', 'max:255'],
            'service_date' => ['nullable', 'date'],
            'service_time' => ['nullable', 'string', 'max:20'],
            'vendor_contact_name' => ['nullable', 'string', 'max:255'],
            'vendor_contact_phone' => ['nullable', 'string', 'max:100'],
            'vendor_contact_email' => ['nullable', 'email', 'max:255'],
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
        if (! in_array((string) $voucher->status, [\App\Models\BookingItemVoucher::STATUS_USED, \App\Models\BookingItemVoucher::STATUS_CANCELLED], true)) {
            $voucher->status = $this->voucherService->resolveStatusFromBooking($bookingItem);
        }
        $voucher->updated_by = auth()->id();
        if (in_array((string) $voucher->status, [\App\Models\BookingItemVoucher::STATUS_GENERATED, \App\Models\BookingItemVoucher::STATUS_REISSUED], true) && ! $voucher->issued_at) {
            $voucher->issued_at = now();
        }
        if ($voucher->status === \App\Models\BookingItemVoucher::STATUS_USED && ! $voucher->used_at) {
            $voucher->used_at = now();
        }
        $voucher->source_hash = $this->voucherService->computeSourceHash($bookingItem);
        $voucher->save();
        $bookingItem->booking?->logActivity('voucher.updated', $bookingItem->booking, [
            'booking_item_id' => (int) $bookingItem->id,
            'voucher_number' => (string) ($voucher->voucher_number ?? ''),
            'status' => (string) ($voucher->status ?? ''),
            'revision_number' => (int) ($voucher->revision_number ?? 1),
        ]);

        return redirect()
            ->route('bookings.edit', $bookingItem->booking_id)
            ->with('success', ui_phrase('Voucher saved successfully.'));
    }

    public function reissue(BookingItem $bookingItem)
    {
        $voucher = $this->voucherService->reissue($bookingItem);
        $bookingItem->booking?->logActivity('voucher.reissued', $bookingItem->booking, [
            'booking_item_id' => (int) $bookingItem->id,
            'voucher_number' => (string) ($voucher->voucher_number ?? ''),
            'status' => (string) ($voucher->status ?? ''),
            'revision_number' => (int) ($voucher->revision_number ?? 1),
        ]);

        return redirect()
            ->route('bookings.edit', $bookingItem->booking_id)
            ->with('success', ui_phrase('Voucher reissued successfully: :number', ['number' => $voucher->voucher_number]));
    }

    public function pdf(BookingItem $bookingItem)
    {
        $bookingItem->load([
            'booking.quotation.inquiry.customer',
            'serviceable',
            'voucher',
            'latestBookingLog',
        ]);
        $bookingItem->loadMorph('serviceable', [
            Activity::class => ['vendor'],
            FoodBeverage::class => ['vendor'],
            IslandTransfer::class => ['vendor'],
            TransportUnit::class => ['vendor'],
            HotelRoom::class => ['hotel'],
        ]);
        $voucher = $bookingItem->voucher;
        if (! $voucher) {
            return redirect()
                ->route('bookings.show', $bookingItem->booking_id)
                ->with('error', ui_phrase('Voucher is not available yet for this item.'));
        }

        $preview = $this->buildVoucherPreviewData($bookingItem, $voucher);

        $pdf = PDF::loadView('pdf.booking-item-voucher', [
            'bookingItem' => $bookingItem,
            'booking' => $bookingItem->booking,
            'quotation' => $bookingItem->booking?->quotation,
            'voucher' => $voucher,
            'preview' => $preview,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($voucher->voucher_number . '.pdf');
    }

    private function generateVoucherNumber(): string
    {
        do {
            $number = 'VCH-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (BookingItemVoucher::query()->where('voucher_number', $number)->exists());

        return $number;
    }

    private function buildVoucherPreviewData(BookingItem $bookingItem, BookingItemVoucher $voucher): array
    {
        $serviceable = $bookingItem->serviceable;
        $latestLog = $bookingItem->latestBookingLog;
        $booking = $bookingItem->booking;
        $quotation = $booking?->quotation;

        $orderNumber = trim((string) ($quotation?->order_number ?? ''));
        $customerModel = $quotation?->inquiry?->customer;
        $customerName = trim((string) ($customerModel?->name ?? ''));
        $agentName = trim((string) ($customerModel?->company_name ?? ''));
        $customerOrAgentName = $agentName !== '' ? $agentName : $customerName;
        $tourName = trim($orderNumber . ' - ' . $customerOrAgentName);
        if ($tourName === '-' || $tourName === '') {
            $tourName = trim((string) ($voucher->tour_name ?? '-'));
        }

        $vendorName = trim((string) ($latestLog?->vendor_provider_item_name ?? ''));
        if ($vendorName === '') {
            $vendorName = trim((string) ($voucher->vendor_contact_name ?? ''));
        }
        if ($vendorName === '') {
            $vendorName = trim((string) ($bookingItem->description ?? '-'));
        }

        $location = '-';
        if (! $latestLog) {
            if ($serviceable instanceof HotelRoom) {
                $location = trim(implode(', ', array_filter([
                    trim((string) ($serviceable->hotel?->address ?? '')),
                    trim((string) ($serviceable->hotel?->city ?? '')),
                    trim((string) ($serviceable->hotel?->province ?? '')),
                ])));
            } elseif (method_exists($serviceable, 'vendor')) {
                $vendor = $serviceable?->vendor;
                $location = trim((string) ($vendor?->location ?? ''));
                if ($location === '') {
                    $location = trim((string) ($vendor?->address ?? ''));
                }
            }
        }
        if ($location === '') {
            $location = '-';
        }

        $toContact = trim(implode(' | ', array_filter([
            trim((string) ($latestLog?->contact_channel ?? '')),
            trim((string) ($latestLog?->contact_value ?? '')),
        ])));
        if ($toContact === '' && ! $latestLog) {
            $parts = [];
            if ($serviceable instanceof HotelRoom) {
                $parts = array_values(array_filter([
                    trim((string) ($serviceable->hotel?->phone ?? '')),
                    trim((string) ($serviceable->hotel?->email ?? '')),
                ]));
            } elseif (method_exists($serviceable, 'vendor')) {
                $vendor = $serviceable?->vendor;
                $parts = array_values(array_filter([
                    trim((string) ($vendor?->contact_phone ?? '')),
                    trim((string) ($vendor?->contact_email ?? '')),
                    trim((string) ($vendor?->website ?? '')),
                ]));
            }
            $toContact = $parts !== [] ? implode(' | ', $parts) : '-';
        }

        $serviceDate = optional($latestLog?->service_date)->format('d-M-y')
            ?? optional($voucher->service_date)->format('d-M-y')
            ?? optional($booking?->travel_date)->format('d-M-y')
            ?? '-';
        $itemLabel = trim((string) ($latestLog?->vendor_provider_item_name ?? ''));
        if ($itemLabel === '') {
            $itemLabel = trim((string) ($bookingItem->description ?? '-'));
        }
        $confirmation = trim((string) ($latestLog?->confirmation_number ?? ''));
        if ($confirmation === '') {
            $confirmation = trim((string) ($voucher->confirmation_code ?? '#'));
        }
        $qty = (int) ($bookingItem->qty ?? 0);
        if ($latestLog) {
            $qty = max(0, (int) ($latestLog->pax_adult ?? 0)) + max(0, (int) ($latestLog->pax_child ?? 0));
        }
        $contactedPerson = trim((string) ($latestLog?->contacted_person_name ?? '-'));

        return [
            'tour_name' => $tourName !== '' ? $tourName : '-',
            'vendor_name' => $vendorName !== '' ? $vendorName : '-',
            'to_location' => $location,
            'to_contact' => $toContact,
            'service_date' => $serviceDate,
            'item_label' => $itemLabel !== '' ? $itemLabel : '-',
            'confirmation' => $confirmation !== '' ? $confirmation : '#',
            'qty' => $qty,
            'contacted_person' => $contactedPerson !== '' ? $contactedPerson : '-',
            'contact_channel' => trim((string) ($latestLog?->contact_channel ?? '-')) ?: '-',
            'contact_detail' => trim((string) ($latestLog?->contact_value ?? '-')) ?: '-',
            'issue_date' => optional($voucher->issued_at)->format('d-M-y') ?? now()->format('d-M-y'),
        ];
    }
}
