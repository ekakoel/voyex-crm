import L from 'leaflet';
import 'leaflet.markercluster';

function parseMarkers() {
    const markersNode = document.getElementById('service-map-markers');
    if (!markersNode) {
        return [];
    }
    try {
        const parsed = JSON.parse(String(markersNode.textContent || '[]'));
        return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
        return [];
    }
}

function parseRoutes() {
    const routesNode = document.getElementById('service-map-routes');
    if (!routesNode) {
        return [];
    }
    try {
        const parsed = JSON.parse(String(routesNode.textContent || '[]'));
        return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
        return [];
    }
}

function normalizeProvinceName(value) {
    return String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, ' ')
        .trim()
        .replace(/\s+/g, ' ');
}

function resolveDestinationProvinceKey(value) {
    const base = normalizeProvinceName(value);
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

    return aliases[base] || base;
}

function initServiceMap() {
    const mapElement = document.getElementById('service-map-canvas');
    if (!mapElement || mapElement.dataset.mapBound === '1') {
        return;
    }

    mapElement.dataset.mapBound = '1';

    const markers = parseMarkers();
    const routes = parseRoutes();
    const colorByType = {
        destination: '#2563eb',
        vendor: '#16a34a',
        activity: '#ea580c',
        'food-beverage': '#dc2626',
        hotel: '#7c3aed',
        airport: '#0f766e',
        transport: '#be123c',
        'tourist-attraction': '#4f46e5',
        'island-transfer': '#0284c7',
    };

    const map = L.map(mapElement, { zoomControl: true }).setView([-2.5489, 118.0149], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const markerStore = [];
    const destinationEntries = [];
    markers.forEach((item) => {
        const lat = Number(item.latitude);
        const lng = Number(item.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        const icon = L.divIcon({
            className: 'service-map-div-icon',
            html: `<span class="service-map-marker" style="background:${colorByType[item.type] || '#334155'}"><i class="fa-solid ${item.icon || 'fa-location-dot'}"></i></span>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -12],
        });

        const marker = L.marker([lat, lng], { icon });
        const subtitle = String(item.subtitle || '').trim();
        const subtitleHtml = subtitle !== '' ? `<div style="margin-top:4px;color:#64748b;font-size:12px;">${subtitle}</div>` : '';
        marker.bindPopup(`
            <div style="min-width:190px;">
                <div style="font-weight:600;color:#0f172a;">${item.name || '-'}</div>
                ${subtitleHtml}
                <a href="${item.url || '#'}" style="display:inline-block;margin-top:8px;font-size:12px;color:#2563eb;text-decoration:underline;">Open detail</a>
            </div>
        `);

        const type = String(item.type || '');
        markerStore.push({ type, marker });
        if (type === 'destination') {
            destinationEntries.push({
                lat,
                lng,
                name: String(item.name || 'Destination'),
                province: String(item.province || item.subtitle || ''),
                url: String(item.url || '#'),
            });
        }
    });

    const routeStore = [];
    routes.forEach((item) => {
        const points = Array.isArray(item.coordinates) ? item.coordinates : [];
        const latLngs = points
            .map((pair) => Array.isArray(pair) ? [Number(pair[0]), Number(pair[1])] : null)
            .filter((pair) => Array.isArray(pair) && Number.isFinite(pair[0]) && Number.isFinite(pair[1]));
        if (latLngs.length < 2) {
            return;
        }

        const type = String(item.type || 'island-transfer');
        const color = colorByType[type] || '#0284c7';
        const isFallback = Boolean(item.is_fallback);
        const polyline = L.polyline(latLngs, {
            color,
            weight: 4,
            opacity: 0.78,
            dashArray: isFallback ? '8 8' : undefined,
            lineCap: 'round',
            lineJoin: 'round',
        });

        const fromLabel = String(item.from || '').trim();
        const toLabel = String(item.to || '').trim();
        const subtitle = [fromLabel, toLabel].filter((value) => value !== '').join(' -> ');
        polyline.bindPopup(`
            <div style="min-width:220px;">
                <div style="font-weight:600;color:#0f172a;">${item.name || 'Island Transfer'}</div>
                ${subtitle ? `<div style="margin-top:4px;color:#64748b;font-size:12px;">${subtitle}</div>` : ''}
                <a href="${item.url || '#'}" style="display:inline-block;margin-top:8px;font-size:12px;color:#2563eb;text-decoration:underline;">Open detail</a>
            </div>
        `);

        routeStore.push({ type, polyline });
    });

    const activeLayer = typeof L.markerClusterGroup === 'function'
        ? L.markerClusterGroup({
            chunkedLoading: true,
            showCoverageOnHover: false,
            spiderfyOnMaxZoom: true,
            maxClusterRadius: 55,
        })
        : L.layerGroup();

    activeLayer.addTo(map);
    const routeLayer = L.layerGroup().addTo(map);
    const destinationOverlayLayer = L.layerGroup().addTo(map);
    const destinationOverlays = [];
    let destinationOverlaysReady = false;

    const toggles = Array.from(document.querySelectorAll('[data-map-type-toggle]'));
    const getEnabledTypes = () => new Set(
        toggles
            .filter((input) => input.checked)
            .map((input) => String(input.value || '').trim())
    );

    const loadDestinationOverlays = async () => {
        if (!destinationEntries.length) {
            destinationOverlaysReady = true;
            return;
        }

        const geoJsonUrl = String(mapElement.dataset.provinceGeojsonUrl || '').trim();
        if (geoJsonUrl === '') {
            destinationOverlaysReady = true;
            return;
        }

        try {
            const response = await fetch(geoJsonUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                destinationOverlaysReady = true;
                return;
            }

            const geoJson = await response.json();
            const features = Array.isArray(geoJson?.features) ? geoJson.features : [];
            const featureByProvinceKey = new Map();
            features.forEach((feature) => {
                const featureProvinceName = String(feature?.properties?.NAME_1 || '').trim();
                const key = resolveDestinationProvinceKey(featureProvinceName);
                if (key !== '' && !featureByProvinceKey.has(key)) {
                    featureByProvinceKey.set(key, feature);
                }
            });

            const destinationGroups = new Map();
            destinationEntries.forEach((destination) => {
                const key = resolveDestinationProvinceKey(destination.province);
                if (key === '') {
                    return;
                }

                if (!destinationGroups.has(key)) {
                    destinationGroups.set(key, []);
                }
                destinationGroups.get(key).push(destination);
            });

            destinationGroups.forEach((destinations, key) => {
                const matchedFeature = featureByProvinceKey.get(key);
                if (!matchedFeature) {
                    return;
                }

                const overlay = L.geoJSON(matchedFeature, {
                    style: {
                        color: '#2563eb',
                        weight: 1.8,
                        opacity: 0.95,
                        fillColor: '#60a5fa',
                        fillOpacity: 0.20,
                    },
                });

                const properties = matchedFeature.properties || {};
                const provinceName = String(properties.NAME_1 || '-');
                const destinationList = destinations
                    .map((destination) => destination.name)
                    .filter((name, index, arr) => name && arr.indexOf(name) === index)
                    .slice(0, 8);
                const destinationHtml = destinationList.length
                    ? destinationList.map((name) => `<li style="margin:2px 0;">${name}</li>`).join('')
                    : '<li>-</li>';
                overlay.bindPopup(`
                    <div style="min-width:220px;">
                        <div style="font-weight:600;color:#0f172a;">Province Overlay</div>
                        <div style="margin-top:2px;color:#64748b;font-size:12px;">Provinsi: ${provinceName}</div>
                        <div style="margin-top:6px;color:#334155;font-size:12px;">Destination linked: ${destinations.length}</div>
                        <ul style="margin:4px 0 0 16px;padding:0;color:#334155;font-size:12px;">
                            ${destinationHtml}
                        </ul>
                    </div>
                `);

                destinationOverlays.push(overlay);
            });
        } catch (_) {
            destinationOverlaysReady = true;
            return;
        }

        destinationOverlaysReady = true;
    };

    const fitToVisible = () => {
        const visibleMarkerPoints = markerStore
            .map((entry) => entry.marker)
            .filter((marker) => activeLayer.hasLayer(marker));
        const visibleRoutePoints = routeStore
            .map((entry) => entry.polyline)
            .filter((polyline) => routeLayer.hasLayer(polyline))
            .flatMap((polyline) => polyline.getLatLngs())
            .filter((point) => point && Number.isFinite(point.lat) && Number.isFinite(point.lng));
        const overlayBounds = destinationOverlays
            .filter((overlay) => destinationOverlayLayer.hasLayer(overlay))
            .map((overlay) => overlay.getBounds())
            .filter((bounds) => bounds && bounds.isValid());

        if (!visibleMarkerPoints.length && !visibleRoutePoints.length && !overlayBounds.length) {
            return;
        }

        const bounds = L.latLngBounds([]);
        visibleMarkerPoints.forEach((marker) => bounds.extend(marker.getLatLng()));
        visibleRoutePoints.forEach((point) => bounds.extend(point));
        overlayBounds.forEach((overlayBoundsItem) => bounds.extend(overlayBoundsItem));
        if (bounds.isValid()) {
            map.fitBounds(bounds.pad(0.2));
        }
    };

    const applyFilter = (refit = false) => {
        const enabled = getEnabledTypes();
        activeLayer.clearLayers();
        routeLayer.clearLayers();
        destinationOverlayLayer.clearLayers();

        markerStore.forEach((entry) => {
            if (enabled.has(entry.type)) {
                activeLayer.addLayer(entry.marker);
            }
        });
        routeStore.forEach((entry) => {
            if (enabled.has(entry.type)) {
                routeLayer.addLayer(entry.polyline);
            }
        });
        if (destinationOverlaysReady && enabled.has('destination')) {
            destinationOverlays.forEach((overlay) => {
                destinationOverlayLayer.addLayer(overlay);
            });
        }

        if (refit) {
            fitToVisible();
        }
    };

    applyFilter(true);
    loadDestinationOverlays().then(() => {
        applyFilter(true);
    });
    toggles.forEach((input) => {
        input.addEventListener('change', () => applyFilter(false));
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initServiceMap, { once: true });
} else {
    initServiceMap();
}
