<?php

namespace App\Services;

use App\Models\AccommodationRoom;
use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\Itinerary;
use App\Models\TouristAttraction;
use App\Models\TransportUnit;

class ItineraryQuotationService
{
    /**
     * @return array<int, array{description:string, qty:int, unit_price:float, discount:float, serviceable_type?:string, serviceable_id?:int, day_number?:int, serviceable_meta?:array, itinerary_item_type?:string}>
     */
    public function buildItems(Itinerary $itinerary): array
    {
        $itinerary->loadMissing([
            'touristAttractions:id,name,entrance_fee_per_pax,other_fee_per_pax,other_fee_label',
            'itineraryActivities.activity:id,name,agent_price',
            'itineraryFoodBeverages.foodBeverage:id,name,agent_price',
            'itineraryTransportUnits.transportUnit:id,name,vehicle_type,publish_rate',
            'dayPoints.endAccommodationRoom:id,accommodation_id,name,room_type,publish_rate',
            'dayPoints.endAccommodation:id,name',
        ]);

        $dayRows = [];

        foreach ($itinerary->itineraryTransportUnits as $transportItem) {
            $unit = $transportItem->transportUnit;
            if (! $unit) {
                continue;
            }
            $day = (int) ($transportItem->day_number ?? 0);
            $label = 'Transport: ' . $unit->name;
            if ($unit->vehicle_type) {
                $label .= ' (' . $unit->vehicle_type . ')';
            }
            $dayRows[] = [
                'day' => $day,
                'type_order' => 0,
                'item' => $this->makeItem(
                    $this->dayPrefix($day) . $label,
                    1,
                    (float) ($unit->publish_rate ?? 0),
                    0,
                    TransportUnit::class,
                    (int) $unit->id,
                    $day,
                    [
                        'day_number' => $day,
                    ],
                    'transport_day'
                ),
            ];
        }

        foreach ($itinerary->touristAttractions as $attraction) {
            $day = (int) ($attraction->pivot->day_number ?? 0);
            $price = (float) ($attraction->entrance_fee_per_pax ?? 0) + (float) ($attraction->other_fee_per_pax ?? 0);
            $dayRows[] = [
                'day' => $day,
                'type_order' => 1,
                'item' => $this->makeItem(
                    $this->dayPrefix($day) . 'Attraction: ' . $attraction->name,
                    1,
                    $price,
                    0,
                    TouristAttraction::class,
                    (int) $attraction->id,
                    $day,
                    [
                        'day_number' => $day,
                        'start_time' => $this->normalizeTime($attraction->pivot->start_time ?? null),
                        'end_time' => $this->normalizeTime($attraction->pivot->end_time ?? null),
                        'travel_minutes_to_next' => $this->normalizeInt($attraction->pivot->travel_minutes_to_next ?? null),
                        'visit_order' => $this->normalizeInt($attraction->pivot->visit_order ?? null),
                    ],
                    'attraction'
                ),
            ];
        }

        foreach ($itinerary->itineraryActivities as $activityItem) {
            $activity = $activityItem->activity;
            if (! $activity) {
                continue;
            }
            $day = (int) ($activityItem->day_number ?? 0);
            $qty = max(1, (int) ($activityItem->pax ?? 1));
            $price = (float) ($activity->agent_price ?? 0);
            $dayRows[] = [
                'day' => $day,
                'type_order' => 2,
                'item' => $this->makeItem(
                    $this->dayPrefix($day) . 'Activity: ' . $activity->name,
                    $qty,
                    $price,
                    0,
                    Activity::class,
                    (int) $activity->id,
                    $day,
                    [
                        'day_number' => $day,
                        'pax' => $qty,
                        'start_time' => $this->normalizeTime($activityItem->start_time ?? null),
                        'end_time' => $this->normalizeTime($activityItem->end_time ?? null),
                        'travel_minutes_to_next' => $this->normalizeInt($activityItem->travel_minutes_to_next ?? null),
                        'visit_order' => $this->normalizeInt($activityItem->visit_order ?? null),
                    ],
                    'activity'
                ),
            ];
        }

        foreach ($itinerary->itineraryFoodBeverages as $foodItem) {
            $food = $foodItem->foodBeverage;
            if (! $food) {
                continue;
            }
            $day = (int) ($foodItem->day_number ?? 0);
            $qty = max(1, (int) ($foodItem->pax ?? 1));
            $price = (float) ($food->agent_price ?? 0);
            $dayRows[] = [
                'day' => $day,
                'type_order' => 3,
                'item' => $this->makeItem(
                    $this->dayPrefix($day) . 'F&B: ' . $food->name,
                    $qty,
                    $price,
                    0,
                    FoodBeverage::class,
                    (int) $food->id,
                    $day,
                    [
                        'day_number' => $day,
                        'pax' => $qty,
                        'start_time' => $this->normalizeTime($foodItem->start_time ?? null),
                        'end_time' => $this->normalizeTime($foodItem->end_time ?? null),
                        'travel_minutes_to_next' => $this->normalizeInt($foodItem->travel_minutes_to_next ?? null),
                        'visit_order' => $this->normalizeInt($foodItem->visit_order ?? null),
                    ],
                    'fnb'
                ),
            ];
        }

        foreach ($itinerary->dayPoints as $dayPoint) {
            if (($dayPoint->end_point_type ?? null) !== 'accommodation') {
                continue;
            }
            $room = $dayPoint->endAccommodationRoom;
            if (! $room) {
                continue;
            }
            $day = (int) ($dayPoint->day_number ?? 0);
            $accommodationName = $dayPoint->endAccommodation?->name ?: 'Accommodation';
            $label = 'Accommodation: ' . $accommodationName;
            if ($room->name) {
                $label .= ' - ' . $room->name;
            }
            if ($room->room_type) {
                $label .= ' (' . $room->room_type . ')';
            }
            $qty = max(1, (int) ($dayPoint->end_accommodation_room_qty ?? 1));
            $dayRows[] = [
                'day' => $day,
                'type_order' => 4,
                'item' => $this->makeItem(
                    $this->dayPrefix($day) . $label,
                    $qty,
                    (float) ($room->publish_rate ?? 0),
                    0,
                    AccommodationRoom::class,
                    (int) $room->id,
                    $day,
                    [
                        'day_number' => $day,
                        'end_accommodation_id' => $this->normalizeInt($dayPoint->end_accommodation_id ?? null),
                        'end_accommodation_room_id' => (int) $room->id,
                        'end_accommodation_room_qty' => $qty,
                        'end_point_type' => (string) ($dayPoint->end_point_type ?? 'accommodation'),
                    ],
                    'accommodation_day_end'
                ),
            ];
        }

        usort($dayRows, function (array $a, array $b): int {
            if ($a['day'] !== $b['day']) {
                return $a['day'] <=> $b['day'];
            }
            return $a['type_order'] <=> $b['type_order'];
        });

        $items = array_values(array_map(fn (array $row) => $row['item'], $dayRows));

        return array_values(array_filter($items, function (array $item): bool {
            return trim((string) ($item['description'] ?? '')) !== '';
        }));
    }

