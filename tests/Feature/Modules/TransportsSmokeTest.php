<?php

namespace Tests\Feature\Modules;

use App\Models\Module;
use App\Models\Transport;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TransportsSmokeTest extends ModuleSmokeTestCase
{
    public function test_transports_get_and_store_smoke(): void
    {
        Module::query()->updateOrCreate(
            ['key' => 'transports'],
            ['name' => 'Transports', 'description' => 'Smoke test module', 'is_enabled' => true]
        );

        $this->get(route('transports.index'))->assertOk();
        $this->get(route('transports.create'))->assertOk();

        $vendor = Vendor::query()->create([
            'name' => 'Smoke Transport Vendor ' . Str::upper(Str::random(4)),
            'location' => 'Ubud',
            'city' => 'Gianyar',
            'province' => 'Bali',
            'is_active' => true,
        ]);

        $name = 'Smoke Transport ' . Str::upper(Str::random(5));
        $this->post(route('transports.store'), [
            'name' => $name,
            'transport_type' => 'van',
            'vendor_id' => $vendor->id,
            'description' => 'Smoke transport description',
            'brand_model' => 'Toyota Hiace Premio',
            'seat_capacity' => 12,
            'luggage_capacity' => 8,
            'contract_rate' => 1300000,
            'publish_rate' => 1600000,
            'overtime_rate' => 120000,
            'fuel_type' => 'diesel',
            'transmission' => 'manual',
            'air_conditioned' => 1,
            'with_driver' => 1,
            'images' => [
                UploadedFile::fake()->image('transport-1.jpg'),
            ],
            'is_active' => 1,
        ])->assertRedirect(route('transports.index'));

        $transport = Transport::query()->where('name', $name)->latest()->firstOrFail();
        $this->get(route('transports.show', $transport))->assertOk();
        $this->get(route('transports.edit', $transport))->assertOk();
    }
}
