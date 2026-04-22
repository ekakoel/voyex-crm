@extends('layouts.master')

@section('page_title', __('ui.modules.destinations.page_title'))
@section('page_subtitle', __('ui.modules.destinations.show_page_subtitle'))

@section('content')
    <div class="module-page module-page--destinations">
        @section('page_actions')<a href="{{ route('destinations.edit', $destination) }}"  class="btn-primary">{{ __('ui.common.edit') }}</a>
                <a href="{{ route('destinations.index') }}"  class="btn-ghost">{{ __('ui.common.back') }}</a>@endsection

        @php
            $linkedServiceGroups = [
                [
                    'key' => 'island_transfers',
                    'label' => __('ui.modules.island_transfers.page_title'),
                    'items' => $islandTransfers,
                    'empty' => __('ui.modules.destinations.island_transfers_empty'),
                    'tone' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
                ],
                [
                    'key' => 'activities',
                    'label' => __('ui.modules.activities.page_title'),
                    'items' => $activities,
                    'empty' => __('No activities linked to this destination yet.'),
                    'tone' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
                ],
                [
                    'key' => 'vendors',
                    'label' => __('ui.modules.vendors.page_title'),
                    'items' => $vendors,
                    'empty' => __('No vendors linked to this destination yet.'),
                    'tone' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
                ],
                [
                    'key' => 'hotels',
                    'label' => __('ui.modules.hotels.page_title'),
                    'items' => $hotels,
                    'empty' => __('No hotels linked to this destination yet.'),
                    'tone' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-200',
                ],
                [
                    'key' => 'attractions',
                    'label' => __('ui.modules.tourist_attractions.page_title'),
                    'items' => $touristAttractions,
                    'empty' => __('No attractions linked to this destination yet.'),
                    'tone' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
                ],
                [
                    'key' => 'airports',
                    'label' => __('ui.modules.airports.page_title'),
                    'items' => $airports,
                    'empty' => __('No airports linked to this destination yet.'),
                    'tone' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200',
                ],
                [
                    'key' => 'transports',
                    'label' => __('ui.modules.transports.page_title'),
                    'items' => $transports,
                    'empty' => __('No transports linked to this destination yet.'),
                    'tone' => 'bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-200',
                ],
                [
                    'key' => 'food_beverages',
                    'label' => __('ui.modules.food_beverages.page_title'),
                    'items' => $foodBeverages,
                    'empty' => __('No food & beverage linked to this destination yet.'),
                    'tone' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200',
                ],
            ];
            $nonEmptyLinkedServiceGroups = collect($linkedServiceGroups)
                ->filter(function ($group) {
                    return $group['items']->count() > 0;
                })
                ->values();
            $totalLinkedRecords = (int) $nonEmptyLinkedServiceGroups->sum(function ($group) {
                return $group['items']->count();
            });

            $serviceCards = [
                [
                    'key' => 'vendors',
                    'label' => __('ui.modules.vendors.page_title'),
                    'value' => (int) ($destination->vendors_count ?? 0),
                    'caption' => __('ui.common.total'),
                    'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                ],
                [
                    'key' => 'island_transfers',
                    'label' => __('Island Transfers'),
                    'value' => (int) ($destination->island_transfers_count ?? 0),
                    'caption' => __('ui.common.total'),
                    'tone' => 'bg-sky-50 text-sky-700 border-sky-100',
                ],
                [
                    'key' => 'attractions',
                    'label' => __('ui.modules.destinations.attractions'),
                    'value' => (int) ($destination->tourist_attractions_count ?? 0),
                    'caption' => __('ui.common.total'),
                    'tone' => 'bg-amber-50 text-amber-700 border-amber-100',
                ],
                [
                    'key' => 'hotels',
                    'label' => __('ui.modules.hotels.page_title'),
                    'value' => (int) ($destination->hotels_count ?? 0),
                    'caption' => __('ui.common.total'),
                    'tone' => 'bg-cyan-50 text-cyan-700 border-cyan-100',
                ],
                [
                    'key' => 'airports',
                    'label' => __('ui.modules.airports.page_title'),
                    'value' => (int) ($destination->airports_count ?? 0),
                    'caption' => __('ui.common.total'),
                    'tone' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                ],
            ];

        @endphp
        <div class="space-y-2 mb-5">
            <x-index-stats :cards="$serviceCards" />
        </div>

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="app-card p-4">
                    <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                        <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.location') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->location ?: '-' }}</span></div>
                        <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.destinations.city_province') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ trim(($destination->city ?? '') . (($destination->city && $destination->province) ? ', ' : '') . ($destination->province ?? '')) ?: '-' }}</span></div>
                        <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.country') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->country ?: '-' }}</span></div>
                        <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.destinations.timezone') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->timezone ?: '-' }}</span></div>
                        <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.destinations.latitude') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->latitude ?? '-' }}</span></div>
                        <div><span class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.destinations.longitude') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->longitude ?? '-' }}</span></div>
                        <div class="md:col-span-2"><span class="text-gray-500 dark:text-gray-400">{{ __('ui.common.description') }}:</span> <span class="text-gray-800 dark:text-gray-100">{{ $destination->description ?: '-' }}</span></div>
                    </div>
                </div>

                <div class="app-card p-4 mb-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.destinations.linked_data') }}</h3>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ number_format($totalLinkedRecords) }} {{ __('ui.common.total') }}
                        </span>
                    </div>

                    @if ($totalLinkedRecords === 0)
                        <div class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            {{ __('ui.index.no_data_available', ['entity' => __('ui.modules.destinations.linked_data')]) }}
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($nonEmptyLinkedServiceGroups as $group)
                                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ $group['label'] }}</h4>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $group['tone'] }}">
                                            {{ number_format($group['items']->count()) }} {{ __('ui.modules.destinations.linked') }}
                                        </span>
                                    </div>

                                    @if ($group['items']->isEmpty())
                                        <div class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            {{ $group['empty'] }}
                                        </div>
                                    @else
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                                            @foreach ($group['items'] as $item)
                                                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 break-words">
                                                        @if ($group['key'] === 'island_transfers')
                                                            <a href="{{ route('island-transfers.show', $item) }}" class="hover:text-sky-700 dark:hover:text-sky-300">{{ $item->name ?: '-' }}</a>
                                                        @elseif ($group['key'] === 'activities')
                                                            <a href="{{ route('activities.show', $item) }}" class="hover:text-sky-700 dark:hover:text-sky-300">{{ $item->name ?: '-' }}</a>
                                                        @elseif ($group['key'] === 'hotels')
                                                            <a href="{{ route('hotels.show', $item) }}" class="hover:text-sky-700 dark:hover:text-sky-300">{{ $item->name ?: '-' }}</a>
                                                        @elseif ($group['key'] === 'airports')
                                                            <a href="{{ route('airports.show', $item) }}" class="hover:text-sky-700 dark:hover:text-sky-300">{{ $item->name ?: '-' }}</a>
                                                        @elseif ($group['key'] === 'transports')
                                                            <a href="{{ route('transports.show', $item) }}" class="hover:text-sky-700 dark:hover:text-sky-300">{{ $item->name ?: '-' }}</a>
                                                        @else
                                                            {{ $item->name ?: '-' }}
                                                        @endif
                                                    </div>

                                                    @if ($group['key'] === 'activities')
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item->vendor->name ?? '-' }}</div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.duration') }}: {{ (int) ($item->duration_minutes ?? 0) }} {{ __('ui.modules.destinations.minutes_short') }}</div>
                                                    @elseif ($group['key'] === 'island_transfers')
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item->departure_point_name ?: '-' }} -> {{ $item->arrival_point_name ?: '-' }}</div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.vendor') }}: {{ $item->vendor->name ?? '-' }} | {{ __('ui.common.duration') }}: {{ (int) ($item->duration_minutes ?? 0) }} {{ __('ui.modules.destinations.minutes_short') }} | {{ __('ui.modules.island_transfers.distance') }}: {{ number_format((float) ($item->distance_km ?? 0), 2, '.', '') }} km</div>
                                                    @elseif ($group['key'] === 'vendors')
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trim(($item->city ?? '') . (($item->city && $item->province) ? ', ' : '') . ($item->province ?? '')) ?: '-' }}</div>
                                                    @elseif ($group['key'] === 'hotels')
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trim(($item->city ?? '') . (($item->city && $item->province) ? ', ' : '') . ($item->province ?? '')) ?: '-' }}</div>
                                                    @elseif ($group['key'] === 'attractions')
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trim(($item->city ?? '') . (($item->city && $item->province) ? ', ' : '') . ($item->province ?? '')) ?: '-' }}</div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.duration') }}: {{ (int) ($item->ideal_visit_minutes ?? 0) }} {{ __('ui.modules.destinations.minutes_short') }}</div>
                                                    @elseif ($group['key'] === 'airports')
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item->code ?: '-' }}</div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trim(($item->city ?? '') . (($item->city && $item->province) ? ', ' : '') . ($item->province ?? '')) ?: '-' }}</div>
                                                    @elseif ($group['key'] === 'transports')
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item->vendor->name ?? '-' }}</div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item->code ?: '-' }} | {{ \Illuminate\Support\Str::headline((string) ($item->transport_type ?? '-')) }}</div>
                                                    @elseif ($group['key'] === 'food_beverages')
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item->vendor->name ?? '-' }}</div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::headline((string) ($item->service_type ?? '-')) }} | {{ (int) ($item->duration_minutes ?? 0) }} {{ __('ui.modules.destinations.minutes_short') }}</div>
                                                    @endif

                                                    <div class="mt-2">
                                                        @if (in_array($group['key'], ['island_transfers', 'activities', 'vendors', 'attractions', 'airports', 'transports', 'food_beverages'], true))
                                                            <x-status-badge :status="$item->is_active ? 'active' : 'inactive'" size="xs" />
                                                        @elseif ($group['key'] === 'hotels')
                                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                                {{ \Illuminate\Support\Str::title((string) ($item->status ?? '-')) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="app-card p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.destinations.province_map_title') }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.destinations.province_map_subtitle') }}</p>
                    <div
                        id="destination-province-map"
                        class="mt-3 h-[300px] overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                        data-province-geojson-url="{{ $provinceGeoJsonUrl }}"
                        data-destination-name="{{ $destination->name }}"
                        data-destination-province="{{ $destination->province }}"
                        @if (! is_null($destination->latitude)) data-destination-lat="{{ $destination->latitude }}" @endif
                        @if (! is_null($destination->longitude)) data-destination-lng="{{ $destination->longitude }}" @endif
                    ></div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" data-destination-province-map-hint></p>
                </div>
                @include('partials._audit-info', ['record' => $destination])
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const destinationProvinceMapMessages = {
            unavailable: @json(__('ui.modules.destinations.province_map_unavailable')),
            boundaryLoadFailed: @json(__('ui.modules.destinations.province_map_boundary_failed')),
            boundaryNotFound: @json(__('ui.modules.destinations.province_map_not_found')),
            overlayLoaded: @json(__('ui.modules.destinations.province_map_loaded')),
            renderFailed: @json(__('ui.modules.destinations.province_map_render_failed')),
            provinceLabel: @json(__('ui.modules.destinations.province_label')),
        };

        function normalizeDestinationProvinceName(value) {
            return String(value || '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, ' ')
                .trim()
                .replace(/\s+/g, ' ');
        }

        function resolveDestinationProvinceAlias(value) {
            const normalized = normalizeDestinationProvinceName(value);
            const aliases = {
                'dki jakarta': 'jakarta raya',
                'daerah khusus ibukota jakarta': 'jakarta raya',
                'di yogyakarta': 'yogyakarta',
                'daerah istimewa yogyakarta': 'yogyakarta',
                'kepulauan bangka belitung': 'bangka belitung',
                'bangka belitung': 'bangka belitung',
                'papua barat': 'irian jaya barat',
                'papua barat daya': 'irian jaya barat',
                'papua selatan': 'papua',
                'papua tengah': 'papua',
                'papua pegunungan': 'papua',
            };

            return aliases[normalized] || normalized;
        }

        async function initDestinationProvinceMap() {
            const mapElement = document.getElementById('destination-province-map');
            if (!mapElement || mapElement.dataset.mapBound === '1' || typeof window.L === 'undefined') {
                return;
            }

            mapElement.dataset.mapBound = '1';
            const hintNode = document.querySelector('[data-destination-province-map-hint]');
            const destinationName = String(mapElement.dataset.destinationName || 'Destination').trim();
            const destinationProvince = String(mapElement.dataset.destinationProvince || '').trim();
            const destinationProvinceKey = resolveDestinationProvinceAlias(destinationProvince);
            const destinationLat = Number.parseFloat(String(mapElement.dataset.destinationLat || '').trim());
            const destinationLng = Number.parseFloat(String(mapElement.dataset.destinationLng || '').trim());

            const map = window.L.map(mapElement, { zoomControl: true }).setView([-2.5489, 118.0149], 5);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const setHint = (text, tone = 'neutral') => {
                if (!hintNode) {
                    return;
                }
                hintNode.textContent = text;
                hintNode.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-rose-600', 'dark:text-rose-400', 'text-emerald-600', 'dark:text-emerald-400');
                if (tone === 'error') {
                    hintNode.classList.add('text-rose-600', 'dark:text-rose-400');
                    return;
                }
                if (tone === 'success') {
                    hintNode.classList.add('text-emerald-600', 'dark:text-emerald-400');
                    return;
                }
                hintNode.classList.add('text-gray-500', 'dark:text-gray-400');
            };

            const geoJsonUrl = String(mapElement.dataset.provinceGeojsonUrl || '').trim();
            if (!geoJsonUrl || !destinationProvinceKey) {
                setHint(destinationProvinceMapMessages.unavailable, 'error');
                return;
            }

            try {
                const response = await fetch(geoJsonUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    setHint(destinationProvinceMapMessages.boundaryLoadFailed, 'error');
                    return;
                }

                const geoJson = await response.json();
                const features = Array.isArray(geoJson?.features) ? geoJson.features : [];
                const matchedFeature = features.find((feature) => {
                    const name = String(feature?.properties?.NAME_1 || '').trim();
                    return resolveDestinationProvinceAlias(name) === destinationProvinceKey;
                });

                if (!matchedFeature) {
                    setHint(destinationProvinceMapMessages.boundaryNotFound, 'error');
                    return;
                }

                const overlay = window.L.geoJSON(matchedFeature, {
                    style: {
                        color: '#2563eb',
                        weight: 2,
                        opacity: 0.95,
                        fillColor: '#60a5fa',
                        fillOpacity: 0.2,
                    },
                }).addTo(map);

                const provinceName = String(matchedFeature?.properties?.NAME_1 || destinationProvince || '-');
                overlay.bindPopup(`
                    <div style="min-width:210px;">
                        <div style="font-weight:600;color:#0f172a;">${destinationName}</div>
                        <div style="margin-top:4px;color:#64748b;font-size:12px;">${destinationProvinceMapMessages.provinceLabel}: ${provinceName}</div>
                    </div>
                `);

                const markerIcon = window.L.divIcon({
                    className: 'destination-province-map-pin',
                    html: '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-blue-600 text-white"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });

                const bounds = overlay.getBounds();
                let markerLatLng = null;
                if (Number.isFinite(destinationLat) && Number.isFinite(destinationLng)) {
                    markerLatLng = [destinationLat, destinationLng];
                } else if (bounds && bounds.isValid()) {
                    const center = bounds.getCenter();
                    markerLatLng = [center.lat, center.lng];
                }

                if (markerLatLng) {
                    window.L.marker(markerLatLng, { icon: markerIcon })
                        .addTo(map)
                        .bindPopup(`
                            <div style="min-width:190px;">
                                <div style="font-weight:600;color:#0f172a;">${destinationName}</div>
                                <div style="margin-top:4px;color:#64748b;font-size:12px;">${destinationProvince || '-'}</div>
                            </div>
                        `);
                }

                if (bounds && bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.2));
                }

                setHint(destinationProvinceMapMessages.overlayLoaded, 'success');
            } catch (_) {
                setHint(destinationProvinceMapMessages.renderFailed, 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', initDestinationProvinceMap);
    </script>
@endpush
