@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $itinerary->title }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $itinerary->duration_days }} day(s)</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Inquiry:
                    @if ($itinerary->inquiry)
                        {{ $itinerary->inquiry->inquiry_number }}{{ $itinerary->inquiry->customer?->name ? ' | '.$itinerary->inquiry->customer->name : '' }}
                    @else
                        Independent
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('itineraries.edit', $itinerary) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Edit</a>
                <a href="{{ route('itineraries.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Back</a>
            </div>
        </div>

        @if ($itinerary->description)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-700 dark:text-gray-200">{{ $itinerary->description }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Schedule by Day</h2>
                <div class="mt-3 space-y-4 text-sm text-gray-700 dark:text-gray-200">
                    @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                        @php
                            $attractions = ($dayGroups[$day] ?? collect())->map(function ($attraction) {
                                return [
                                    'type' => 'attraction',
                                    'name' => $attraction->name,
                                    'location' => $attraction->location,
                                    'pax' => null,
                                    'start_time' => $attraction->pivot->start_time,
                                    'end_time' => $attraction->pivot->end_time,
                                    'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next,
                                    'visit_order' => $attraction->pivot->visit_order ?? 999999,
                                ];
                            });
                            $activities = ($activityDayGroups[$day] ?? collect())->map(function ($activityItem) {
                                $activity = $activityItem->activity;
                                return [
                                    'type' => 'activity',
                                    'name' => $activity->name ?? '-',
                                    'location' => $activity->vendor->location ?? null,
                                    'pax' => $activityItem->pax,
                                    'start_time' => $activityItem->start_time,
                                    'end_time' => $activityItem->end_time,
                                    'travel_minutes_to_next' => $activityItem->travel_minutes_to_next,
                                    'visit_order' => $activityItem->visit_order ?? 999999,
                                ];
                            });
                            $dayItems = $attractions->merge($activities)->sortBy('visit_order')->values();
                        @endphp
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Day {{ $day }}</p>
                            <ul class="mt-2 space-y-2">
                                @forelse ($dayItems as $item)
                                    <li class="rounded-lg border border-gray-200 px-2 py-1 dark:border-gray-700">
                                        <span class="font-medium">{{ $item['name'] }}</span>
                                        <span class="ml-1 text-[11px] uppercase tracking-wide {{ $item['type'] === 'activity' ? 'text-emerald-600 dark:text-emerald-400' : 'text-indigo-600 dark:text-indigo-400' }}">{{ $item['type'] }}</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $item['location'] ?? '-' }}
                                            @if ($item['pax'])
                                                | {{ $item['pax'] }} pax
                                            @endif
                                            |
                                            {{ $item['start_time'] ? substr((string) $item['start_time'], 0, 5) : '--:--' }}
                                            -
                                            {{ $item['end_time'] ? substr((string) $item['end_time'], 0, 5) : '--:--' }}
                                            @if ($item['travel_minutes_to_next'] !== null)
                                                | Travel next: {{ $item['travel_minutes_to_next'] }} min
                                            @endif
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-xs text-gray-500 dark:text-gray-400">No schedule item.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">Itinerary Map</h2>
                <div id="itinerary-show-map" class="mt-3 h-[420px] w-full rounded-lg border border-gray-300"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .itinerary-marker-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 9999px;
            background: #1d4ed8;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @php
        $mapPoints = $itinerary->touristAttractions->map(function ($attraction) {
            return [
                'type' => 'attraction',
                'name' => $attraction->name,
                'location' => $attraction->location,
                'lat' => $attraction->latitude,
                'lng' => $attraction->longitude,
                'day_number' => $attraction->pivot->day_number ?? 1,
                'start_time' => $attraction->pivot->start_time,
                'end_time' => $attraction->pivot->end_time,
                'visit_order' => $attraction->pivot->visit_order ?? 1,
            ];
        })->merge(
            $itinerary->itineraryActivities->map(function ($activityItem) {
                return [
                    'type' => 'activity',
                    'name' => $activityItem->activity->name ?? '-',
                    'location' => $activityItem->activity->vendor->location ?? null,
                    'lat' => $activityItem->activity->vendor->latitude ?? null,
                    'lng' => $activityItem->activity->vendor->longitude ?? null,
                    'day_number' => $activityItem->day_number ?? 1,
                    'start_time' => $activityItem->start_time,
                    'end_time' => $activityItem->end_time,
                    'visit_order' => $activityItem->visit_order ?? 1,
                ];
            })
        )->values();
    @endphp
    <script>
        (function () {
            const points = @json($mapPoints);

            const validPoints = points.filter((point) =>
                typeof point.lat === 'number' && Number.isFinite(point.lat) &&
                typeof point.lng === 'number' && Number.isFinite(point.lng)
            ).sort((a, b) => (a.day_number - b.day_number) || (a.visit_order - b.visit_order));

            const mapElement = document.getElementById('itinerary-show-map');
            if (!mapElement || typeof L === 'undefined') return;

            const map = L.map(mapElement).setView([-6.2, 106.816666], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (!validPoints.length) return;

            const createBadgeIcon = (order) => L.divIcon({
                className: '',
                html: `<div class="itinerary-marker-badge">${order}</div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            const latLngs = [];
            validPoints.forEach((point, orderIndex) => {
                const latLng = [point.lat, point.lng];
                latLngs.push(latLng);
                const start = point.start_time ? String(point.start_time).slice(0, 5) : '--:--';
                const end = point.end_time ? String(point.end_time).slice(0, 5) : '--:--';
                L.marker(latLng, { icon: createBadgeIcon(orderIndex + 1) })
                    .bindPopup(`#${orderIndex + 1} | Day ${point.day_number} | ${point.type}: ${point.name}${point.location ? ' - ' + point.location : ''} (${start} - ${end})`)
                    .addTo(map);
            });

            if (latLngs.length === 1) {
                map.setView(latLngs[0], 14);
                return;
            }

            map.fitBounds(latLngs, { padding: [20, 20] });

            const groupedByDay = validPoints.reduce((carry, point) => {
                const key = String(point.day_number);
                carry[key] = carry[key] || [];
                carry[key].push(point);
                return carry;
            }, {});

            const dayColors = ['#2563eb', '#16a34a', '#ea580c', '#db2777', '#7c3aed', '#0891b2'];
            Object.entries(groupedByDay).forEach(([day, dayPoints], index) => {
                if (dayPoints.length < 2) return;
                const coordinates = dayPoints.map((point) => `${point.lng},${point.lat}`).join(';');
                fetch(`https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson`)
                    .then((response) => response.json())
                    .then((data) => {
                        const geometry = data?.routes?.[0]?.geometry;
                        if (!geometry) return;
                        L.geoJSON(geometry, {
                            style: { color: dayColors[index % dayColors.length], weight: 4, opacity: 0.9 }
                        }).addTo(map);
                    })
                    .catch(() => {});
            });
        })();
    </script>
@endpush


