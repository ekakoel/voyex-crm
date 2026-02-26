<?php

namespace Tests\Feature\Modules;

use App\Models\Booking;

class BookingsSmokeTest extends ModuleSmokeTestCase
{
    public function test_bookings_get_and_store_smoke(): void
    {
        $customer = $this->createCustomer();
        $inquiry = $this->createInquiry($customer);
        $quotation = $this->createQuotation($inquiry);

        $this->get(route('bookings.index'))->assertOk();
        $this->get(route('bookings.create'))->assertOk();

        $this->post(route('bookings.store'), [
            'quotation_id' => $quotation->id,
            'travel_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'confirmed',
            'notes' => 'Smoke booking',
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::query()->where('quotation_id', $quotation->id)->latest()->firstOrFail();
        $this->get(route('bookings.edit', $booking))->assertOk();
        $this->get(route('bookings.show', $booking))->assertOk();
    }
}

