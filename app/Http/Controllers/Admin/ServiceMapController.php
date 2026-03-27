<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Airport;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Hotel;
use App\Models\TouristAttraction;
use App\Models\Transport;
use App\Models\Vendor;
use Illuminate\Support\Facades\Schema;

class ServiceMapController extends Controller
{
    public function index()
    {
        $markers = collect();

        if ($this->hasMapColumns('destinations')) {
            Destination::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'name', 'city', 'province', 'latitude', 'longitude'])
                ->each(function ($item) use ($markers) {
                    $markers->push($this->markerItem(
                        type: 'destination',
                        icon: 'fa-map-location-dot',
                        name: (string) ($item->name ?? 'Destination'),
                        subtitle: $this->joinSubtitle([$item->city, $item->province]),
                        latitude: (float) $item->latitude,
                        longitude: (float) $item->longitude,
                        url: route('destinations.show', $item)
                    ));
                });
        }

        if ($this->hasMapColumns('vendors')) {
            Vendor::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'name', 'city', 'province', 'latitude', 'longitude'])
                ->each(function ($item) use ($markers) {
                    $markers->push($this->markerItem(
                        type: 'vendor',
                        icon: 'fa-handshake',
                        name: (string) ($item->name ?? 'Vendor'),
                        subtitle: $this->joinSubtitle([$item->city, $item->province]),
                        latitude: (float) $item->latitude,
                        longitude: (float) $item->longitude,
                        url: route('vendors.edit', $item)
                    ));
                });
        }

        if ($this->hasMapColumns('vendors')) {
            Activity::query()
                ->with('vendor:id,name,city,province,latitude,longitude')
                ->get(['id', 'name', 'vendor_id'])
                ->each(function ($item) use ($markers) {
                    $vendor = $item->vendor;
                    if (! $vendor || $vendor->latitude === null || $vendor->longitude === null) {
                        return;
                    }

                    $markers->push($this->markerItem(
                        type: 'activity',
                        icon: 'fa-person-hiking',
                        name: (string) ($item->name ?? 'Activity'),
                        subtitle: $this->joinSubtitle([(string) ($vendor->name ?? ''), $vendor->city, $vendor->province]),
                        latitude: (float) $vendor->latitude,
                        longitude: (float) $vendor->longitude,
                        url: route('activities.show', $item)
                    ));
                });
        }

        if ($this->hasMapColumns('vendors')) {
            FoodBeverage::query()
                ->with('vendor:id,name,city,province,latitude,longitude')
                ->get(['id', 'name', 'vendor_id'])
                ->each(function ($item) use ($markers) {
                    $vendor = $item->vendor;
                    if (! $vendor || $vendor->latitude === null || $vendor->longitude === null) {
                        return;
                    }

                    $markers->push($this->markerItem(
                        type: 'food-beverage',
                        icon: 'fa-utensils',
                        name: (string) ($item->name ?? 'F&B'),
                        subtitle: $this->joinSubtitle([(string) ($vendor->name ?? ''), $vendor->city, $vendor->province]),
                        latitude: (float) $vendor->latitude,
                        longitude: (float) $vendor->longitude,
                        url: route('food-beverages.edit', $item)
                    ));
                });
        }


        if ($this->hasMapColumns('hotels')) {
            Hotel::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'name', 'region', 'latitude', 'longitude'])
                ->each(function ($item) use ($markers) {
                    $markers->push($this->markerItem(
                        type: 'hotel',
                        icon: 'fa-bed',
                        name: (string) ($item->name ?? 'Hotel'),
                        subtitle: (string) ($item->region ?? ''),
                        latitude: (float) $item->latitude,
                        longitude: (float) $item->longitude,
                        url: route('hotels.show', $item)
                    ));
                });
        }

        if ($this->hasMapColumns('airports')) {
            Airport::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'name', 'city', 'province', 'latitude', 'longitude'])
                ->each(function ($item) use ($markers) {
                    $markers->push($this->markerItem(
                        type: 'airport',
                        icon: 'fa-plane-departure',
                        name: (string) ($item->name ?? 'Airport'),
                        subtitle: $this->joinSubtitle([$item->city, $item->province]),
                        latitude: (float) $item->latitude,
                        longitude: (float) $item->longitude,
                        url: route('airports.show', $item)
                    ));
                });
        }

        $transportSelect = ['id', 'name', 'latitude', 'longitude'];
        if (Schema::hasColumn('transports', 'city')) {
            $transportSelect[] = 'city';
        }
        if (Schema::hasColumn('transports', 'province')) {
            $transportSelect[] = 'province';
        }
        if (Schema::hasColumn('transports', 'location')) {
            $transportSelect[] = 'location';
        }

        if ($this->hasMapColumns('transports')) {
            Transport::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get($transportSelect)
                ->each(function ($item) use ($markers) {
                    $markers->push($this->markerItem(
                        type: 'transport',
                        icon: 'fa-bus',
                        name: (string) ($item->name ?? 'Transport'),
                        subtitle: $this->joinSubtitle([
                            (string) ($item->city ?? ''),
                            (string) ($item->province ?? ''),
                            (string) ($item->location ?? ''),
                        ]),
                        latitude: (float) $item->latitude,
                        longitude: (float) $item->longitude,
                        url: route('transports.show', $item)
                    ));
                });
        }

        if ($this->hasMapColumns('tourist_attractions')) {
            TouristAttraction::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'name', 'city', 'province', 'latitude', 'longitude'])
                ->each(function ($item) use ($markers) {
                    $markers->push($this->markerItem(
                        type: 'tourist-attraction',
                        icon: 'fa-landmark',
                        name: (string) ($item->name ?? 'Attraction'),
                        subtitle: $this->joinSubtitle([$item->city, $item->province]),
                        latitude: (float) $item->latitude,
                        longitude: (float) $item->longitude,
                        url: route('tourist-attractions.edit', $item)
                    ));
                });
        }

        $markers = $markers
            ->filter(fn ($marker) => is_finite((float) ($marker['latitude'] ?? 0)) && is_finite((float) ($marker['longitude'] ?? 0)))
            ->values();

        return view('modules.services.map', [
            'markers' => $markers,
            'stats' => [
                'total' => $markers->count(),
                'destinations' => $markers->where('type', 'destination')->count(),
                'vendors' => $markers->where('type', 'vendor')->count(),
                'activities' => $markers->where('type', 'activity')->count(),
                'foodBeverages' => $markers->where('type', 'food-beverage')->count(),
                'hotels' => $markers->where('type', 'hotel')->count(),
                'airports' => $markers->where('type', 'airport')->count(),
                'transports' => $markers->where('type', 'transport')->count(),
                'attractions' => $markers->where('type', 'tourist-attraction')->count(),
            ],
        ]);
    }

    private function markerItem(string $type, string $icon, string $name, string $subtitle, float $latitude, float $longitude, string $url): array
    {
        return [
            'type' => $type,
            'icon' => $icon,
            'name' => $name,
            'subtitle' => $subtitle,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'url' => $url,
        ];
    }

    private function joinSubtitle(array $parts): string
    {
        return implode(' • ', array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $parts))));
    }

    private function hasMapColumns(string $table): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'latitude')
            && Schema::hasColumn($table, 'longitude');
    }
}
