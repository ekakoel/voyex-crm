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
                <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'stream']) }}" target="_blank" class="rounded-lg border border-sky-300 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50 dark:border-sky-700 dark:text-sky-300 dark:hover:bg-sky-900/20">Preview PDF</a>
                <a href="{{ route('itineraries.pdf', [$itinerary, 'mode' => 'download']) }}" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">Download PDF</a>
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
                                    'description' => $attraction->description,
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
                                    'description' => $activity->notes ?? null,
                                    'includes' => $activity->includes ?? null,
                                    'benefits' => $activity->benefits ?? null,
                                    'pax' => $activityItem->pax,
                                    'start_time' => $activityItem->start_time,
                                    'end_time' => $activityItem->end_time,
                                    'travel_minutes_to_next' => $activityItem->travel_minutes_to_next,
                                    'visit_order' => $activityItem->visit_order ?? 999999,
                                ];
                            });
                            $dayItems = $attractions->merge($activities)->sortBy('visit_order')->values();
                            $dayStartTime = $dayItems
                                ->pluck('start_time')
                                ->filter()
                                ->map(fn ($time) => substr((string) $time, 0, 5))
                                ->sort()
                                ->first();
                            $dayEndTime = $dayItems
                                ->pluck('end_time')
                                ->filter()
                                ->map(fn ($time) => substr((string) $time, 0, 5))
                                ->sort()
                                ->last();
                        @endphp
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Day {{ $day }}</p>
                                <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                    {{ $dayStartTime ?? '--:--' }} - {{ $dayEndTime ?? '--:--' }}
                                </p>
                            </div>
                            <ul class="mt-2 space-y-2">
                                @forelse ($dayItems as $index => $item)
                                    @php
                                        $isLast = $index === ($dayItems->count() - 1);
                                        $travelMinutes = $item['travel_minutes_to_next'];
                                    @endphp
                                    <li class="flex items-start gap-0">
                                        <div class="w-10 flex flex-col items-center">
                                            <button
                                                type="button"
                                                class="schedule-item-index-btn inline-flex h-7 w-7 items-center justify-center rounded-full bg-black text-[11px] font-semibold text-white"
                                                data-day="{{ $day }}"
                                                data-seq="{{ $index + 1 }}"
                                                title="Lihat di map">
                                                {{ $index + 1 }}
                                            </button>
                                            @unless ($isLast)
                                                <span class="h-5 w-px bg-gray-300 dark:bg-gray-600"></span>
                                                <span class="timeline-travel-label">
                                                    {{ $travelMinutes !== null ? $travelMinutes . ' min' : '- min' }}
                                                </span>
                                                <span class="h-5 w-px bg-gray-300 dark:bg-gray-600"></span>
                                            @endunless
                                        </div>
                                        <span class="mt-3 h-px w-5 shrink-0 bg-gray-300 dark:bg-gray-600"></span>
                                        <div class="ml-2 flex-1 rounded-lg border border-gray-200 px-2 py-1 dark:border-gray-700">
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
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $item['description'] ? \Illuminate\Support\Str::limit(strip_tags((string) $item['description']), 180) : '-' }}</p>
                                            @if ($item['type'] === 'activity')
                                                <div class="mt-1 space-y-0.5 text-[11px] text-gray-600 dark:text-gray-300">
                                                    <p><span class="font-semibold">Pax:</span> {{ $item['pax'] ?? '-' }}</p>
                                                    <p><span class="font-semibold">Includes:</span> {{ $item['includes'] ? \Illuminate\Support\Str::limit(strip_tags((string) $item['includes']), 120) : '-' }}</p>
                                                    <p><span class="font-semibold">Benefits:</span> {{ $item['benefits'] ? \Illuminate\Support\Str::limit(strip_tags((string) $item['benefits']), 120) : '-' }}</p>
                                                </div>
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
                <div id="itinerary-show-map" class="mt-3 h-[520px] md:h-[640px] w-full rounded-lg border border-gray-300"></div>
                <div class="mt-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Display By Day</p>
                    <div id="itinerary-day-controls" class="mt-2 flex flex-wrap gap-2">
                        <button type="button" data-day="" class="day-filter-btn rounded-lg border border-indigo-500 bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 hover:text-white">All Days</button>
                        @for ($day = 1; $day <= $itinerary->duration_days; $day++)
                            <button type="button" data-day="{{ $day }}" class="day-filter-btn rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-white">Day {{ $day }}</button>
                        @endfor
                    </div>
                </div>
                <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50/70 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Visible Items</p>
                    <ul id="itinerary-map-item-list" class="mt-2 space-y-1.5 text-xs text-gray-700 dark:text-gray-200"></ul>
                </div>
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
        .itinerary-marker-badge.is-highlighted {
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.95), 0 2px 8px rgba(0, 0, 0, 0.45);
            transform: scale(1.08);
        }
        .timeline-travel-label {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            background: #6b7280;
            color: #fff;
            border-radius: 8px;
            padding: 6px 2px;
            font-size: 10px;
            line-height: 1;
        }
        .visible-item {
            display: flex;
            align-items: stretch;
            overflow: hidden;
        }
        .visible-item-icon-box {
            width: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .visible-item-icon-box.attraction {
            background: rgba(29, 78, 216, 0.14);
            color: #1d4ed8;
        }
        .visible-item-icon-box.activity {
            background: rgba(5, 150, 105, 0.14);
            color: #047857;
        }
        .dark .visible-item-icon-box.attraction {
            background: rgba(59, 130, 246, 0.25);
            color: #bfdbfe;
        }
        .dark .visible-item-icon-box.activity {
            background: rgba(16, 185, 129, 0.25);
            color: #a7f3d0;
        }
        .visible-item-content {
            flex: 1;
            padding: 6px 8px;
            min-width: 0;
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

            const markerLayer = L.layerGroup().addTo(map);
            const routeLayers = [];
            const dayControls = document.getElementById('itinerary-day-controls');
            const dayButtons = dayControls ? Array.from(dayControls.querySelectorAll('.day-filter-btn')) : [];
            const scheduleIndexButtons = Array.from(document.querySelectorAll('.schedule-item-index-btn'));
            const mapItemList = document.getElementById('itinerary-map-item-list');
            const dayValues = [...new Set(validPoints.map((point) => Number(point.day_number)))].sort((a, b) => a - b);
            const markerLookup = new Map();
            let highlightedMarker = null;
            let highlightedMarkerOrder = null;

            const createBadgeIcon = (order, highlighted = false) => L.divIcon({
                className: '',
                html: `<div class="itinerary-marker-badge ${highlighted ? 'is-highlighted' : ''}">${order}</div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            const groupedByDay = validPoints.reduce((carry, point) => {
                const key = String(point.day_number);
                carry[key] = carry[key] || [];
                carry[key].push(point);
                return carry;
            }, {});

            const dayColors = ['#2563eb', '#16a34a', '#ea580c', '#db2777', '#7c3aed', '#0891b2'];
            const clearRouteLayers = () => {
                routeLayers.forEach((layer) => map.removeLayer(layer));
                routeLayers.length = 0;
            };

            const setActiveButton = (selectedDay) => {
                dayButtons.forEach((button) => {
                    const isActive = (button.dataset.day || '') === (selectedDay === null ? '' : String(selectedDay));
                    button.classList.toggle('bg-indigo-600', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('border-indigo-500', isActive);
                    button.classList.toggle('hover:bg-indigo-700', isActive);
                    button.classList.toggle('hover:text-white', isActive);
                    button.classList.toggle('bg-white', !isActive);
                    button.classList.toggle('text-gray-700', !isActive);
                    button.classList.toggle('border-gray-300', !isActive);
                    button.classList.toggle('hover:bg-gray-100', !isActive);
                    button.classList.toggle('hover:text-gray-900', !isActive);
                    button.classList.toggle('dark:bg-gray-900', !isActive);
                    button.classList.toggle('dark:text-gray-200', !isActive);
                    button.classList.toggle('dark:border-gray-600', !isActive);
                    button.classList.toggle('dark:hover:bg-gray-700', !isActive);
                    button.classList.toggle('dark:hover:text-white', !isActive);
                });
            };

            const renderMapItemList = (items) => {
                if (!mapItemList) return;
                if (!items.length) {
                    mapItemList.innerHTML = '<li class="text-gray-500 dark:text-gray-400">No items available.</li>';
                    return;
                }
                mapItemList.innerHTML = items.map((item) => {
                    const start = item.start_time ? String(item.start_time).slice(0, 5) : '--:--';
                    const end = item.end_time ? String(item.end_time).slice(0, 5) : '--:--';
                    const type = item.type === 'activity' ? 'Activity' : 'Attraction';
                    const dayLabel = `Day ${item.day_number}`;
                    const isActivity = item.type === 'activity';
                    const iconClass = isActivity ? 'fa-solid fa-person-hiking' : 'fa-solid fa-location-dot';
                    const iconTypeClass = isActivity ? 'activity' : 'attraction';
                    return `<li class="visible-item rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/50">
                        <div class="visible-item-icon-box ${iconTypeClass}">
                            <i class="${iconClass} text-xl"></i>
                        </div>
                        <div class="visible-item-content">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex min-w-0 items-center">
                                <span class="truncate font-medium">#${item.badge_no} ${item.name}</span>
                                </span>
                                <span class="text-[10px] uppercase text-gray-500 dark:text-gray-400">${dayLabel} | ${type}</span>
                            </div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">${start} - ${end}${item.location ? ` | ${item.location}` : ''}</div>
                        </div>
                    </li>`;
                }).join('');
            };

            const renderPoints = async (selectedDay = null) => {
                markerLayer.clearLayers();
                clearRouteLayers();
                markerLookup.clear();
                highlightedMarker = null;
                highlightedMarkerOrder = null;

                const activeDays = selectedDay === null ? dayValues : [selectedDay];
                const activePoints = validPoints.filter((point) => activeDays.includes(Number(point.day_number)));
                if (!activePoints.length) return;

                const badgeCounterByDay = {};
                const latLngs = [];
                const visibleListItems = [];
                activePoints.forEach((point) => {
                    const dayKey = String(point.day_number);
                    badgeCounterByDay[dayKey] = (badgeCounterByDay[dayKey] || 0) + 1;
                    const badgeNo = badgeCounterByDay[dayKey];
                    const latLng = [point.lat, point.lng];
                    latLngs.push(latLng);
                    const start = point.start_time ? String(point.start_time).slice(0, 5) : '--:--';
                    const end = point.end_time ? String(point.end_time).slice(0, 5) : '--:--';
                    const marker = L.marker(latLng, { icon: createBadgeIcon(badgeNo) })
                        .bindPopup(`#${badgeNo} | Day ${point.day_number} | ${point.type}: ${point.name}${point.location ? ' - ' + point.location : ''} (${start} - ${end})`)
                        .addTo(markerLayer);
                    markerLookup.set(`${point.day_number}-${badgeNo}`, marker);
                    visibleListItems.push({
                        badge_no: badgeNo,
                        day_number: point.day_number,
                        type: point.type,
                        name: point.name,
                        location: point.location,
                        start_time: point.start_time,
                        end_time: point.end_time,
                    });
                });
                renderMapItemList(visibleListItems);

                if (latLngs.length === 1) {
                    map.setView(latLngs[0], 14);
                } else {
                    map.fitBounds(latLngs, { padding: [20, 20] });
                }

                for (const day of activeDays) {
                    const dayPoints = (groupedByDay[String(day)] || []).slice().sort((a, b) => (a.visit_order - b.visit_order));
                    if (dayPoints.length < 2) continue;
                    const coordinates = dayPoints.map((point) => `${point.lng},${point.lat}`).join(';');
                    try {
                        const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson`);
                        const data = await response.json();
                        const geometry = data?.routes?.[0]?.geometry;
                        if (!geometry) continue;
                        const dayIndex = Math.max(0, dayValues.indexOf(day));
                        const layer = L.geoJSON(geometry, {
                            style: { color: dayColors[dayIndex % dayColors.length], weight: 4, opacity: 0.9 }
                        }).addTo(map);
                        routeLayers.push(layer);
                    } catch (_) {}
                }
            };

            let activeDay = null;
            setActiveButton(activeDay);
            renderPoints(activeDay);

            const focusSchedulePoint = async (day, seq) => {
                if (!Number.isFinite(day) || !Number.isFinite(seq)) return;
                if (activeDay !== day) {
                    activeDay = day;
                    setActiveButton(activeDay);
                    await renderPoints(activeDay);
                }

                const key = `${day}-${seq}`;
                const marker = markerLookup.get(key);
                if (!marker) return;

                if (highlightedMarker && highlightedMarkerOrder !== null) {
                    highlightedMarker.setIcon(createBadgeIcon(highlightedMarkerOrder, false));
                }

                marker.setIcon(createBadgeIcon(seq, true));
                highlightedMarker = marker;
                highlightedMarkerOrder = seq;
                map.panTo(marker.getLatLng(), { animate: true, duration: 0.35 });
                marker.openPopup();
            };

            dayButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const value = button.dataset.day || '';
                    activeDay = value === '' ? null : Number(value);
                    setActiveButton(activeDay);
                    await renderPoints(activeDay);
                });
            });

            scheduleIndexButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const day = Number(button.dataset.day || '');
                    const seq = Number(button.dataset.seq || '');
                    await focusSchedulePoint(day, seq);
                });
            });
        })();
    </script>
@endpush


