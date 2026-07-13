<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md max-w-4xl mx-auto">
        <div class="flex items-center mb-8">
            <IdentificationIcon class="w-8 h-8 mr-3 text-primary"/>
            <h1 class="text-2xl font-bold text-primary">Moje údaje</h1>
        </div>

        <div class="p-6 bg-base-200 rounded-box mb-6">
            <h2 class="text-xl font-semibold mb-6">Kontaktné a fakturačné údaje</h2>

            <form @submit.prevent="saveDetails" class="space-y-4">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Meno a priezvisko / názov firmy</legend>
                    <input type="text" class="input input-bordered w-full" v-model="form.name" required/>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">E-mail</legend>
                    <input type="email" class="input input-bordered w-full" v-model="form.email" required/>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Telefón</legend>
                    <input type="tel" class="input input-bordered w-full" v-model="form.phone"/>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Ulica</legend>
                    <input type="text" class="input input-bordered w-full" v-model="form.street"/>
                </fieldset>
                <div class="grid grid-cols-2 gap-4">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Mesto</legend>
                        <input type="text" class="input input-bordered w-full" v-model="form.city"/>
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">PSČ</legend>
                        <input type="text" class="input input-bordered w-full" v-model="form.postcode"/>
                    </fieldset>
                </div>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">IČO</legend>
                    <input type="text" class="input input-bordered w-full" v-model="form.ico"/>
                </fieldset>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary" :disabled="isSaving">
                        <span v-if="isSaving" class="loading loading-spinner"></span>
                        Uložiť
                    </button>
                </div>
            </form>
        </div>

        <div class="p-6 bg-error/10 border border-error/30 rounded-box">
            <h2 class="text-xl font-semibold mb-2 text-error">Nebezpečná zóna</h2>
            <p class="text-sm text-base-content/70 mb-4">
                Vymazanie účtu je nevratné. Váš účet bude anonymizovaný a znefunkčnený, no existujúce rezervácie
                zostanú zachované.
            </p>
            <button type="button" class="btn btn-error btn-outline" @click="showDeleteModal = true">Vymazať účet</button>
        </div>
    </div>

    <dialog :class="{'modal-open': showDeleteModal}" class="modal">
        <div class="modal-box">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="closeDeleteModal">✕</button>
            <h3 class="font-bold text-lg mb-1 text-error">Vymazať účet</h3>
            <p class="text-sm text-base-content/70 mb-4">
                Táto akcia je nevratná. Pre potvrdenie zadajte svoje aktuálne heslo.
            </p>
            <fieldset class="fieldset">
                <legend class="fieldset-legend">Aktuálne heslo</legend>
                <input type="password" class="input input-bordered w-full" v-model="deletePassword"/>
            </fieldset>
            <div class="modal-action">
                <button class="btn btn-ghost" @click="closeDeleteModal">Zrušiť</button>
                <button class="btn btn-error" :disabled="!deletePassword || isDeleting" @click="deleteAccount">
                    <span v-if="isDeleting" class="loading loading-spinner"></span>
                    Vymazať účet natrvalo
                </button>
            </div>
        </div>
    </dialog>
</template>

<script setup>
import {reactive, ref} from 'vue';
import {useRouter} from 'vue-router';
import {IdentificationIcon} from '@heroicons/vue/24/outline';
import 'notyf/notyf.min.css';
import {showErrorToast, showSuccessToast} from '../../constants/toast.js';
import http from '../../http.js';
import {useAuthStore} from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const form = reactive({
    name: authStore.user?.name ?? '',
    email: authStore.user?.email ?? '',
    phone: authStore.user?.phone ?? '',
    street: authStore.user?.street ?? '',
    city: authStore.user?.city ?? '',
    postcode: authStore.user?.postcode ?? '',
    ico: authStore.user?.ico ?? '',
});

const isSaving = ref(false);

const saveDetails = async () => {
    isSaving.value = true;
    const emailChanged = form.email !== authStore.user?.email;

    try {
        const response = await http.request('/api/user/contact-details', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(form),
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                showErrorToast(Object.values(data.errors).flat().join(' '));
            } else {
                throw new Error(data.message ?? 'Nepodarilo sa uložiť údaje.');
            }
            return;
        }

        await authStore.fetchUser();

        if (emailChanged) {
            showSuccessToast('Údaje boli uložené. Potvrďte prosím novú e-mailovú adresu.');
            router.push({name: 'verification.notice', params: {email: form.email}});
            return;
        }

        showSuccessToast('Údaje boli uložené.');
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa uložiť údaje.');
    } finally {
        isSaving.value = false;
    }
};

const showDeleteModal = ref(false);
const deletePassword = ref('');
const isDeleting = ref(false);

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletePassword.value = '';
};

const deleteAccount = async () => {
    isDeleting.value = true;
    try {
        const response = await http.request('/api/user/account', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({current_password: deletePassword.value}),
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                throw new Error(Object.values(data.errors).flat().join(' '));
            }
            throw new Error(data.message ?? 'Nepodarilo sa vymazať účet.');
        }

        await authStore.logout();
        router.push('/');
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa vymazať účet.');
    } finally {
        isDeleting.value = false;
    }
};
</script>
