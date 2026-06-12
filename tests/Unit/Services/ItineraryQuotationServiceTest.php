<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Services\ItineraryQuotationService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ItineraryQuotationServiceTest extends TestCase
{
    public function test_build_items_imports_zero_rate_manual_schedule_activity(): void
    {
        $activity = new Activity([
            'name' => 'Manual Snorkeling Stop',
            'adult_contract_rate' => 0,
            'adult_markup_type' => 'fixed',
            'adult_markup' => 0,
            'adult_publish_rate' => 0,
            'child_contract_rate' => 0,
            'child_markup_type' => 'fixed',
            'child_markup' => 0,
            'child_publish_rate' => 0,
        ]);
        $activity->id = 91;

        $itineraryActivity = new ItineraryActivity([
            'activity_id' => 91,
            'day_number' => 2,
            'pax' => 1,
            'pax_adult' => 1,
            'pax_child' => 0,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'travel_minutes_to_next' => 15,
            'visit_order' => 1,
        ]);
        $itineraryActivity->setRelation('activity', $activity);

        $itinerary = new Itinerary(['duration_days' => 2]);
        $itinerary->setRelation('touristAttractions', new Collection());
        $itinerary->setRelation('itineraryActivities', new Collection([$itineraryActivity]));
        $itinerary->setRelation('itineraryIslandTransfers', new Collection());
        $itinerary->setRelation('itineraryFoodBeverages', new Collection());
        $itinerary->setRelation('itineraryTransportUnits', new Collection());
        $itinerary->setRelation('dayPoints', new Collection());

        $items = (new ItineraryQuotationService())->buildItems($itinerary);

        $this->assertCount(1, $items);
        $this->assertSame('Day 2 - Activity: Manual Snorkeling Stop', $items[0]['description']);
        $this->assertSame('activity', $items[0]['itinerary_item_type']);
        $this->assertSame(0.0, (float) $items[0]['unit_price']);
        $this->assertSame(2, $items[0]['day_number']);
    }

    public function test_build_items_splits_activity_rates_by_adult_and_child_pax(): void
    {
        $activity = new Activity([
            'name' => 'Island Hopping',
            'adult_contract_rate' => 120000,
            'adult_markup_type' => 'fixed',
            'adult_markup' => 30000,
            'adult_publish_rate' => 150000,
            'child_contract_rate' => 80000,
            'child_markup_type' => 'fixed',
            'child_markup' => 20000,
            'child_publish_rate' => 100000,
        ]);
        $activity->id = 12;

        $itineraryActivity = new ItineraryActivity([
            'activity_id' => 12,
            'day_number' => 1,
            'pax' => 5,
            'pax_adult' => 3,
            'pax_child' => 2,
            'visit_order' => 2,
        ]);
        $itineraryActivity->setRelation('activity', $activity);

        $itinerary = new Itinerary(['duration_days' => 1]);
        $itinerary->setRelation('touristAttractions', new Collection());
        $itinerary->setRelation('itineraryActivities', new Collection([$itineraryActivity]));
        $itinerary->setRelation('itineraryIslandTransfers', new Collection());
        $itinerary->setRelation('itineraryFoodBeverages', new Collection());
        $itinerary->setRelation('itineraryTransportUnits', new Collection());
        $itinerary->setRelation('dayPoints', new Collection());

        $items = (new ItineraryQuotationService())->buildItems($itinerary);

        $this->assertCount(2, $items);
        $this->assertSame('adult', $items[0]['serviceable_meta']['pax_type'] ?? null);
        $this->assertSame(3, $items[0]['qty']);
        $this->assertSame(150000.0, (float) $items[0]['unit_price']);
        $this->assertSame(120000.0, (float) $items[0]['contract_rate']);

        $this->assertSame('child', $items[1]['serviceable_meta']['pax_type'] ?? null);
        $this->assertSame(2, $items[1]['qty']);
        $this->assertSame(100000.0, (float) $items[1]['unit_price']);
        $this->assertSame(80000.0, (float) $items[1]['contract_rate']);
    }
}
