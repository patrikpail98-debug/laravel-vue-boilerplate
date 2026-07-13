<template>
    <div class="min-h-screen flex items-center justify-center bg-base-200">
        <div class="card w-full max-w-md bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6">Overte svoj e-mail</h2>
                <p class="mb-4">Ďakujeme za registráciu! Pred pokračovaním prosím overte svoju e-mailovú
                    adresu kliknutím na odkaz, ktorý sme Vám práve poslali. Ak ste e-mail nedostali, radi Vám
                    pošleme ďalší.</p>
                <div v-if="verificationSent" class="alert alert-success mb-4">
                    Nový overovací odkaz bol odoslaný.
                </div>
                <button @click="resendVerification" class="btn btn-primary" :disabled="sending">
                    <span v-if="sending" class="loading loading-spinner"></span>
                    Znova odoslať overovací e-mail
                </button>
                <div class="justify-end card-actions">
                    <button class="btn btn-ghost" @click="logout">Odhlásiť sa</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import {useAuthStore} from '@/stores/auth';
import {useRouter} from 'vue-router';
import {useRoute} from 'vue-router';
import 'notyf/notyf.min.css';
import {showErrorToast} from "../constants/toast.js";

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const sending = ref(false);
const verificationSent = ref(false);

const logout = async () => {
    await authStore.logout();
    router.push('/login');
};

const resendVerification = async () => {
    // Falls back to the logged-in (but unverified) user's own email, since
    // the router guard sends people here with no :email param at all.
    const email = route.params.email || authStore.user?.email;

    if (!email) {
        showErrorToast('Nepodarilo sa zistiť e-mailovú adresu. Prihláste sa prosím znova.');
        return;
    }

    sending.value = true;
    verificationSent.value = false;
    try {
        await authStore.resendVerificationEmail(email);
        verificationSent.value = true;
    } catch (error) {
        showErrorToast('Overovací e-mail sa nepodarilo odoslať.');
    } finally {
        sending.value = false;
    }
};
</script>
