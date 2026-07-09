<template>
    <div>
        <!-- Hero -->
        <section class="bg-primary text-primary-content">
            <div class="max-w-6xl mx-auto px-4 py-16 md:py-24 text-center">
                <h1 class="text-3xl md:text-5xl font-bold mb-4">Rezervujte si športovisko v Karlovej Vsi</h1>
                <p class="text-lg md:text-xl max-w-2xl mx-auto opacity-90 mb-8">
                    Tenisové kurty, multifunkčné ihriská a ďalšie športoviská mestskej časti si teraz môžete
                    rezervovať online, bez nutnosti registrácie.
                </p>
                <router-link to="/rezervacia" class="btn btn-secondary btn-lg">Rezervovať teraz</router-link>
            </div>
        </section>

        <!-- Facilities overview -->
        <section class="max-w-6xl mx-auto px-4 py-14">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-primary">Areály a ihriská</h2>
                <router-link to="/rezervacia" class="link link-primary text-sm">Zobraziť všetky &rarr;</router-link>
            </div>

            <div v-if="loading" class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>

            <div v-else-if="playgrounds.length" class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <router-link v-for="playground in playgrounds" :key="playground.id"
                             :to="`/rezervacia/ihrisko/${playground.id}`"
                             class="card bg-base-100 shadow hover:shadow-lg transition-shadow overflow-hidden">
                    <figure class="h-40 bg-base-200">
                        <img v-if="playground.image_url" :src="playground.image_url" :alt="playground.name" class="w-full h-full object-cover"/>
                        <div v-else class="w-full h-full flex items-center justify-center text-base-content/30 text-sm">
                            Bez fotografie
                        </div>
                    </figure>
                    <div class="card-body p-4">
                        <h3 class="card-title text-base">{{ playground.name }}</h3>
                        <p class="text-sm text-base-content/60">{{ playground.areaName }}</p>
                        <p class="text-sm font-medium text-primary">{{ Number(playground.price_per_30min).toFixed(2) }} &euro; / 30 min</p>
                    </div>
                </router-link>
            </div>

            <p v-else class="text-center text-base-content/60 py-16">Momentálne nie sú dostupné žiadne športoviská.</p>
        </section>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import http from '@/http.js';

const playgrounds = ref([]);
const loading = ref(true);

onMounted(async () => {
    document.title = 'Rezervácia športovísk – Karlova Ves';

    try {
        const response = await http.request('/api/areas');
        const areas = await response.json();

        playgrounds.value = areas
            .flatMap(area => (area.playgrounds ?? []).map(playground => ({...playground, areaName: area.name})))
            .slice(0, 6);
    } catch {
        playgrounds.value = [];
    } finally {
        loading.value = false;
    }
});
</script>
