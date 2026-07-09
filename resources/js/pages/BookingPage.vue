<template>
    <div class="min-h-screen bg-base-200 py-10 px-4">
        <div class="max-w-2xl mx-auto">
            <router-link to="/rezervacia" class="link link-primary text-sm mb-4 inline-block">&larr; Späť na zoznam ihrísk</router-link>

            <div v-if="submitted" class="card bg-base-100 shadow-xl">
                <div class="card-body items-center text-center">
                    <h2 class="card-title text-2xl text-primary mb-2">Ďakujeme!</h2>
                    <p>{{ submitted }}</p>
                </div>
            </div>

            <div v-else class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h1 class="card-title text-2xl text-primary">{{ playgroundInfo.name || 'Rezervácia' }}</h1>
                    <p v-if="playgroundInfo.area_name" class="text-sm text-base-content/70 mb-4">{{ playgroundInfo.area_name }}</p>

                    <SlotPicker
                        :playground-id="playgroundId"
                        :max-horizon-days="rules.maxHorizonDays"
                        :max-duration-minutes="rules.maxDurationMinutes"
                        :price-per30-min="rules.pricePer30Min"
                        @loaded="onSlotPickerLoaded"
                        @update:selection="selection = $event"
                    />

                    <div class="divider"></div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Meno a priezvisko</legend>
                            <input type="text" class="input input-bordered w-full" v-model="form.customer_name" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">E-mail</legend>
                            <input type="email" class="input input-bordered w-full" v-model="form.customer_email" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Telefón</legend>
                            <input type="tel" class="input input-bordered w-full" v-model="form.customer_phone" required/>
                        </fieldset>

                        <button class="btn btn-primary btn-block" :disabled="!selection || loading">
                            <span v-if="loading" class="loading loading-spinner"></span>
                            Odoslať rezerváciu
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {reactive, ref} from 'vue';
import {useRoute} from 'vue-router';
import http from '@/http.js';
import SlotPicker from '@/components/ui/SlotPicker.vue';
import {showErrorToast} from '../constants/toast.js';
import 'notyf/notyf.min.css';

const route = useRoute();
const playgroundId = route.params.playgroundId;

const playgroundInfo = reactive({name: '', area_name: ''});
const rules = reactive({maxHorizonDays: 60, maxDurationMinutes: 120, pricePer30Min: 0});
const selection = ref(null);
const loading = ref(false);
const submitted = ref('');

const form = reactive({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
});

const onSlotPickerLoaded = (data) => {
    playgroundInfo.name = data.playground?.name ?? '';
    playgroundInfo.area_name = data.playground?.area_name ?? '';
    rules.maxHorizonDays = data.max_horizon_days ?? rules.maxHorizonDays;
    rules.maxDurationMinutes = data.max_duration_minutes ?? rules.maxDurationMinutes;
    rules.pricePer30Min = data.price_per_30min ?? rules.pricePer30Min;
};

const submit = async () => {
    if (!selection.value) return;

    loading.value = true;
    try {
        const response = await http.request('/api/reservations', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                playground_id: playgroundId,
                customer_name: form.customer_name,
                customer_email: form.customer_email,
                customer_phone: form.customer_phone,
                start_time: selection.value.start_time,
                end_time: selection.value.end_time,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message ?? 'Rezerváciu sa nepodarilo vytvoriť.');
        }

        submitted.value = data.message;
    } catch (error) {
        showErrorToast(error.message ?? 'Rezerváciu sa nepodarilo vytvoriť.');
    } finally {
        loading.value = false;
    }
};
</script>
