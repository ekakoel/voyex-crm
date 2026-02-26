<?php

namespace Tests\Feature\Modules;

use App\Models\Quotation;

class QuotationsSmokeTest extends ModuleSmokeTestCase
{
    public function test_quotations_get_and_store_smoke(): void
    {
        $customer = $this->createCustomer();
        $inquiry = $this->createInquiry($customer);

        $this->get(route('quotations.index'))->assertOk();
        $this->get(route('quotations.create'))->assertOk();

        $this->post(route('quotations.store'), [
            'inquiry_id' => $inquiry->id,
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

        $quotation = Quotation::query()->where('inquiry_id', $inquiry->id)->latest()->firstOrFail();
        $this->get(route('quotations.edit', $quotation))->assertOk();
    }
}

