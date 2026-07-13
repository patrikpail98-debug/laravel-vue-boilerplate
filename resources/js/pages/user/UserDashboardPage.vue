<template>
    <div class="space-y-6">
        <div class="p-6 bg-base-100 rounded-box shadow-md">
            <h1 class="text-2xl font-bold text-primary mb-1">Vitajte, {{ authStore.user?.name }}</h1>
            <p class="text-base-content/60">Prehľad vašich rezervácií.</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="p-4 bg-base-100 rounded-box shadow-md">
                <p class="text-sm text-base-content/60">Nadchádzajúce rezervácie</p>
                <p class="text-3xl font-bold text-primary">{{ upcomingCount }}</p>
            </div>
            <div class="p-4 bg-base-100 rounded-box shadow-md">
                <p class="text-sm text-base-content/60">Rezervácie celkovo</p>
                <p class="text-3xl font-bold text-primary">{{ reservations.length }}</p>
            </div>
        </div>

        <div class="p-6 bg-base-100 rounded-box shadow-md">
            <h2 class="text-xl font-semibold mb-4">Najbližšia rezervácia</h2>

            <div v-if="loading" class="flex justify-center py-8">
                <span class="loading loading-spinner loading-lg"></span>
            </div>

            <div v-else-if="nextReservation">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="font-bold text-lg">{{ nextReservation.playground?.name }}</div>
                        <div class="text-sm text-base-content/60">{{ nextReservation.playground?.area?.name }}</div>
                        <div class="mt-2">{{ formatRange(nextReservation.start_time, nextReservation.end_time) }}</div>
                        <span class="badge mt-2" :class="statusBadgeClass(nextReservation.status)">{{ statusLabel(nextReservation.status) }}</span>
                    </div>
                    <div v-if="countdownLabel" class="text-right">
                        <p class="text-sm text-base-content/60">Ostáva</p>
                        <p class="text-xl font-bold text-primary">{{ countdownLabel }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-8">
                <p class="text-base-content/60 mb-4">Zatiaľ nemáte žiadnu nadchádzajúcu rezerváciu.</p>
                <router-link to="/rezervacia" class="btn btn-primary">Vytvoriť rezerváciu</router-link>
            </div>
        </div>

        <div class="text-right">
            <router-link to="/user/reservations" class="link link-primary">Zobraziť všetky rezervácie &rarr;</router-link>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref} from 'vue';
import 'notyf/notyf.min.css';
import {showErrorToast} from '../../constants/toast.js';
import http from '../../http.js';
import {formatReservationRange} from '../../utils/datetime.js';
import {useAuthStore} from '@/stores/auth';

const authStore = useAuthStore();
const reservations = ref([]);
const loading = ref(true);
const now = ref(new Date());
let timer = null;

const statuses = [
    {value: 'unverified', label: 'Čaká na overenie e-mailu'},
    {value: 'awaiting_payment', label: 'Čaká na platbu kartou'},
    {value: 'pending_approval', label: 'Čaká na platbu'},
    {value: 'approved', label: 'Schválené'},
    {value: 'rejected', label: 'Zamietnuté'},
    {value: 'cancelled', label: 'Zrušené'},
];

const statusLabel = (value) => statuses.find(s => s.value === value)?.label ?? value;

const statusBadgeClass = (status) => ({
    unverified: 'badge-ghost',
    awaiting_payment: 'badge-warning',
    pending_approval: 'badge-warning',
    approved: 'badge-success',
    rejected: 'badge-error',
    cancelled: 'badge-error badge-outline',
}[status] ?? 'badge-ghost');

const formatRange = formatReservationRange;

// Reservations that still hold a real, non-cancelled slot in the future.
const upcomingReservations = computed(() => {
    return reservations.value
        .filter(r => !['cancelled', 'rejected'].includes(r.status) && new Date(r.start_time) > now.value)
        .sort((a, b) => new Date(a.start_time) - new Date(b.start_time));
});

const upcomingCount = computed(() => upcomingReservations.value.length);
const nextReservation = computed(() => upcomingReservations.value[0] ?? null);

const pluralize = (n, one, few, many) => {
    if (n === 1) return one;
    if (n >= 2 && n <= 4) return few;
    return many;
};

const countdownLabel = computed(() => {
    if (!nextReservation.value) return null;

    const diffMs = new Date(nextReservation.value.start_time) - now.value;
    if (diffMs <= 0) return null;

    const totalMinutes = Math.floor(diffMs / 60000);
    const days = Math.floor(totalMinutes / (60 * 24));
    const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
    const minutes = totalMinutes % 60;

    const parts = [];
    if (days > 0) parts.push(`${days} ${pluralize(days, 'deň', 'dni', 'dní')}`);
    if (days > 0 || hours > 0) parts.push(`${hours} ${pluralize(hours, 'hodina', 'hodiny', 'hodín')}`);
    parts.push(`${minutes} ${pluralize(minutes, 'minúta', 'minúty', 'minút')}`);

    return parts.join(', ');
});

onMounted(async () => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 30000);

    try {
        const response = await http.request('/api/user/reservations');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Nepodarilo sa načítať rezervácie.');
        reservations.value = data;
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať rezervácie.');
    } finally {
        loading.value = false;
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>
