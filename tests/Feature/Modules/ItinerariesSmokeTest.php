<?php

namespace Tests\Feature\Modules;

use App\Models\Itinerary;
use App\Models\TouristAttraction;
use Illuminate\Support\Str;

class ItinerariesSmokeTest extends ModuleSmokeTestCase
{
    public function test_itineraries_get_and_store_smoke(): void
    {
        $attraction = TouristAttraction::query()->create([
            'name' => 'Smoke Itinerary Attraction ' . Str::upper(Str::random(5)),
            'ideal_visit_minutes' => 120,
            'location' => 'Bandung',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'is_active' => true,
        ]);

        $this->get(route('itineraries.index'))->assertOk();
        $this->get(route('itineraries.create'))->assertOk();

        $title = 'Smoke Itinerary ' . Str::upper(Str::random(6));
        $this->post(route('itineraries.store'), [
            'title' => $title,
            'duration_days' => 2,
            'description' => 'Smoke itinerary',
            'is_active' => 1,
            'itinerary_items' => [
                [
                    'tourist_attraction_id' => $attraction->id,
                    'day_number' => 1,
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'travel_minutes_to_next' => 30,
                    'visit_order' => 1,
                ],
            ],
        ])->assertRedirect(route('itineraries.index'));

        $itinerary = Itinerary::query()->where('title', $title)->latest()->firstOrFail();
        $this->get(route('itineraries.edit', $itinerary))->assertOk();
        $this->get(route('itineraries.show', $itinerary))->assertOk();
    }
}

