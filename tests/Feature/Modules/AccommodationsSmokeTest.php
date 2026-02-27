<?php

namespace Tests\Feature\Modules;

use App\Models\Accommodation;
use App\Models\Module;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AccommodationsSmokeTest extends ModuleSmokeTestCase
{
    public function test_accommodations_get_and_store_smoke(): void
    {
        Module::query()->updateOrCreate(
            ['key' => 'accommodations'],
            ['name' => 'Accommodations', 'description' => 'Smoke test module', 'is_enabled' => true]
        );

        $this->get(route('accommodations.index'))->assertOk();
        $this->get(route('accommodations.create'))->assertOk();

        $code = 'ACC-' . Str::upper(Str::random(6));
        $name = 'Smoke Accommodation ' . Str::upper(Str::random(5));
        $this->post(route('accommodations.store'), [
            'code' => $code,
            'name' => $name,
            'category' => 'hotel',
            'star_rating' => 4,
            'location' => 'Kuta',
            'city' => 'Badung',
            'province' => 'Bali',
            'contact_name' => 'Smoke PIC',
            'contact_phone' => '08123456789',
            'description' => 'Smoke accommodation description',
            'gallery_images' => [
                UploadedFile::fake()->image('acc-1.jpg'),
            ],
            'rooms' => [
                [
                    'name' => 'Deluxe Garden',
                    'room_type' => 'Deluxe',
                    'bed_type' => 'King',
                    'view_type' => 'Garden',
                    'max_occupancy' => 2,
                    'contract_rate' => 950000,
                    'publish_rate' => 1200000,
                    'currency' => 'IDR',
                    'meal_plan' => 'breakfast',
                    'amenities' => 'WiFi, AC, TV',
                    'benefits' => 'Free breakfast',
                    'is_refundable' => 1,
                    'quantity_available' => 10,
                    'is_active' => 1,
                ],
            ],
            'is_active' => 1,
        ])->assertRedirect(route('accommodations.index'));

        $accommodation = Accommodation::query()->where('code', $code)->latest()->firstOrFail();
        $this->get(route('accommodations.show', $accommodation))->assertOk();
        $this->get(route('accommodations.edit', $accommodation))->assertOk();
    }
}
