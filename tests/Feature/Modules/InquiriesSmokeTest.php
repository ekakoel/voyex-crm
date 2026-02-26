<?php

namespace Tests\Feature\Modules;

use App\Models\Inquiry;

class InquiriesSmokeTest extends ModuleSmokeTestCase
{
    public function test_inquiries_get_and_store_smoke(): void
    {
        $customer = $this->createCustomer();

        $this->get(route('inquiries.index'))->assertOk();
        $this->get(route('inquiries.create'))->assertOk();

        $this->post(route('inquiries.store'), [
            'customer_id' => $customer->id,
            'source' => 'website',
            'status' => 'new',
            'priority' => 'normal',
            'notes' => 'Smoke inquiry note',
            'reminder_enabled' => 1,
        ])->assertRedirect(route('inquiries.index'));

        $inquiry = Inquiry::query()->where('customer_id', $customer->id)->latest()->firstOrFail();
        $this->get(route('inquiries.edit', $inquiry))->assertOk();
        $this->get(route('inquiries.show', $inquiry))->assertOk();
    }
}
