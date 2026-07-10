<template>
    <div>
        <!-- Hero -->
        <section class="bg-primary text-primary-content">
            <div class="max-w-6xl mx-auto px-4 py-10 md:py-14 text-center">
                <h1 class="text-3xl md:text-4xl font-bold mb-3">Rezervácia športovísk</h1>
                <p class="text-base md:text-lg max-w-2xl mx-auto opacity-90">
                    Vyberte si areál a ihrisko, ktoré chcete rezervovať. Rezervovať môžete aj bez registrácie.
                </p>
            </div>
        </section>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div v-if="loading" class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>

            <template v-else>
                <!-- Area filter -->
                <div v-if="areas.length > 1" class="flex gap-2 overflow-x-auto pb-2 mb-6">
                    <button type="button" class="btn btn-sm shrink-0" :class="selectedAreaId === null ? 'btn-primary' : 'btn-outline'"
                            @click="selectArea(null)">
                        Všetky areály
                        <span class="badge badge-sm" :class="selectedAreaId === null ? 'badge-neutral' : 'badge-ghost'">{{ totalPlaygroundCount }}</span>
                    </button>
                    <button v-for="area in areas" :key="area.id" type="button" class="btn btn-sm shrink-0"
                            :class="selectedAreaId === area.id ? 'btn-primary' : 'btn-outline'"
                            @click="selectArea(area.id)">
                        {{ area.name }}
                        <span class="badge badge-sm" :class="selectedAreaId === area.id ? 'badge-neutral' : 'badge-ghost'">{{ area.playgrounds?.length ?? 0 }}</span>
                    </button>
                </div>

                <!-- Selected area info -->
                <div v-if="selectedArea" class="mb-6">
                    <h2 class="text-xl font-bold text-primary">{{ selectedArea.name }}</h2>
                    <p class="text-sm text-base-content/70">{{ selectedArea.address }}</p>
                    <p v-if="selectedArea.description" class="mt-1 text-base-content/80">{{ selectedArea.description }}</p>
                </div>

                <div v-if="visiblePlaygrounds.length" class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <div v-for="playground in visiblePlaygrounds" :key="playground.id"
                         class="card bg-base-100 shadow hover:shadow-lg transition-shadow overflow-hidden">
                        <router-link :to="`/rezervacia/ihrisko/${playground.id}`" class="block">
                            <figure class="aspect-[4/3] bg-base-200">
                                <img v-if="playground.image_url" :src="playground.image_url" :alt="playground.name"
                                     class="w-full h-full object-cover"/>
                                <div v-else class="w-full h-full flex items-center justify-center text-base-content/30">
                                    <PhotoIcon class="w-8 h-8"/>
                                </div>
                            </figure>
                        </router-link>

                        <div class="card-body p-3 gap-1.5">
                            <router-link :to="`/rezervacia/ihrisko/${playground.id}`" class="link link-hover">
                                <h3 class="card-title text-sm leading-tight">{{ playground.name }}</h3>
                            </router-link>
                            <p v-if="!selectedArea" class="text-xs text-base-content/60 -mt-1">{{ playground.areaName }}</p>

                            <router-link :to="`/rezervacia/${playground.id}`"
                                         class="btn btn-secondary btn-xs w-full justify-between mt-1">
                                Rezervovať
                                <span>{{ Number(playground.price_per_30min).toFixed(2) }} &euro; / 30 min</span>
                            </router-link>
                        </div>
                    </div>
                </div>
                <p v-else class="text-center text-base-content/60 py-16">
                    {{ areas.length ? 'V tomto areáli momentálne nie sú dostupné žiadne ihriská.' : 'Momentálne nie sú dostupné žiadne areály.' }}
                </p>

                <div v-if="hasMore" class="flex justify-center mt-8">
                    <button type="button" class="btn btn-outline btn-primary" @click="pageSize += PAGE_STEP">
                        Zobraziť ďalšie ihriská
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {PhotoIcon} from '@heroicons/vue/24/outline';
import http from '@/http.js';
import {showErrorToast} from '../constants/toast.js';
import 'notyf/notyf.min.css';

const PAGE_STEP = 15;

const areas = ref([]);
const loading = ref(true);
const selectedAreaId = ref(null);
const pageSize = ref(PAGE_STEP);

const selectedArea = computed(() => areas.value.find(area => area.id === selectedAreaId.value) ?? null);

const totalPlaygroundCount = computed(() =>
    areas.value.reduce((sum, area) => sum + (area.playgrounds?.length ?? 0), 0)
);

// Flattened, area-tagged list so "Všetky areály" can render one dense grid
// instead of stacking a full section per area (which is what made the page
// grow tall once there were several areas).
const filteredPlaygrounds = computed(() => {
    const source = selectedArea.value ? [selectedArea.value] : areas.value;
    return source.flatMap(area => (area.playgrounds ?? []).map(playground => ({...playground, areaName: area.name})));
});

const visiblePlaygrounds = computed(() => filteredPlaygrounds.value.slice(0, pageSize.value));
const hasMore = computed(() => pageSize.value < filteredPlaygrounds.value.length);

const selectArea = (areaId) => {
    selectedAreaId.value = areaId;
};

watch(selectedAreaId, () => {
    pageSize.value = PAGE_STEP;
});

onMounted(async () => {
    document.title = 'Rezervácia športovísk – Karlova Ves';

    try {
        const response = await http.request('/api/areas');
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message ?? 'Nepodarilo sa načítať areály.');
        }
        areas.value = data;
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať areály.');
    } finally {
        loading.value = false;
    }
});
</script>
