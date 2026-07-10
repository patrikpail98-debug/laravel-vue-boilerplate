<template>
    <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
        <div class="card w-full max-w-lg bg-base-100 shadow-xl">
            <div class="card-body items-center text-center">
                <h2 class="card-title text-2xl mb-4">Platba za rezerváciu</h2>

                <div v-if="loading" class="py-8">
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                    <p class="mt-4">Overujeme výsledok platby...</p>
                </div>

                <div v-else-if="status === 'approved'" class="py-8">
                    <div class="text-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-lg">Ďakujeme, platba bola prijatá a rezervácia je potvrdená. Potvrdenie sme Vám poslali e-mailom.</p>
                    <router-link to="/rezervacia" class="btn btn-primary mt-6">Späť na ihriská</router-link>
                </div>

                <div v-else-if="status === 'awaiting_payment'" class="py-8">
                    <p class="text-lg">Platbu ešte len spracúvame. Ak ste práve dokončili platbu na bráne, môže to chvíľu trvať.</p>
                    <button class="btn btn-outline btn-primary mt-6" :disabled="loading" @click="checkStatus">
                        Skontrolovať znova
                    </button>
                </div>

                <div v-else class="py-8">
                    <div class="text-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-lg text-error">{{ errorMessage }}</p>
                    <router-link :to="retryLink" class="btn btn-secondary mt-6">Skúsiť znova</router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import {useRoute} from 'vue-router';
import http from '@/http.js';

const route = useRoute();
const loading = ref(true);
const status = ref('');
const errorMessage = ref('');
const playgroundId = ref(null);

const retryLink = computed(() => playgroundId.value ? `/rezervacia/${playgroundId.value}` : '/rezervacia');

const checkStatus = async () => {
    const reservationId = route.query.reservation_id;
    const orderId = route.query.order_id;

    if (!reservationId || !orderId) {
        status.value = 'error';
        errorMessage.value = 'Chýbajú údaje o platbe.';
        loading.value = false;
        return;
    }

    loading.value = true;
    try {
        const response = await http.request(`/api/reservations/${reservationId}/payment-status?order_id=${encodeURIComponent(orderId)}`);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Overenie platby zlyhalo.');
        }

        status.value = data.status;
        playgroundId.value = data.reservation?.playground_id ?? null;

        if (data.status === 'cancelled' || data.status === 'rejected') {
            errorMessage.value = 'Platba sa nepodarila alebo bola zrušená. Termín bol uvoľnený, skúste to prosím znova.';
        }
    } catch (error) {
        status.value = 'error';
        errorMessage.value = error.message || 'Overenie platby zlyhalo.';
    } finally {
        loading.value = false;
    }
};

onMounted(checkStatus);
</script>
