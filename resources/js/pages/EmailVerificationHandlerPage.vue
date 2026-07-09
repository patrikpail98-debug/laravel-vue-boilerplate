<template>
    <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
        <div class="card w-full max-w-lg bg-base-100 shadow-xl">
            <div class="card-body items-center text-center">
                <h2 class="card-title text-2xl mb-4">Overenie e-mailu</h2>

                <div v-if="loading" class="py-8">
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                    <p class="mt-4">Overujeme Váš e-mail...</p>
                </div>

                <div v-if="successMessage" class="py-8">
                    <div class="text-green-500 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-lg">{{ successMessage }}</p>
                    <p class="mt-2">Teraz sa môžete prihlásiť.</p>
                    <router-link to="/login" class="btn btn-primary mt-6">Prejsť na prihlásenie</router-link>
                </div>

                <div v-if="errorMessage" class="py-8">
                    <div class="text-red-500 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-lg text-error">{{ errorMessage }}</p>
                    <p class="mt-2">Skúste znova odoslať overovací e-mail, alebo kontaktujte administrátora.</p>
                    <router-link to="/login" class="btn btn-secondary mt-6">Späť na prihlásenie</router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import {useRoute} from 'vue-router';
import http from '@/http.js';

const route = useRoute();
const loading = ref(true);
const successMessage = ref('');
const errorMessage = ref('');

onMounted(async () => {
    const {id, hash} = route.params;
    const {expires, signature} = route.query;

    if (!id || !hash || !expires || !signature) {
        errorMessage.value = 'Neplatný alebo neúplný overovací odkaz.';
        loading.value = false;
        return;
    }

    try {
        const verificationUrl = `/api/auth/email/verify/${id}/${hash}?expires=${expires}&signature=${signature}`;
        const response = await http.request(verificationUrl);

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Overenie zlyhalo');
        }

        successMessage.value = data.message || 'Váš e-mail bol úspešne overený!';

    } catch (error) {
        errorMessage.value = 'Overovací odkaz je neplatný.';
        console.error('Verification failed:', error);
    } finally {
        loading.value = false;
    }
});
</script>
