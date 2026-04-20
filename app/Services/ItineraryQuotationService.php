<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\HotelRoom;
use App\Models\IslandTransfer;
use App\Models\Itinerary;
use App\Models\TouristAttraction;
use App\Models\TransportUnit;

class ItineraryQuotationService
{
    /**
     * @return array<int, array{description:string, qty:int, contract_rate:float, markup_type:string, markup:float, unit_price:float, discount:float, serviceable_type?:string, serviceable_id?:int, day_number?:int, serviceable_meta?:array, itinerary_item_type?:string}>
     */
    public function buildItems(Itinerary $itinerary): array
    {
        $itinerary->loadMissing([
            'touristAttractions:id,name,contract_rate_per_pax,markup_type,markup,publish_rate_per_pax',
            'itineraryActivities.activity:id,name,adult_contract_rate,child_contract_rate,adult_markup_type,adult_markup,child_markup_type,child_markup,adult_publish_rate,child_publish_rate',
            'itineraryIslandTransfers.islandTransfer:id,name,contract_rate,markup_type,markup,publish_rate',
            'itineraryFoodBeverages.foodBeverage:id,name,contract_rate,markup_type,markup,publish_rate',
            'itineraryTransportUnits.transportUnit:id,name,contract_rate,markup_type,markup,publish_rate',
            'dayPoints.endHotelRoom:id,hotels_id,rooms,view',
            'dayPoints.endHotelRoom.prices:id,rooms_id,start_date,end_date,contract_rate,markup_type,markup,publish_rate',
            'dayPoints.endHotel:id,name',
            'dayPoints.endHotel.prices:id,hotels_id,rooms_id,start_date,end_date,contract_rate,markup_type,markup,publish_rate',
        ]);

        $dayRows = [];
        $sequence = 0;

        foreach ($itinerary->itineraryTransportUnits as $transportItem) {
            $unit = $transportItem->transportUnit;
            if (! $unit) {
                continue;
            }
            $day = (int) ($transportItem->day_number ?? 0);
            $label = 'Transport: ' . $unit->name;
            $contractRate = (float) ($unit->contract_rate ?? 0);
            $publishRate = (float) ($unit->publish_rate ?? 0);
            if ($contractRate <= 0 && $publishRate > 0) {
                $contractRate = $publishRate;
            }
            if ($publishRate <= 0) {
                $publishRate = $contractRate;
            }
            $markupType = ($unit->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markup = (float) ($unit->markup ?? 0);
            if ($markup <= 0) {
                $markup = max(0, $publishRate - $contractRate);
                $markupType = 'fixed';
            }
            $item = $this->makeItem(
                $this->dayPrefix($day) . $label,
                1,
                $publishRate,
                0,
                TransportUnit::class,
                (int) $unit->id,
                $day,
                [
                    'day_number' => $day,
                ],
                'transport_day'
            );
            $item['contract_rate'] = max(0, $contractRate);
            $item['markup_type'] = $markupType;
            $item['markup'] = max(0, $markup);
            $item['unit_price'] = max(0, $publishRate);
            $dayRows[] = [
                'day' => $day,
                'bucket_order' => 0,
                'visit_order' => null,
                'start_minutes' => null,
                'sequence' => $sequence++,
                'item' => $item,
            ];
        }

        foreach ($itinerary->touristAttractions as $attraction) {
            $day = (int) ($attraction->pivot->day_number ?? 0);
            $contractRate = (float) ($attraction->contract_rate_per_pax ?? 0);
            $markupType = ($attraction->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markup = (float) ($attraction->markup ?? 0);
            $price = (float) ($attraction->publish_rate_per_pax ?? 0);
            if ($contractRate <= 0 && $price > 0) {
                $contractRate = $price;
            }
            if ($price <= 0) {
                $price = $markupType === 'percent'
                    ? ($contractRate + ($contractRate * ($markup / 100)))
                    : ($contractRate + $markup);
            }
            $item = $this->makeItem(
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
            );
            $item['contract_rate'] = max(0, $contractRate);
            $item['markup_type'] = $markupType;
            $item['markup'] = max(0, $markup);
            $item['unit_price'] = max(0, $price);
            $dayRows[] = [
                'day' => $day,
                'bucket_order' => 1,
                'visit_order' => $this->normalizeInt($attraction->pivot->visit_order ?? null),
                'start_minutes' => $this->timeToMinutes($attraction->pivot->start_time ?? null),
                'sequence' => $sequence++,
                'item' => $item,
            ];
        }

        foreach ($itinerary->itineraryActivities as $activityItem) {
            $activity = $activityItem->activity;
            if (! $activity) {
                continue;
            }
            $day = (int) ($activityItem->day_number ?? 0);
            $adultQty = max(0, (int) ($activityItem->pax_adult ?? 0));
            $childQty = max(0, (int) ($activityItem->pax_child ?? 0));
            $totalQty = max(1, (int) ($activityItem->pax ?? 1));
            if (($adultQty + $childQty) <= 0) {
                $adultQty = $totalQty;
                $childQty = 0;
            }

            $activityMeta = [
                'day_number' => $day,
                'start_time' => $this->normalizeTime($activityItem->start_time ?? null),
                'end_time' => $this->normalizeTime($activityItem->end_time ?? null),
                'travel_minutes_to_next' => $this->normalizeInt($activityItem->travel_minutes_to_next ?? null),
                'visit_order' => $this->normalizeInt($activityItem->visit_order ?? null),
            ];

            if ($adultQty > 0) {
                $adultContract = (float) ($activity->adult_contract_rate ?? 0);
                $adultMarkupType = ($activity->adult_markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
                $adultMarkup = (float) ($activity->adult_markup ?? 0);
                $adultPublish = (float) ($activity->adult_publish_rate ?? 0);
                if ($adultContract <= 0 && $adultPublish > 0) {
                    $adultContract = $adultPublish;
                }
                if ($adultPublish <= 0) {
                    $adultPublish = $adultMarkupType === 'percent'
                        ? ($adultContract + ($adultContract * ($adultMarkup / 100)))
                        : ($adultContract + $adultMarkup);
                }
                $adultItem = $this->makeItem(
                    $this->dayPrefix($day) . 'Activity: ' . $activity->name,
                    $adultQty,
                    $adultPublish,
                    0,
                    Activity::class,
                    (int) $activity->id,
                    $day,
                    array_merge($activityMeta, [
                        'pax' => $adultQty,
                        'pax_type' => 'adult',
                    ]),
                    'activity'
                );
                $adultItem['contract_rate'] = max(0, $adultContract);
                $adultItem['markup_type'] = $adultMarkupType;
                $adultItem['markup'] = max(0, $adultMarkup);
                $adultItem['unit_price'] = max(0, $adultPublish);
                $dayRows[] = [
                    'day' => $day,
                    'bucket_order' => 1,
                    'visit_order' => $this->normalizeInt($activityItem->visit_order ?? null),
                    'start_minutes' => $this->timeToMinutes($activityItem->start_time ?? null),
                    'sequence' => $sequence++,
                    'item' => $adultItem,
                ];
            }

            if ($childQty > 0) {
                $childContract = (float) ($activity->child_contract_rate ?? $activity->adult_contract_rate ?? 0);
                $childMarkupType = ($activity->child_markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
                $childMarkup = (float) ($activity->child_markup ?? 0);
                $childPublish = (float) ($activity->child_publish_rate ?? $activity->adult_publish_rate ?? 0);
                if ($childContract <= 0 && $childPublish > 0) {
                    $childContract = $childPublish;
                }
                if ($childPublish <= 0) {
                    $childPublish = $childMarkupType === 'percent'
                        ? ($childContract + ($childContract * ($childMarkup / 100)))
                        : ($childContract + $childMarkup);
                }
                $childItem = $this->makeItem(
                    $this->dayPrefix($day) . 'Activity: ' . $activity->name,
                    $childQty,
                    $childPublish,
                    0,
                    Activity::class,
                    (int) $activity->id,
                    $day,
                    array_merge($activityMeta, [
                        'pax' => $childQty,
                        'pax_type' => 'child',
                    ]),
                    'activity'
                );
                $childItem['contract_rate'] = max(0, $childContract);
                $childItem['markup_type'] = $childMarkupType;
                $childItem['markup'] = max(0, $childMarkup);
                $childItem['unit_price'] = max(0, $childPublish);
                $dayRows[] = [
                    'day' => $day,
                    'bucket_order' => 1,
                    'visit_order' => $this->normalizeInt($activityItem->visit_order ?? null),
                    'start_minutes' => $this->timeToMinutes($activityItem->start_time ?? null),
                    'sequence' => $sequence++,
                    'item' => $childItem,
                ];
            }
        }

        foreach ($itinerary->itineraryIslandTransfers as $transferItem) {
            $transfer = $transferItem->islandTransfer;
            if (! $transfer) {
                continue;
            }
            $day = (int) ($transferItem->day_number ?? 0);
            $qty = max(1, (int) ($transferItem->pax ?? 1));
            $contractRate = (float) ($transfer->contract_rate ?? 0);
            $markupType = ($transfer->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markup = (float) ($transfer->markup ?? 0);
            $price = (float) ($transfer->publish_rate ?? 0);
            if ($contractRate <= 0 && $price > 0) {
                $contractRate = $price;
            }
            if ($price <= 0) {
                $price = $markupType === 'percent'
                    ? ($contractRate + ($contractRate * ($markup / 100)))
                    : ($contractRate + $markup);
            }
            $item = $this->makeItem(
                $this->dayPrefix($day) . 'Island Transfer: ' . $transfer->name,
                $qty,
                $price,
                0,
                IslandTransfer::class,
                (int) $transfer->id,
                $day,
                [
                    'day_number' => $day,
                    'pax' => $qty,
                    'start_time' => $this->normalizeTime($transferItem->start_time ?? null),
                    'end_time' => $this->normalizeTime($transferItem->end_time ?? null),
                    'travel_minutes_to_next' => $this->normalizeInt($transferItem->travel_minutes_to_next ?? null),
                    'visit_order' => $this->normalizeInt($transferItem->visit_order ?? null),
                ],
                'transfer'
            );
            $item['contract_rate'] = max(0, $contractRate);
            $item['markup_type'] = $markupType;
            $item['markup'] = max(0, $markup);
            $item['unit_price'] = max(0, $price);
            $dayRows[] = [
                'day' => $day,
                'bucket_order' => 1,
                'visit_order' => $this->normalizeInt($transferItem->visit_order ?? null),
                'start_minutes' => $this->timeToMinutes($transferItem->start_time ?? null),
                'sequence' => $sequence++,
                'item' => $item,
            ];
        }

        foreach ($itinerary->itineraryFoodBeverages as $foodItem) {
            $food = $foodItem->foodBeverage;
            if (! $food) {
                continue;
            }
            $day = (int) ($foodItem->day_number ?? 0);
            $qty = max(1, (int) ($foodItem->pax ?? 1));
            $contractRate = (float) ($food->contract_rate ?? 0);
            $markupType = ($food->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markup = (float) ($food->markup ?? 0);
            $price = (float) ($food->publish_rate ?? 0);
            if ($contractRate <= 0 && $price > 0) {
                $contractRate = $price;
            }
            if ($price <= 0) {
                $price = $markupType === 'percent'
                    ? ($contractRate + ($contractRate * ($markup / 100)))
                    : ($contractRate + $markup);
            }
            $item = $this->makeItem(
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
            );
            $item['contract_rate'] = max(0, $contractRate);
            $item['markup_type'] = $markupType;
            $item['markup'] = max(0, $markup);
            $item['unit_price'] = max(0, $price);
            $dayRows[] = [
                'day' => $day,
                'bucket_order' => 1,
                'visit_order' => $this->normalizeInt($foodItem->visit_order ?? null),
                'start_minutes' => $this->timeToMinutes($foodItem->start_time ?? null),
                'sequence' => $sequence++,
                'item' => $item,
            ];
        }

        foreach ($itinerary->dayPoints as $dayPoint) {
            if (($dayPoint->end_point_type ?? null) !== 'hotel') {
                continue;
            }
            $room = $dayPoint->endHotelRoom;
            if (! $room) {
                continue;
            }
            $day = (int) ($dayPoint->day_number ?? 0);
            $hotelName = $dayPoint->endHotel?->name ?: 'Hotel';
            $label = 'Hotel: ' . $hotelName;
            if ($room->rooms) {
                $label .= ' - ' . $room->rooms;
            }
            if ($room->view) {
                $label .= ' (' . $room->view . ')';
            }
            $endHotelBookingMode = (string) ($dayPoint->end_hotel_booking_mode ?? 'arranged');
            $isSelfBookedHotel = $endHotelBookingMode === 'self';
            if ($isSelfBookedHotel) {
                $label .= ' (Self Booked)';
            }

            $today = now()->toDateString();
            $hotelPrice = $room->prices
                ->first(function ($price) use ($today): bool {
                    $start = $price->start_date ? (string) $price->start_date : null;
                    $end = $price->end_date ? (string) $price->end_date : null;

                    if ($start && $end) {
                        return $start <= $today && $today <= $end;
                    }
                    if ($start && ! $end) {
                        return $start <= $today;
                    }
                    if (! $start && $end) {
                        return $today <= $end;
                    }

                    return false;
                });

            if (! $hotelPrice) {
                $hotelPrice = $room->prices
                    ->sortByDesc(function ($price) {
                        return $price->end_date
                            ?? $price->start_date
                            ?? $price->id;
                    })
                    ->first();
            }

            if (! $hotelPrice && $dayPoint->endHotel) {
                $hotelPrices = $dayPoint->endHotel->prices ?? collect();
                $hotelPrice = $hotelPrices
                    ->first(function ($price) use ($today, $room): bool {
                        $priceRoomId = (int) ($price->rooms_id ?? 0);
                        if ($priceRoomId > 0 && $priceRoomId !== (int) $room->id) {
                            return false;
                        }
                        $start = $price->start_date ? (string) $price->start_date : null;
                        $end = $price->end_date ? (string) $price->end_date : null;

                        if ($start && $end) {
                            return $start <= $today && $today <= $end;
                        }
                        if ($start && ! $end) {
                            return $start <= $today;
                        }
                        if (! $start && $end) {
                            return $today <= $end;
                        }

                        return false;
                    });

                if (! $hotelPrice) {
                    $hotelPrice = $hotelPrices
                        ->filter(function ($price) use ($room): bool {
                            $priceRoomId = (int) ($price->rooms_id ?? 0);
                            return $priceRoomId === 0 || $priceRoomId === (int) $room->id;
                        })
                        ->sortByDesc(function ($price) {
                            return $price->end_date
                                ?? $price->start_date
                                ?? $price->id;
                        })
                        ->first();
                }
            }

            $contractRate = (float) ($hotelPrice?->contract_rate ?? 0);
            $markupType = ($hotelPrice?->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $markup = (float) ($hotelPrice?->markup ?? 0);
            $publishRate = (float) ($hotelPrice?->publish_rate ?? 0);
            if ($contractRate <= 0 && $publishRate > 0) {
                $contractRate = $publishRate;
            }
            if ($publishRate <= 0) {
                $publishRate = $markupType === 'percent'
                    ? ($contractRate + ($contractRate * ($markup / 100)))
                    : ($contractRate + $markup);
            }
            if ($isSelfBookedHotel) {
                $contractRate = 0;
                $markupType = 'fixed';
                $markup = 0;
                $publishRate = 0;
            }

            $qty = 1;
            $item = $this->makeItem(
                $this->dayPrefix($day) . $label,
                $qty,
                $publishRate,
                0,
                HotelRoom::class,
                (int) $room->id,
                $day,
                [
                    'day_number' => $day,
                    'end_hotel_id' => $this->normalizeInt($dayPoint->end_hotel_id ?? null),
                    'end_hotel_room_id' => (int) $room->id,
                    'end_point_type' => (string) ($dayPoint->end_point_type ?? 'hotel'),
                    'end_hotel_booking_mode' => $endHotelBookingMode,
                    'hotel_price_id' => $hotelPrice?->id ? (int) $hotelPrice->id : null,
                ],
                'hotel_day_end'
            );
            $item['contract_rate'] = max(0, $contractRate);
            $item['markup_type'] = $markupType;
            $item['markup'] = max(0, $markup);
            $item['unit_price'] = max(0, $publishRate);

            $dayRows[] = [
                'day' => $day,
                'bucket_order' => 2,
                'visit_order' => null,
                'start_minutes' => null,
                'sequence' => $sequence++,
                'item' => $item,
            ];
        }

        usort($dayRows, function (array $a, array $b): int {
            if ($a['day'] !== $b['day']) {
                return $a['day'] <=> $b['day'];
            }
            if ($a['bucket_order'] !== $b['bucket_order']) {
                return $a['bucket_order'] <=> $b['bucket_order'];
            }

            if ((int) $a['bucket_order'] === 1) {
                $aVisit = $a['visit_order'];
                $bVisit = $b['visit_order'];

                if ($aVisit !== null && $bVisit !== null && $aVisit !== $bVisit) {
                    return $aVisit <=> $bVisit;
                }
                if ($aVisit !== null && $bVisit === null) {
                    return -1;
                }
                if ($aVisit === null && $bVisit !== null) {
                    return 1;
                }

                $aStart = $a['start_minutes'];
                $bStart = $b['start_minutes'];
                if ($aStart !== null && $bStart !== null && $aStart !== $bStart) {
                    return $aStart <=> $bStart;
                }
                if ($aStart !== null && $bStart === null) {
                    return -1;
                }
                if ($aStart === null && $bStart !== null) {
                    return 1;
                }
            }

            return $a['sequence'] <=> $b['sequence'];
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
     * @return array{description:string, qty:int, contract_rate:float, markup_type:string, markup:float, unit_price:float, discount:float, serviceable_type?:string, serviceable_id?:int, day_number?:int, serviceable_meta?:array, itinerary_item_type?:string}
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
            'contract_rate' => max(0, $unitPrice),
            'markup_type' => 'fixed',
            'markup' => 0,
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

    private function timeToMinutes($value): ?int
    {
        $time = $this->normalizeTime($value);
        if (! $time || strlen($time) < 5) {
            return null;
        }
        [$hours, $minutes] = explode(':', substr($time, 0, 5));
        if (! is_numeric($hours) || ! is_numeric($minutes)) {
            return null;
        }

        return ((int) $hours * 60) + (int) $minutes;
    }
}