    private function dayPrefix(int $day): string
    {
        return $day > 0 ? 'Day ' . $day . ' - ' : '';
    }

    /**
     * @return array{description:string, qty:int, unit_price:float, discount:float, serviceable_type?:string, serviceable_id?:int, day_number?:int, serviceable_meta?:array, itinerary_item_type?:string}
     */
    private function makeItem(
        string $description,
        int $qty = 1,
        float $unitPrice = 0,
        float $discount = 0,
        ?string $serviceableType = null,
        ?int $serviceableId = null,
        ?int $dayNumber = null,
        ?array $serviceableMeta = null,
        ?string $itineraryItemType = null
    ): array
    {
        $item = [
            'description' => trim($description),
            'qty' => max(1, $qty),
            'unit_price' => max(0, $unitPrice),
            'discount' => max(0, $discount),
        ];
        if ($serviceableType && $serviceableId) {
            $item['serviceable_type'] = $serviceableType;
            $item['serviceable_id'] = $serviceableId;
        }
        if ($dayNumber && $dayNumber > 0) {
            $item['day_number'] = $dayNumber;
        }
        if ($serviceableMeta && $serviceableMeta !== []) {
            $item['serviceable_meta'] = $serviceableMeta;
        }
        if ($itineraryItemType) {
            $item['itinerary_item_type'] = $itineraryItemType;
        }

        return $item;
    }

    private function normalizeTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $raw = (string) $value;
        return strlen($raw) >= 5 ? substr($raw, 0, 5) : $raw;
    }

    private function normalizeInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
}
