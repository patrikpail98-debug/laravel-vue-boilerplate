<template>
    <div class="min-h-screen flex items-center justify-center bg-base-200">
        <div class="card w-full max-w-md bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-2">Dvojfaktorové overenie</h2>
                <p class="mb-6 text-sm">
                    Zadajte overovací kód odoslaný do
                    <strong v-if="authStore.tfaMethod === 'app'">Vašej autentifikačnej aplikácie</strong>
                    <strong v-if="authStore.tfaMethod === 'email'">na Vašu e-mailovú adresu</strong>.
                </p>
                <form @submit.prevent="verifyCode">
                    <div class="form-control mb-4">
                        <input type="text" v-model="code"
                               class="input input-bordered w-full text-center tracking-[0.5em]" placeholder="123456"
                               required maxlength="6"/>
                    </div>
                    <div class="form-control">
                        <button class="btn btn-block btn-primary" :disabled="loading">
                            <span v-if="loading" class="loading loading-spinner"></span>
                            Overiť
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import {useAuthStore} from '@/stores/auth';
import {useRouter} from 'vue-router';
import 'notyf/notyf.min.css';
import {showErrorToast} from '../constants/toast.js';

const authStore = useAuthStore();
const router = useRouter();
const code = ref('');
const loading = ref(false);

const verifyCode = async () => {
    loading.value = true;
    try {
        await authStore.verifyTfa(code.value);
        router.push(authStore.hasPermission('view_admin') ? '/admin' : '/user');
    } catch (error) {
        showErrorToast(error.message ?? 'Neplatný kód.');
    } finally {
        loading.value = false;
    }
};
</script>
