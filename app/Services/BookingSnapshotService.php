<?php

namespace App\Services;

use App\Models\Quotation;

class BookingSnapshotService
{
    public function resolvePaxSnapshot(Quotation $quotation, ?int $paxAdultInput = null, ?int $paxChildInput = null): array
    {
        $adult = $paxAdultInput ?? (int) ($quotation->pax_adult ?? 0);
        $child = $paxChildInput ?? (int) ($quotation->pax_child ?? 0);

        return [
            'pax_adult' => max(0, (int) $adult),
            'pax_child' => max(0, (int) $child),
        ];
    }

    public function resolveItinerarySnapshot(Quotation $quotation): ?array
    {
        $itinerary = $quotation->itinerary;
        if (! $itinerary) {
            return null;
        }

        return [
            'id' => (int) $itinerary->id,
            'title' => (string) ($itinerary->title ?? ''),
            'destination_id' => ! empty($itinerary->destination_id) ? (int) $itinerary->destination_id : null,
            'destination_name' => (string) ($itinerary->destination?->name ?? $itinerary->destination ?? ''),
            'duration_days' => ! empty($itinerary->duration_days) ? (int) $itinerary->duration_days : null,
            'duration_nights' => ! empty($itinerary->duration_nights) ? (int) $itinerary->duration_nights : null,
            'snapshot_at' => now()->toIso8601String(),
        ];
    }
}

