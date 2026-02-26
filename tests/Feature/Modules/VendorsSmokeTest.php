<?php

namespace Tests\Feature\Modules;

use App\Models\Vendor;
use Illuminate\Support\Str;

class VendorsSmokeTest extends ModuleSmokeTestCase
{
    public function test_vendors_get_and_store_smoke(): void
    {
        $this->get(route('vendors.index'))->assertOk();
        $this->get(route('vendors.create'))->assertOk();

        $name = 'Smoke Vendor ' . Str::upper(Str::random(5));
        $this->post(route('vendors.store'), [
            'name' => $name,
            'location' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'contact_name' => 'Smoke Contact',
            'contact_email' => 'smoke-vendor-' . Str::random(6) . '@example.com',
            'contact_phone' => '081298765432',
            'address' => 'Smoke Address',
            'is_active' => 1,
        ])->assertRedirect(route('vendors.index'));

        $vendor = Vendor::query()->where('name', $name)->latest()->firstOrFail();
        $this->get(route('vendors.edit', $vendor))->assertOk();
    }
}

