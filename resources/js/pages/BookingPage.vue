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
                        :playground-id="playgroundSlug"
                        :max-horizon-days="rules.maxHorizonDays"
                        :max-duration-minutes="rules.maxDurationMinutes"
                        :price-per30-min="rules.pricePer30Min"
                        @loaded="onSlotPickerLoaded"
                        @update:selection="selection = $event"
                    />

                    <div class="divider"></div>

                    <form @submit.prevent="startCheckout" class="space-y-4">
                        <p class="text-xs text-base-content/60">
                            Polia označené <span class="text-error" aria-hidden="true">*</span> sú povinné.
                        </p>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                Meno a priezvisko / názov firmy <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="text" class="input input-bordered w-full" v-model="form.customer_name" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                E-mail <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="email" class="input input-bordered w-full" v-model="form.customer_email" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                Telefón <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="tel" class="input input-bordered w-full" v-model="form.customer_phone" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                Ulica <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="text" class="input input-bordered w-full" v-model="form.street" required/>
                        </fieldset>
                        <div class="grid grid-cols-2 gap-4">
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">
                                    Mesto <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                                </legend>
                                <input type="text" class="input input-bordered w-full" v-model="form.city" required/>
                            </fieldset>
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">
                                    PSČ <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                                </legend>
                                <input type="text" class="input input-bordered w-full" v-model="form.postcode" required/>
                            </fieldset>
                        </div>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">IČO</legend>
                            <input type="text" class="input input-bordered w-full" v-model="form.ico"/>
                        </fieldset>

                        <button class="btn btn-primary btn-block" :disabled="!selection || loading">
                            <span v-if="loading" class="loading loading-spinner"></span>
                            Odoslať a rezervovať
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment method choice -->
        <dialog :class="{'modal-open': showPaymentModal}" class="modal">
            <div class="modal-box">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="showPaymentModal = false">✕</button>
                <h3 class="font-bold text-lg mb-1">Spôsob platby</h3>
                <p class="text-sm text-base-content/70 mb-4">Vyberte, akým spôsobom chcete rezerváciu zaplatiť.</p>

                <div class="grid gap-3">
                    <button type="button" class="btn btn-outline btn-primary justify-start h-auto py-3 normal-case"
                            :disabled="loading" @click="submit('card')">
                        <div class="text-left">
                            <div class="font-semibold">Platba kartou</div>
                            <div class="text-xs font-normal opacity-70">Okamžitá platba online, rezervácia je potvrdená hneď po zaplatení.</div>
                        </div>
                    </button>
                    <button type="button" class="btn btn-outline justify-start h-auto py-3 normal-case"
                            :disabled="loading" @click="submit('bank_transfer')">
                        <div class="text-left">
                            <div class="font-semibold">Platba prevodom</div>
                            <div class="text-xs font-normal opacity-70">Platobné údaje Vám pošleme e-mailom po potvrdení e-mailovej adresy.</div>
                        </div>
                    </button>
                </div>
            </div>
        </dialog>
    </div>
</template>

<script setup>
import {onMounted, reactive, ref} from 'vue';
import {useRoute} from 'vue-router';
import http from '@/http.js';
import SlotPicker from '@/components/ui/SlotPicker.vue';
import {showErrorToast} from '../constants/toast.js';
import {useAuthStore} from '@/stores/auth.js';
import 'notyf/notyf.min.css';

const route = useRoute();
const authStore = useAuthStore();
const playgroundSlug = route.params.slug;

const playgroundInfo = reactive({name: '', area_name: '', allowCardPayment: false});
const rules = reactive({maxHorizonDays: 60, maxDurationMinutes: 120, pricePer30Min: 0});
const selection = ref(null);
// The real UUID primary key, captured from SlotPicker's availability response
// (playground.id) once it loads - POST /api/reservations still needs the
// actual id, not the slug the page was routed in on.
const playgroundRealId = ref(null);
const loading = ref(false);
const submitted = ref('');
const showPaymentModal = ref(false);

const form = reactive({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    street: '',
    city: '',
    postcode: '',
    ico: '',
});

const onSlotPickerLoaded = (data) => {
    playgroundInfo.name = data.playground?.name ?? '';
    playgroundInfo.area_name = data.playground?.area_name ?? '';
    playgroundInfo.allowCardPayment = data.playground?.allow_card_payment ?? false;
    playgroundRealId.value = data.playground?.id ?? null;
    rules.maxHorizonDays = data.max_horizon_days ?? rules.maxHorizonDays;
    rules.maxDurationMinutes = data.max_duration_minutes ?? rules.maxDurationMinutes;
    rules.pricePer30Min = data.price_per_30min ?? rules.pricePer30Min;
};

// Facilities without card payment enabled skip the choice entirely and keep
// today's bank-transfer-only flow; only card-enabled facilities show the modal.
const startCheckout = () => {
    if (!selection.value) return;

    if (playgroundInfo.allowCardPayment) {
        showPaymentModal.value = true;
    } else {
        submit('bank_transfer');
    }
};

const submit = async (paymentMethod) => {
    if (!selection.value) return;

    loading.value = true;
    try {
        const response = await http.request('/api/reservations', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                playground_id: playgroundRealId.value,
                customer_name: form.customer_name,
                customer_email: form.customer_email,
                customer_phone: form.customer_phone,
                street: form.street,
                city: form.city,
                postcode: form.postcode,
                ico: form.ico,
                start_time: selection.value.start_time,
                end_time: selection.value.end_time,
                payment_method: paymentMethod,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message ?? 'Rezerváciu sa nepodarilo vytvoriť.');
        }

        if (paymentMethod === 'card' && data.payment_url) {
            // Full redirect to the Nexi hosted page - leaving the SPA on purpose.
            window.location.href = data.payment_url;
            return;
        }

        showPaymentModal.value = false;
        submitted.value = data.message;
    } catch (error) {
        showErrorToast(error.message ?? 'Rezerváciu sa nepodarilo vytvoriť.');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (authStore.isAuthenticated && authStore.user) {
        form.customer_name = authStore.user.name ?? '';
        form.customer_email = authStore.user.email ?? '';
        form.customer_phone = authStore.user.phone ?? '';
        form.street = authStore.user.street ?? '';
        form.city = authStore.user.city ?? '';
        form.postcode = authStore.user.postcode ?? '';
        form.ico = authStore.user.ico ?? '';
    }
});
</script>
