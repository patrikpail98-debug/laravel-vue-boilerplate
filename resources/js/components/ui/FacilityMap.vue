<!-- resources/js/components/ui/FacilityMap.vue -->
<template>
    <div>
        <div ref="mapEl" class="w-full rounded-box border border-base-300" :style="{height}" role="img"
             :aria-label="`Mapa: ${markers.map(m => m.name).join(', ')}`"></div>

        <!-- Accessible text alternative to the map for screen reader / keyboard users -->
        <ul class="mt-4 grid gap-2 sm:grid-cols-2" :class="{'sr-only': hideListOnDesktop}">
            <li v-for="marker in validMarkers" :key="marker.id" class="text-sm">
                <router-link v-if="marker.url" :to="marker.url" class="link link-primary font-medium">{{ marker.name }}</router-link>
                <span v-else class="font-medium">{{ marker.name }}</span>
                <span v-if="marker.subtitle" class="text-base-content/60"> &ndash; {{ marker.subtitle }}</span>
            </li>
        </ul>
    </div>
</template>

<script setup>
import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

const props = defineProps({
    markers: {type: Array, default: () => []}, // [{id, name, subtitle, latitude, longitude, url}]
    center: {type: Object, default: null}, // {lat, lng}
    zoom: {type: Number, default: 13},
    height: {type: String, default: '400px'},
    hideListOnDesktop: {type: Boolean, default: true},
});

const DEFAULT_CENTER = {lat: 48.1717, lng: 17.0574}; // Karlova Ves

const mapEl = ref(null);
let map = null;
let markerLayer = null;

const validMarkers = computed(() =>
    props.markers.filter(m => m.latitude !== null && m.latitude !== undefined && m.longitude !== null && m.longitude !== undefined)
);

const pinIcon = L.divIcon({
    className: '',
    html: `<svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M16 0C7.163 0 0 7.163 0 16c0 11 16 24 16 24s16-13 16-24c0-8.837-7.163-16-16-16z" fill="#003366"/>
        <circle cx="16" cy="16" r="7" fill="#FFCC00"/>
    </svg>`,
    iconSize: [32, 40],
    iconAnchor: [16, 40],
    popupAnchor: [0, -36],
});

const resolveCenter = () => {
    if (props.center) return props.center;
    if (validMarkers.value.length) {
        const avgLat = validMarkers.value.reduce((sum, m) => sum + Number(m.latitude), 0) / validMarkers.value.length;
        const avgLng = validMarkers.value.reduce((sum, m) => sum + Number(m.longitude), 0) / validMarkers.value.length;
        return {lat: avgLat, lng: avgLng};
    }
    return DEFAULT_CENTER;
};

const renderMarkers = () => {
    if (!map) return;

    if (markerLayer) {
        markerLayer.remove();
    }

    markerLayer = L.layerGroup();

    validMarkers.value.forEach((marker) => {
        const popupHtml = `<strong>${escapeHtml(marker.name)}</strong>` +
            (marker.subtitle ? `<br>${escapeHtml(marker.subtitle)}` : '') +
            (marker.url ? `<br><a href="${escapeHtml(marker.url)}">Rezervovať &rarr;</a>` : '');

        L.marker([marker.latitude, marker.longitude], {icon: pinIcon, title: marker.name})
            .bindPopup(popupHtml)
            .addTo(markerLayer);
    });

    markerLayer.addTo(map);
};

const escapeHtml = (str) => String(str).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[c]));

onMounted(() => {
    const c = resolveCenter();
    map = L.map(mapEl.value, {scrollWheelZoom: false}).setView([c.lat, c.lng], props.zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> prispievatelia',
        maxZoom: 19,
    }).addTo(map);

    renderMarkers();
});

watch(() => props.markers, () => {
    renderMarkers();
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>
