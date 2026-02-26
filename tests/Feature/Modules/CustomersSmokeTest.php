<?php

namespace Tests\Feature\Modules;

use App\Models\Customer;
use Illuminate\Support\Str;

class CustomersSmokeTest extends ModuleSmokeTestCase
{
    public function test_customers_get_and_store_smoke(): void
    {
        $this->get(route('customers.index'))->assertOk();
        $this->get(route('customers.create'))->assertOk();

        $code = 'SMK-CUST-' . strtoupper(Str::random(8));
        $this->post(route('customers.store'), [
            'code' => $code,
            'name' => 'Smoke Customer',
            'email' => 'smoke-customer-' . Str::random(8) . '@example.com',
            'phone' => '081234567890',
            'address' => 'Smoke Address',
            'country' => 'Indonesia',
            'customer_type' => 'individual',
        ])->assertRedirect(route('customers.index'));

        $customer = Customer::query()->where('code', $code)->firstOrFail();
        $this->get(route('customers.edit', $customer))->assertOk();
    }
}

