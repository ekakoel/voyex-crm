<?php

namespace Tests\Feature\Modules;

use App\Models\Module;
use App\Models\Transport;
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

        $code = 'TRN-' . Str::upper(Str::random(6));
        $name = 'Smoke Transport ' . Str::upper(Str::random(5));
        $this->post(route('transports.store'), [
            'code' => $code,
            'name' => $name,
            'transport_type' => 'van',
            'provider_name' => 'Smoke Transport Provider',
            'service_scope' => 'daily_tour',
            'location' => 'Ubud',
            'city' => 'Gianyar',
            'province' => 'Bali',
            'contact_name' => 'Smoke PIC',
            'contact_phone' => '081234567890',
            'description' => 'Smoke transport description',
            'gallery_images' => [
                UploadedFile::fake()->image('transport-1.jpg'),
            ],
            'units' => [
                [
                    'name' => 'Toyota Hiace Premio',
                    'vehicle_type' => 'Minibus',
                    'brand_model' => 'Toyota Hiace Premio',
                    'seat_capacity' => 12,
                    'luggage_capacity' => 8,
                    'contract_rate' => 1300000,
                    'publish_rate' => 1600000,
                    'overtime_rate' => 120000,
                    'currency' => 'IDR',
                    'fuel_type' => 'diesel',
                    'transmission' => 'manual',
                    'air_conditioned' => 1,
                    'with_driver' => 1,
                    'benefits' => 'Driver included',
                    'is_active' => 1,
                ],
            ],
            'is_active' => 1,
        ])->assertRedirect(route('transports.index'));

        $transport = Transport::query()->where('code', $code)->latest()->firstOrFail();
        $this->get(route('transports.show', $transport))->assertOk();
        $this->get(route('transports.edit', $transport))->assertOk();
    }
}
