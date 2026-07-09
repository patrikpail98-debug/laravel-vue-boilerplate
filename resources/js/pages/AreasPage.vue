<template>
    <div class="min-h-screen bg-base-200 py-10 px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-primary mb-2">Rezervácia športovísk</h1>
            <p class="text-base-content/70 mb-8">Vyberte si areál a ihrisko, ktoré chcete rezervovať. Rezervovať môžete aj bez registrácie.</p>

            <div v-if="loading" class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>

            <div v-else class="space-y-6">
                <div v-for="area in areas" :key="area.id" class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title text-primary">{{ area.name }}</h2>
                        <p class="text-sm text-base-content/70">{{ area.address }}</p>
                        <p v-if="area.description" class="mt-2">{{ area.description }}</p>

                        <div v-if="area.playgrounds?.length" class="mt-4 grid gap-3 sm:grid-cols-2">
                            <router-link v-for="playground in area.playgrounds" :key="playground.id"
                                         :to="`/rezervacia/ihrisko/${playground.id}`"
                                         class="btn btn-outline btn-primary justify-between">
                                {{ playground.name }}
                                <span class="badge badge-secondary">{{ Number(playground.price_per_30min).toFixed(2) }} &euro; / 30 min</span>
                            </router-link>
                        </div>
                        <p v-else class="mt-4 text-sm text-base-content/50">V tomto areáli momentálne nie sú dostupné žiadne ihriská.</p>
                    </div>
                </div>

                <p v-if="!areas.length" class="text-center text-base-content/60 py-16">Momentálne nie sú dostupné žiadne areály.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import http from '@/http.js';
import {showErrorToast} from '../constants/toast.js';
import 'notyf/notyf.min.css';

const areas = ref([]);
const loading = ref(true);

onMounted(async () => {
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
