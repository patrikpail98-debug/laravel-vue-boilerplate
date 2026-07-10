<template>
    <div class="max-w-4xl mx-auto px-4 py-10">
        <router-link to="/rezervacia" class="link link-primary text-sm mb-4 inline-block">&larr; Späť na zoznam ihrísk</router-link>

        <div v-if="loading" class="flex justify-center py-16">
            <span class="loading loading-spinner loading-lg"></span>
        </div>

        <template v-else-if="playground">
            <div class="card bg-base-100 shadow-xl overflow-hidden">
                <figure class="h-64 bg-base-200">
                    <img v-if="playground.image_url" :src="playground.image_url" :alt="playground.name" class="w-full h-full object-cover"/>
                    <div v-else class="w-full h-full flex items-center justify-center text-base-content/30">Bez fotografie</div>
                </figure>

                <div class="card-body">
                    <h1 class="text-3xl font-bold text-primary">{{ playground.name }}</h1>
                    <p class="text-base-content/60">{{ playground.area?.name }}<span v-if="playground.area?.address">, {{ playground.area.address }}</span></p>

                    <p v-if="playground.description" class="mt-4 leading-relaxed">{{ playground.description }}</p>

                    <div class="mt-6 grid gap-8 md:grid-cols-2">
                        <div>
                            <h2 class="text-lg font-semibold text-primary mb-2">Cenník</h2>
                            <table class="table">
                                <tbody>
                                <tr>
                                    <td>Cena za 30 minút</td>
                                    <td class="font-bold text-right">{{ Number(playground.price_per_30min).toFixed(2) }} &euro;</td>
                                </tr>
                                <tr>
                                    <td>Maximálna dĺžka rezervácie</td>
                                    <td class="text-right">{{ playground.max_duration_minutes }} min</td>
                                </tr>
                                <tr>
                                    <td>Rezervovať dopredu</td>
                                    <td class="text-right">{{ playground.max_horizon_days }} dní</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h2 class="text-lg font-semibold text-primary mb-2">Otváracie hodiny</h2>
                            <table v-if="playground.opening_hours" class="table">
                                <tbody>
                                <tr v-for="day in weekDays" :key="day.key">
                                    <td>{{ day.label }}</td>
                                    <td class="text-right">
                                        <span v-if="playground.opening_hours[day.key]?.is_closed" class="text-base-content/50">Zatvorené</span>
                                        <span v-else-if="playground.opening_hours[day.key]?.opens_at">
                                            {{ playground.opening_hours[day.key].opens_at }} &ndash; {{ playground.opening_hours[day.key].closes_at }}
                                        </span>
                                        <span v-else class="text-base-content/50">Neuvedené</span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <p v-else class="text-base-content/60 text-sm">Otváracie hodiny budú doplnené.</p>
                        </div>
                    </div>

                    <template v-if="hasCoordinates">
                        <FacilityMap :markers="[marker]" :center="{lat: marker.latitude, lng: marker.longitude}"
                                     :zoom="16" height="300px" class="mt-6" :hide-list-on-desktop="false"/>

                        <a :href="navigationUrl" target="_blank" rel="noopener" class="btn btn-outline btn-primary mt-4">
                            <MapPinIcon class="w-5 h-5"/>
                            Navigovať
                        </a>
                    </template>

                    <div class="card-actions justify-end mt-8">
                        <router-link :to="`/rezervacia/${playground.id}`" class="btn btn-primary btn-lg">
                            Rezervovať ihrisko
                        </router-link>
                    </div>
                </div>
            </div>
        </template>

        <p v-else class="text-center text-base-content/60 py-16">Ihrisko sa nepodarilo nájsť.</p>
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import {useRoute} from 'vue-router';
import {MapPinIcon} from '@heroicons/vue/24/outline';
import http from '@/http.js';
import FacilityMap from '@/components/ui/FacilityMap.vue';
import {showErrorToast} from '../constants/toast.js';

const route = useRoute();
const playground = ref(null);
const loading = ref(true);

const weekDays = [
    {key: 'mon', label: 'Pondelok'},
    {key: 'tue', label: 'Utorok'},
    {key: 'wed', label: 'Streda'},
    {key: 'thu', label: 'Štvrtok'},
    {key: 'fri', label: 'Piatok'},
    {key: 'sat', label: 'Sobota'},
    {key: 'sun', label: 'Nedeľa'},
];

const hasCoordinates = computed(() => playground.value?.latitude !== null && playground.value?.latitude !== undefined
    && playground.value?.longitude !== null && playground.value?.longitude !== undefined);

const marker = computed(() => ({
    id: playground.value.id,
    name: playground.value.name,
    subtitle: playground.value.area?.name,
    latitude: Number(playground.value.latitude),
    longitude: Number(playground.value.longitude),
}));

// Opens the visitor's default maps app with turn-by-turn navigation to the
// facility (Google Maps handles this URL on both desktop and mobile).
const navigationUrl = computed(() =>
    `https://www.google.com/maps/dir/?api=1&destination=${marker.value.latitude},${marker.value.longitude}`
);

onMounted(async () => {
    try {
        const response = await http.request(`/api/playgrounds/${route.params.playgroundId}`);
        if (!response.ok) {
            throw new Error('Ihrisko sa nepodarilo nájsť.');
        }
        playground.value = await response.json();
        document.title = `${playground.value.name} – Karlova Ves`;
    } catch (error) {
        showErrorToast(error.message ?? 'Ihrisko sa nepodarilo nájsť.');
    } finally {
        loading.value = false;
    }
});
</script>
