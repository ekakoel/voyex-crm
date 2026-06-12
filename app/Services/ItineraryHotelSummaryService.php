<?php

namespace App\Services;

use App\Models\Itinerary;
use Illuminate\Support\Collection;

class ItineraryHotelSummaryService
{
    /**
     * @return Collection<int, array{
     *   hotel_name: string,
     *   city_label: string,
     *   destination_label: string,
     *   address_label: string,
     *   room_label: string,
     *   area_label: string,
     *   booking_mode_label: string,
     *   day_numbers: array<int, int>,
     *   day_label: string,
     *   night_count: int
     * }>
     */
    public function summarize(Itinerary $itinerary): Collection
    {
        $rows = $itinerary->dayPoints
            ->filter(function ($point): bool {
                $isHotelEndPoint = (string) ($point->end_point_type ?? '') === 'hotel';
                $isSelfBooked = strtolower(trim((string) ($point->end_hotel_booking_mode ?? ''))) === 'self';
                $hasLinkedHotel = (int) ($point->end_hotel_id ?? 0) > 0;

                return $isHotelEndPoint && ! $isSelfBooked && $hasLinkedHotel;
            })
            ->map(function ($point): array {
                $hotel = $point->endHotel;
                $roomName = trim((string) ($point->endHotelRoom?->rooms ?? ''));
                $roomType = trim((string) ($point->endHotelRoom?->view ?? ''));
                $roomLabel = $roomName;
                if ($roomLabel !== '' && $roomType !== '') {
                    $roomLabel .= ' (' . $roomType . ')';
                }

                $hotelRegion = $this->resolveHotelRegionLabel($hotel);
                $hotelCity = trim((string) ($hotel?->city ?? ''));
                $cityLabel = $hotelCity !== '' ? $hotelCity : ($hotelRegion !== '' ? $hotelRegion : '-');
                $addressLabel = $this->resolveHotelAddressLabel($hotel);

                return [
                    'group_key' => implode('|', [
                        (string) ((int) ($point->end_hotel_id ?? 0)),
                        (string) ((int) ($point->end_hotel_room_id ?? 0)),
                        (string) ($point->end_hotel_booking_mode ?? 'arranged'),
                    ]),
                    'hotel_name' => trim((string) ($hotel?->name ?? '')),
                    'city_label' => $cityLabel,
                    'destination_label' => trim((string) ($hotel?->destination?->name ?? '')),
                    'address_label' => $addressLabel,
                    'room_label' => $roomLabel,
                    'area_label' => trim((string) ($point->end_hotel_area ?? '')),
                    'booking_mode_label' => ui_phrase('Hotel arranged by us'),
                    'day_number' => max(1, (int) ($point->day_number ?? 1)),
                ];
            })
            ->values();

        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows
            ->groupBy('group_key')
            ->map(function (Collection $group): array {
                $first = $group->first();
                $dayNumbers = $group
                    ->pluck('day_number')
                    ->map(fn ($day): int => (int) $day)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                return [
                    'hotel_name' => (string) ($first['hotel_name'] ?? ''),
                    'city_label' => (string) ($first['city_label'] ?? '-'),
                    'destination_label' => (string) ($first['destination_label'] ?? '-'),
                    'address_label' => (string) ($first['address_label'] ?? '-'),
                    'room_label' => (string) ($first['room_label'] ?? ''),
                    'area_label' => (string) ($first['area_label'] ?? ''),
                    'booking_mode_label' => (string) ($first['booking_mode_label'] ?? ui_phrase('Hotel arranged by us')),
                    'day_numbers' => $dayNumbers,
                    'day_label' => $this->formatDayLabel($dayNumbers),
                    'night_count' => count($dayNumbers),
                ];
            })
            ->sortBy(function (array $summary): int {
                $days = $summary['day_numbers'] ?? [];
                return (int) ($days[0] ?? 9999);
            })
            ->values();
    }

    private function resolveHotelRegionLabel($hotel): string
    {
        $hotelRegionRaw = trim((string) ($hotel?->region ?? ''));
        $hotelRegion = $hotelRegionRaw;

        if ($hotelRegionRaw !== '' && ctype_digit($hotelRegionRaw)) {
            $hotelRegion = trim((string) ($hotel?->destination?->province ?? $hotel?->destination?->name ?? ''));
        }
        if ($hotelRegion === '') {
            $hotelRegion = trim((string) ($hotel?->destination?->province ?? ''));
        }
        if ($hotelRegion === '') {
            $hotelRegion = trim((string) ($hotel?->city ?? ''));
        }
        if ($hotelRegion === '') {
            $hotelRegion = trim((string) ($hotel?->province ?? ''));
        }

        return $hotelRegion;
    }

    private function resolveHotelAddressLabel($hotel): string
    {
        $segments = array_values(array_filter([
            trim((string) ($hotel?->address ?? '')),
            trim((string) ($hotel?->city ?? '')),
            trim((string) ($hotel?->province ?? '')),
        ], static fn ($value): bool => $value !== ''));

        return $segments !== [] ? implode(', ', $segments) : '-';
    }

    /**
     * @param  array<int, int>  $dayNumbers
     */
    private function formatDayLabel(array $dayNumbers): string
    {
        if ($dayNumbers === []) {
            return '-';
        }

        $ranges = [];
        $start = $dayNumbers[0];
        $end = $start;

        for ($i = 1; $i < count($dayNumbers); $i++) {
            $day = $dayNumbers[$i];
            if ($day === $end + 1) {
                $end = $day;
                continue;
            }

            $ranges[] = $start === $end
                ? 'Day ' . $start
                : 'Day ' . $start . '-' . $end;
            $start = $day;
            $end = $day;
        }

        $ranges[] = $start === $end
            ? 'Day ' . $start
            : 'Day ' . $start . '-' . $end;

        return implode(', ', $ranges);
    }
}
