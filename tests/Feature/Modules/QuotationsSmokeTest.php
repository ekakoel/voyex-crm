<?php

namespace Tests\Feature\Modules;

use App\Models\Itinerary;
use App\Models\Quotation;

class QuotationsSmokeTest extends ModuleSmokeTestCase
{
    public function test_quotations_get_and_store_smoke(): void
    {
        $customer = $this->createCustomer();
        $inquiry = $this->createInquiry($customer);
        $itinerary = Itinerary::query()->create([
            'inquiry_id' => $inquiry->id,
            'created_by' => auth()->id(),
            'title' => 'Smoke Itinerary For Quotation',
            'destination' => 'Bandung',
            'duration_days' => 2,
            'duration_nights' => 1,
            'status' => 'draft',
            'is_active' => true,
        ]);

        $this->get(route('quotations.index'))->assertOk();
        $this->get(route('quotations.create'))->assertOk();

        $this->post(route('quotations.store'), [
            'itinerary_id' => $itinerary->id,
            'status' => 'draft',
            'validity_date' => now()->addDays(7)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Smoke Item',
                    'qty' => 1,
                    'unit_price' => 100000,
                    'discount' => 0,
                ],
            ],
        ])->assertRedirect(route('quotations.index'));

        $quotation = Quotation::query()->where('itinerary_id', $itinerary->id)->latest()->firstOrFail();
        $this->get(route('quotations.edit', $quotation))->assertOk();
    }
}
