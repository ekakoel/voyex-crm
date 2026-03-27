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

function initServiceMap() {
    const mapElement = document.getElementById('service-map-canvas');
    if (!mapElement || mapElement.dataset.mapBound === '1') {
        return;
    }

    mapElement.dataset.mapBound = '1';

    const markers = parseMarkers();
    const colorByType = {
        destination: '#2563eb',
        vendor: '#16a34a',
        activity: '#ea580c',
        'food-beverage': '#dc2626',
        hotel: '#7c3aed',
        airport: '#0f766e',
        transport: '#be123c',
        'tourist-attraction': '#4f46e5',
    };

    const map = L.map(mapElement, { zoomControl: true }).setView([-2.5489, 118.0149], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const markerStore = [];
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

        markerStore.push({ type: String(item.type || ''), marker });
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

    const toggles = Array.from(document.querySelectorAll('[data-map-type-toggle]'));
    const getEnabledTypes = () => new Set(
        toggles
            .filter((input) => input.checked)
            .map((input) => String(input.value || '').trim())
    );

    const fitToVisible = () => {
        const visible = markerStore
            .map((entry) => entry.marker)
            .filter((marker) => activeLayer.hasLayer(marker));
        if (!visible.length) {
            return;
        }
        const bounds = L.latLngBounds(visible.map((marker) => marker.getLatLng()));
        map.fitBounds(bounds.pad(0.2));
    };

    const applyFilter = (refit = false) => {
        const enabled = getEnabledTypes();
        activeLayer.clearLayers();

        markerStore.forEach((entry) => {
            if (enabled.has(entry.type)) {
                activeLayer.addLayer(entry.marker);
            }
        });

        if (refit) {
            fitToVisible();
        }
    };

    applyFilter(true);
    toggles.forEach((input) => {
        input.addEventListener('change', () => applyFilter(false));
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initServiceMap, { once: true });
} else {
    initServiceMap();
}
