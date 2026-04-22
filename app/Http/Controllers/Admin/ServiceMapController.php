<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Airport;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Hotel;
use App\Models\IslandTransfer;
use App\Models\TouristAttraction;
use App\Models\Transport;
use App\Models\Vendor;
use Illuminate\Support\Facades\Schema;

class ServiceMapController extends Controller
{
    public function index()
    {
        $markers = collect();
        $routes = collect();

        if ($this->hasMapColumns('destinations')) {
            Destination::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'name', 'city', 'province', 'latitude', 'longitude'])
                ->each(function ($item) use ($markers) {
                    $marker = $this->markerItem(
                        type: 'destination',
                        icon: 'fa-map-location-dot',
                        name: (string) ($item->name ?? 'Destination'),
                        subtitle: $this->joinSubtitle([$item->city, $item->province]),
                        latitude: (float) $item->latitude,
                        longitude: (float) $item->longitude,
                        url: route('destinations.show', $item)
                    );
                    $marker['province'] = (string) ($item->province ?? '');
                    $markers->push($marker);
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

        if (Schema::hasTable('island_transfers')) {
            IslandTransfer::query()
                ->get([
                    'id',
                    'name',
                    'departure_point_name',
                    'departure_latitude',
                    'departure_longitude',
                    'arrival_point_name',
                    'arrival_latitude',
                    'arrival_longitude',
                    'route_geojson',
                ])
                ->each(function ($item) use ($markers, $routes) {
                    $departureLat = $this->toFiniteFloat($item->departure_latitude);
                    $departureLng = $this->toFiniteFloat($item->departure_longitude);
                    $arrivalLat = $this->toFiniteFloat($item->arrival_latitude);
                    $arrivalLng = $this->toFiniteFloat($item->arrival_longitude);

                    if ($departureLat !== null && $departureLng !== null) {
                        $markers->push($this->markerItem(
                            type: 'island-transfer',
                            icon: 'fa-ship',
                            name: (string) ($item->name ?? 'Island Transfer'),
                            subtitle: $this->joinSubtitle([
                                (string) ($item->departure_point_name ?? ''),
                                'Departure',
                            ]),
                            latitude: $departureLat,
                            longitude: $departureLng,
                            url: route('island-transfers.show', $item)
                        ));
                    }

                    if ($arrivalLat !== null && $arrivalLng !== null) {
                        $markers->push($this->markerItem(
                            type: 'island-transfer',
                            icon: 'fa-anchor',
                            name: (string) ($item->name ?? 'Island Transfer'),
                            subtitle: $this->joinSubtitle([
                                (string) ($item->arrival_point_name ?? ''),
                                'Arrival',
                            ]),
                            latitude: $arrivalLat,
                            longitude: $arrivalLng,
                            url: route('island-transfers.show', $item)
                        ));
                    }

                    $path = $this->normalizeTransferRouteCoords($item->route_geojson);
                    $isFallback = false;
                    if (count($path) < 2 && $departureLat !== null && $departureLng !== null && $arrivalLat !== null && $arrivalLng !== null) {
                        $path = [
                            [$departureLat, $departureLng],
                            [$arrivalLat, $arrivalLng],
                        ];
                        $isFallback = true;
                    }

                    if (count($path) >= 2) {
                        $routes->push([
                            'type' => 'island-transfer',
                            'name' => (string) ($item->name ?? 'Island Transfer'),
                            'from' => (string) ($item->departure_point_name ?? ''),
                            'to' => (string) ($item->arrival_point_name ?? ''),
                            'coordinates' => $path,
                            'url' => route('island-transfers.show', $item),
                            'is_fallback' => $isFallback,
                        ]);
                    }
                });
        }

        $markers = $markers
            ->filter(fn ($marker) => is_finite((float) ($marker['latitude'] ?? 0)) && is_finite((float) ($marker['longitude'] ?? 0)))
            ->values();

        return view('modules.services.map', [
            'markers' => $markers,
            'routes' => $routes->values(),
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
                'islandTransfers' => $routes->where('type', 'island-transfer')->count(),
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

    private function toFiniteFloat(mixed $value): ?float
    {
        $number = is_numeric($value) ? (float) $value : null;
        if ($number === null || ! is_finite($number)) {
            return null;
        }

        return $number;
    }

    private function normalizeTransferRouteCoords(mixed $routeGeoJson): array
    {
        $payload = $routeGeoJson;
        if (is_string($payload) && trim($payload) !== '') {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded;
            }
        }

        if (! is_array($payload)) {
            return [];
        }

        $coordinates = [];
        if (($payload['type'] ?? null) === 'Feature') {
            $geometry = $payload['geometry'] ?? null;
            if (is_array($geometry) && ($geometry['type'] ?? null) === 'LineString') {
                $coordinates = is_array($geometry['coordinates'] ?? null) ? $geometry['coordinates'] : [];
            }
        } elseif (($payload['type'] ?? null) === 'LineString') {
            $coordinates = is_array($payload['coordinates'] ?? null) ? $payload['coordinates'] : [];
        } elseif (isset($payload[0]) && is_array($payload[0])) {
            $coordinates = $payload;
        }

        $latLng = [];
        foreach ($coordinates as $pair) {
            if (! is_array($pair) || count($pair) < 2) {
                continue;
            }

            $lng = $this->toFiniteFloat($pair[0] ?? null);
            $lat = $this->toFiniteFloat($pair[1] ?? null);
            if ($lat === null || $lng === null) {
                continue;
            }
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }

            $latLng[] = [$lat, $lng];
        }

        return $latLng;
    }

    private function hasMapColumns(string $table): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'latitude')
            && Schema::hasColumn($table, 'longitude');
    }
}
