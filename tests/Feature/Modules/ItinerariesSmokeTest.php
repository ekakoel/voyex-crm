<?php

namespace Tests\Feature\Modules;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Itinerary;
use App\Models\Airport;
use App\Models\TouristAttraction;
use Illuminate\Support\Str;

class ItinerariesSmokeTest extends ModuleSmokeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            VerifyCsrfToken::class,
        ]);
    }

    public function test_itinerary_store_validates_duration_day_and_night_limits(): void
    {
        $attraction = TouristAttraction::query()->create([
            'name' => 'Duration Limit Attraction ' . Str::upper(Str::random(5)),
            'ideal_visit_minutes' => 120,
            'location' => 'Bandung',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'is_active' => true,
        ]);
        $airport = Airport::query()->create([
            'code' => 'DUR-AP-' . Str::upper(Str::random(4)),
            'name' => 'Duration Limit Airport ' . Str::upper(Str::random(4)),
            'location' => 'Bandung',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'is_active' => true,
        ]);

        $payload = $this->itineraryPayload($attraction, $airport, [
            'title' => 'Duration Store Limit ' . Str::upper(Str::random(6)),
            'duration_days' => 8,
            'duration_nights' => 7,
        ]);

        $this->from(route('itineraries.create'))
            ->post(route('itineraries.store'), $payload)
            ->assertSessionHasErrors(['duration_days', 'duration_nights']);
    }

    public function test_itinerary_update_validates_duration_day_and_night_limits(): void
    {
        $attraction = TouristAttraction::query()->create([
            'name' => 'Duration Update Attraction ' . Str::upper(Str::random(5)),
            'ideal_visit_minutes' => 120,
            'location' => 'Bandung',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'is_active' => true,
        ]);
        $airport = Airport::query()->create([
            'code' => 'DUR-UP-' . Str::upper(Str::random(4)),
            'name' => 'Duration Update Airport ' . Str::upper(Str::random(4)),
            'location' => 'Bandung',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'is_active' => true,
        ]);

        $itinerary = Itinerary::query()->create([
            'title' => 'Duration Update Itinerary ' . Str::upper(Str::random(5)),
            'destination' => 'Bandung',
            'duration_days' => 2,
            'duration_nights' => 1,
            'description' => 'Duration update baseline',
            'is_active' => true,
            'status' => Itinerary::STATUS_PENDING,
            'created_by' => auth()->id(),
        ]);

        $payload = $this->itineraryPayload($attraction, $airport, [
            'title' => 'Duration Update Invalid ' . Str::upper(Str::random(6)),
            'duration_days' => 9,
            'duration_nights' => 8,
        ]);

        $this->from(route('itineraries.edit', $itinerary))
            ->put(route('itineraries.update', $itinerary), $payload)
            ->assertSessionHasErrors(['duration_days', 'duration_nights']);
    }

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
        $response = $this->post(route('itineraries.store'), $this->itineraryPayload($attraction, $airport, [
            'title' => $title,
        ]));

        $itinerary = Itinerary::query()->where('title', $title)->latest()->firstOrFail();
        $response->assertRedirect(route('itineraries.show', $itinerary));
        $this->get(route('itineraries.edit', $itinerary))->assertOk();
        $this->get(route('itineraries.show', $itinerary))->assertOk();
        $this->get(route('itineraries.pdf', $itinerary))->assertOk();
    }

    public function test_itinerary_store_allows_day_with_only_start_and_end_points(): void
    {
        $attraction = TouristAttraction::query()->create([
            'name' => 'Start End Only Attraction ' . Str::upper(Str::random(5)),
            'ideal_visit_minutes' => 120,
            'location' => 'Bandung',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'is_active' => true,
        ]);
        $airport = Airport::query()->create([
            'code' => 'SEO-AP-' . Str::upper(Str::random(4)),
            'name' => 'Start End Only Airport ' . Str::upper(Str::random(4)),
            'location' => 'Bandung',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'is_active' => true,
        ]);

        $title = 'Start End Only Itinerary ' . Str::upper(Str::random(6));
        $payload = $this->itineraryPayload($attraction, $airport, [
            'title' => $title,
            'itinerary_items' => [],
            'itinerary_activity_items' => [],
            'itinerary_food_beverage_items' => [],
            'day_start_times' => [
                1 => '08:00',
                2 => '09:00',
            ],
            'day_start_travel_minutes' => [
                1 => 30,
                2 => 20,
            ],
        ]);

        $response = $this->post(route('itineraries.store'), $payload);
        $itinerary = Itinerary::query()->where('title', $title)->latest()->firstOrFail();

        $response->assertRedirect(route('itineraries.show', $itinerary));
        $this->assertSame(0, $itinerary->touristAttractions()->count());
        $this->assertSame(0, $itinerary->itineraryActivities()->count());
        $this->assertSame(0, $itinerary->itineraryFoodBeverages()->count());
        $this->assertSame(2, $itinerary->dayPoints()->count());
        $this->get(route('itineraries.show', $itinerary))->assertOk();
        $this->get(route('itineraries.pdf', $itinerary))->assertOk();
    }

    private function itineraryPayload(TouristAttraction $attraction, Airport $airport, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Itinerary Payload ' . Str::upper(Str::random(6)),
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
        ], $overrides);
    }
}
