<template>
    <div class="min-h-screen flex items-center justify-center bg-base-200">
        <div class="card w-full max-w-md bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6">Obnovenie hesla</h2>

                <div v-if="status" class="alert alert-success mb-4">
                    <span>{{ status }}</span>
                </div>

                <form @submit.prevent="handleResetPassword">
                    <!-- Token and Email are hidden, populated from route params -->
                    <input type="hidden" v-model="form.token">

                    <div class="form-control mb-4">
                        <label for="email" class="label"><span class="label-text">E-mail</span></label>
                        <input type="email" id="email" v-model="form.email" class="input input-bordered w-full" :class="{'input-error': errors.email}" required readonly>
                        <label class="label" v-if="errors.email">
                            <span class="label-text-alt text-error">{{ errors.email[0] }}</span>
                        </label>
                    </div>

                    <div class="form-control mb-4">
                        <label for="password" class="label"><span class="label-text">Nové heslo</span></label>
                        <input type="password" id="password" v-model="form.password" class="input input-bordered w-full" :class="{'input-error': errors.password}" required>
                        <label class="label" v-if="errors.password">
                            <span class="label-text-alt text-error">{{ errors.password[0] }}</span>
                        </label>
                        <label class="label" v-else>
                            <span class="label-text-alt text-base-content/60">Aspoň 8 znakov, veľké aj malé písmeno, číslica a symbol.</span>
                        </label>
                    </div>

                    <div class="form-control mb-4">
                        <label for="password_confirmation" class="label"><span class="label-text">Potvrďte nové heslo</span></label>
                        <input type="password" id="password_confirmation" v-model="form.password_confirmation" class="input input-bordered w-full" required>
                    </div>

                    <div class="form-control">
                        <button class="btn btn-block btn-primary" :disabled="loading">
                            <span v-if="loading" class="loading loading-spinner"></span>
                            Obnoviť heslo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http from '@/http';
import { showErrorToast, showSuccessToast } from '@/constants/toast';

const route = useRoute();
const router = useRouter();

const form = ref({
    token: '',
    email: '',
    password: '',
    password_confirmation: ''
});
const loading = ref(false);
const errors = ref({});
const status = ref('');

onMounted(() => {
    form.value.token = route.params.token;
    form.value.email = route.query.email;
});

const handleResetPassword = async () => {
    errors.value = {};
    status.value = '';

    // Mirror the server policy (Password::min(8)->mixedCase()->numbers()->symbols())
    // so a weak password is caught before the round-trip.
    const pw = form.value.password;
    const pwValid = pw.length >= 8
        && /[a-z]/.test(pw)
        && /[A-Z]/.test(pw)
        && /[0-9]/.test(pw)
        && /[^a-zA-Z0-9]/.test(pw);

    if (!pwValid) {
        errors.value = { password: ['Heslo musí mať aspoň 8 znakov a obsahovať veľké aj malé písmeno, číslicu a symbol.'] };
        return;
    }

    if (form.value.password !== form.value.password_confirmation) {
        errors.value = { password: ['Heslá sa nezhodujú.'] };
        return;
    }

    loading.value = true;

    try {
        const response = await http.request('/api/auth/reset-password', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(form.value),
        });
        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422) {
                errors.value = data.errors || { email: [data.email] };
            } else {
                showErrorToast(data.message || 'Heslo sa nepodarilo obnoviť.');
            }
        } else {
            status.value = data.message;
            showSuccessToast(data.message);
            setTimeout(() => router.push('/login'), 2000);
        }
    } catch (err) {
        showErrorToast('Neočakávaná chyba.');
    } finally {
        loading.value = false;
    }
};
</script>
