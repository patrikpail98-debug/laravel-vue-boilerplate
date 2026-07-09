<template>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold text-primary mb-2">Mapa športovísk</h1>
        <p class="text-base-content/70 mb-6">Prehľad všetkých športovísk Mestskej časti Karlova Ves s možnosťou rezervácie.</p>

        <div v-if="loading" class="flex justify-center py-16">
            <span class="loading loading-spinner loading-lg"></span>
        </div>

        <template v-else>
            <FacilityMap :markers="markers" height="500px" :zoom="14"/>
            <p v-if="!markers.length" class="text-center text-base-content/60 mt-4">
                Zatiaľ nie sú k dispozícii súradnice žiadneho športoviska.
            </p>
        </template>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import http from '@/http.js';
import FacilityMap from '@/components/ui/FacilityMap.vue';

const markers = ref([]);
const loading = ref(true);

onMounted(async () => {
    document.title = 'Mapa športovísk – Karlova Ves';

    try {
        const response = await http.request('/api/areas');
        const areas = await response.json();

        markers.value = areas.flatMap(area => (area.playgrounds ?? []).map(playground => ({
            id: playground.id,
            name: playground.name,
            subtitle: area.name,
            latitude: playground.latitude,
            longitude: playground.longitude,
            url: `/rezervacia/ihrisko/${playground.id}`,
        })));
    } catch {
        markers.value = [];
    } finally {
        loading.value = false;
    }
});
</script>
