<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md">
        <h1 class="text-2xl font-bold text-primary mb-6">Moje rezervácie</h1>

        <div v-if="loading" class="flex justify-center py-16">
            <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                <tr>
                    <th class="bg-base-200">Ihrisko</th>
                    <th class="bg-base-200">Termín</th>
                    <th class="bg-base-200">Suma</th>
                    <th class="bg-base-200">VS</th>
                    <th class="bg-base-200">Stav</th>
                    <th class="bg-base-200 text-right">Akcie</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="reservation in reservations" :key="reservation.id">
                    <td>
                        <div class="font-bold">{{ reservation.playground?.name }}</div>
                        <div class="text-xs text-base-content/60">{{ reservation.playground?.area?.name }}</div>
                    </td>
                    <td>{{ formatRange(reservation.start_time, reservation.end_time) }}</td>
                    <td>{{ Number(reservation.total_price).toFixed(2) }} &euro;</td>
                    <td>{{ reservation.variable_symbol }}</td>
                    <td>
                        <span class="badge" :class="statusBadgeClass(reservation.status)">{{ statusLabel(reservation.status) }}</span>
                        <p v-if="reservation.admin_note" class="text-xs text-base-content/60 mt-1">{{ reservation.admin_note }}</p>
                    </td>
                    <td class="text-right">
                        <button v-if="reservation.status === 'approved'" type="button" class="btn btn-sm btn-ghost"
                                @click="downloadPaymentSummary(reservation)">
                            <DocumentArrowDownIcon class="w-4 h-4"/> Súhrn platby (PDF)
                        </button>
                    </td>
                </tr>
                <tr v-if="!reservations.length">
                    <td colspan="6" class="text-center text-base-content/50 py-8">Zatiaľ nemáte žiadne rezervácie.</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <router-link to="/rezervacia" class="btn btn-primary">Vytvoriť novú rezerváciu</router-link>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {DocumentArrowDownIcon} from '@heroicons/vue/24/outline';
import 'notyf/notyf.min.css';
import {showErrorToast} from '../../constants/toast.js';
import http from '../../http.js';
import {formatReservationRange} from '../../utils/datetime.js';

const reservations = ref([]);
const loading = ref(true);

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

const downloadPaymentSummary = async (reservation) => {
    try {
        const response = await http.request(`/api/user/reservations/${reservation.id}/payment-summary-pdf`);
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message ?? 'Nepodarilo sa vygenerovať súhrn platby.');
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `suhrn-platby-${reservation.variable_symbol}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa vygenerovať súhrn platby.');
    }
};

onMounted(async () => {
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
</script>
