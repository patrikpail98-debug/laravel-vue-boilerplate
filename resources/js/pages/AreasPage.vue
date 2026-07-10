<template>
    <div>
        <!-- Hero -->
        <section class="bg-primary text-primary-content">
            <div class="max-w-6xl mx-auto px-4 py-14 md:py-20 text-center">
                <h1 class="text-3xl md:text-4xl font-bold mb-3">Rezervácia športovísk</h1>
                <p class="text-base md:text-lg max-w-2xl mx-auto opacity-90">
                    Vyberte si areál a ihrisko, ktoré chcete rezervovať. Rezervovať môžete aj bez registrácie.
                </p>
            </div>
        </section>

        <div class="max-w-6xl mx-auto px-4 py-10">
            <div v-if="loading" class="flex justify-center py-16">
                <span class="loading loading-spinner loading-lg"></span>
            </div>

            <div v-else class="space-y-12">
                <section v-for="area in areas" :key="area.id">
                    <div class="mb-5">
                        <h2 class="text-2xl font-bold text-primary">{{ area.name }}</h2>
                        <p class="text-sm text-base-content/70">{{ area.address }}</p>
                        <p v-if="area.description" class="mt-1 text-base-content/80">{{ area.description }}</p>
                    </div>

                    <div v-if="area.playgrounds?.length" class="grid gap-5 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
                        <div v-for="playground in area.playgrounds" :key="playground.id"
                             class="card bg-base-100 shadow hover:shadow-lg transition-shadow overflow-hidden">
                            <router-link :to="`/rezervacia/ihrisko/${playground.id}`" class="block">
                                <figure class="aspect-square bg-base-200">
                                    <img v-if="playground.image_url" :src="playground.image_url" :alt="playground.name"
                                         class="w-full h-full object-cover"/>
                                    <div v-else class="w-full h-full flex items-center justify-center text-base-content/30">
                                        <PhotoIcon class="w-10 h-10"/>
                                    </div>
                                </figure>
                            </router-link>

                            <div class="card-body p-4 gap-2">
                                <router-link :to="`/rezervacia/ihrisko/${playground.id}`" class="link link-hover">
                                    <h3 class="card-title text-base leading-tight">{{ playground.name }}</h3>
                                </router-link>

                                <router-link :to="`/rezervacia/${playground.id}`"
                                             class="btn btn-secondary btn-sm w-full justify-between">
                                    Rezervovať
                                    <span>{{ Number(playground.price_per_30min).toFixed(2) }} &euro; / 30 min</span>
                                </router-link>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-base-content/50">V tomto areáli momentálne nie sú dostupné žiadne ihriská.</p>
                </section>

                <p v-if="!areas.length" class="text-center text-base-content/60 py-16">Momentálne nie sú dostupné žiadne areály.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {PhotoIcon} from '@heroicons/vue/24/outline';
import http from '@/http.js';
import {showErrorToast} from '../constants/toast.js';
import 'notyf/notyf.min.css';

const areas = ref([]);
const loading = ref(true);

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
