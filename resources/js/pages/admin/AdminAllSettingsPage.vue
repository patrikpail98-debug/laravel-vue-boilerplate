<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md">
        <div class="flex items-center mb-2">
            <Cog6ToothIcon class="w-8 h-8 mr-3 text-primary"/>
            <h1 class="text-2xl font-bold text-primary">Všetky nastavenia</h1>
        </div>
        <p class="text-sm text-base-content/60 mb-6">
            Technický pohľad na všetky nastavenia priamo z databázy. Bežné nastavenia (kontakt, platobné údaje a pod.)
            odporúčame upravovať cez stránku <router-link to="/admin/settings" class="link link-primary">Nastavenia</router-link>.
        </p>

        <div v-if="loading" class="flex justify-center py-16">
            <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                <tr>
                    <th class="bg-base-200">Kľúč</th>
                    <th class="bg-base-200">Hodnota</th>
                    <th class="bg-base-200 text-right">Akcie</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="setting in settings" :key="setting.id">
                    <td class="font-mono text-sm">{{ setting.key }}</td>
                    <td>
                        <input type="text" class="input input-bordered input-sm w-full" v-model="setting.value"/>
                    </td>
                    <td class="text-right">
                        <button class="btn btn-sm btn-primary" :disabled="savingId === setting.id" @click="saveSetting(setting)">
                            <span v-if="savingId === setting.id" class="loading loading-spinner loading-xs"></span>
                            Uložiť
                        </button>
                    </td>
                </tr>
                <tr v-if="!settings.length">
                    <td colspan="3" class="text-center text-base-content/50 py-8">Žiadne nastavenia.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {Cog6ToothIcon} from '@heroicons/vue/24/outline';
import http from '../../http.js';
import 'notyf/notyf.min.css';
import {showErrorToast, showSuccessToast} from '../../constants/toast.js';

const settings = ref([]);
const loading = ref(true);
const savingId = ref(null);

const fetchSettings = async () => {
    loading.value = true;
    try {
        const response = await http.request('/api/admin/settings/all');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Nepodarilo sa načítať nastavenia.');
        settings.value = data;
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať nastavenia.');
    } finally {
        loading.value = false;
    }
};

const saveSetting = async (setting) => {
    savingId.value = setting.id;
    try {
        const response = await http.request(`/api/admin/settings/all/${setting.id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({value: setting.value}),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Uloženie zlyhalo.');
        showSuccessToast(`Nastavenie "${setting.key}" bolo uložené.`);
    } catch (error) {
        showErrorToast(error.message ?? 'Uloženie zlyhalo.');
    } finally {
        savingId.value = null;
    }
};

onMounted(fetchSettings);
</script>
