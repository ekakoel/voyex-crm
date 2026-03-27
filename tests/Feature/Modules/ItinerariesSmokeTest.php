<?php

namespace Tests\Feature\Modules;

use App\Models\Itinerary;
use App\Models\Airport;
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

        $airport = Airport::query()->create([
            'code' => 'SMK-AP-' . Str::upper(Str::random(4)),
            'name' => 'Smoke Airport ' . Str::upper(Str::random(4)),
            'location' => 'Bandung',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'is_active' => true,
        ]);

        $title = 'Smoke Itinerary ' . Str::upper(Str::random(6));
        $this->post(route('itineraries.store'), [
            'title' => $title,
            'destination' => 'Bandung',
            'duration_days' => 2,
            'duration_nights' => 1,
            'description' => 'Smoke itinerary',
            'is_active' => 1,
            'daily_start_point_types' => [
                1 => 'airport',
                2 => 'previous_day_end',
            ],
            'daily_start_point_items' => [
                1 => $airport->id,
                2 => '',
            ],
            'daily_end_point_types' => [
                1 => 'airport',
                2 => 'airport',
            ],
            'daily_end_point_items' => [
                1 => $airport->id,
                2 => $airport->id,
            ],
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
        $this->get(route('itineraries.pdf', $itinerary))->assertOk();
    }
}
