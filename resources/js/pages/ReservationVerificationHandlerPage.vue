<template>
    <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
        <div class="card w-full max-w-lg bg-base-100 shadow-xl">
            <div class="card-body items-center text-center">
                <h2 class="card-title text-2xl mb-4">Potvrdenie rezervácie</h2>

                <div v-if="loading" class="py-8">
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                    <p class="mt-4">Overujeme Vašu rezerváciu...</p>
                </div>

                <div v-if="successMessage" class="py-8">
                    <div class="text-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-lg">{{ successMessage }}</p>
                    <router-link to="/rezervacia" class="btn btn-primary mt-6">Späť na ihriská</router-link>
                </div>

                <div v-if="errorMessage" class="py-8">
                    <div class="text-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-lg text-error">{{ errorMessage }}</p>
                    <router-link to="/rezervacia" class="btn btn-secondary mt-6">Späť na ihriská</router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {useRoute} from 'vue-router';
import http from '@/http.js';

const route = useRoute();
const loading = ref(true);
const successMessage = ref('');
const errorMessage = ref('');

onMounted(async () => {
    const {id, token} = route.params;

    try {
        const response = await http.request(`/api/reservations/${id}/verify/${token}`, {method: 'POST'});
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Overenie sa nepodarilo.');
        }

        successMessage.value = data.message || 'Rezervácia bola úspešne overená!';
    } catch (error) {
        errorMessage.value = error.message || 'Odkaz na overenie je neplatný.';
    } finally {
        loading.value = false;
    }
});
</script>
