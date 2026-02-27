<?php

namespace Tests\Feature\Modules;

use App\Models\TouristAttraction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TouristAttractionsSmokeTest extends ModuleSmokeTestCase
{
    public function test_tourist_attractions_get_and_store_smoke(): void
    {
        $this->get(route('tourist-attractions.index'))->assertOk();
        $this->get(route('tourist-attractions.create'))->assertOk();

        $name = 'Smoke Attraction ' . Str::upper(Str::random(5));
        $this->post(route('tourist-attractions.store'), [
            'name' => $name,
            'ideal_visit_minutes' => 90,
            'location' => 'Ubud',
            'city' => 'Gianyar',
            'province' => 'Bali',
            'latitude' => -8.5069,
            'longitude' => 115.2625,
            'description' => 'Smoke attraction description',
            'gallery_images' => [
                UploadedFile::fake()->image('attraction-1.jpg'),
            ],
            'is_active' => 1,
        ])->assertRedirect(route('tourist-attractions.index'));

        $attraction = TouristAttraction::query()->where('name', $name)->latest()->firstOrFail();
        $this->get(route('tourist-attractions.edit', $attraction))->assertOk();
    }
}
