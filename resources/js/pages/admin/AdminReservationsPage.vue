<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Rezervácie</h1>
            <select v-model="statusFilter" class="select select-bordered" @change="fetchReservations">
                <option value="">Všetky stavy</option>
                <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                <tr>
                    <th class="bg-base-200">Ihrisko</th>
                    <th class="bg-base-200">Klient</th>
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
                    <td>
                        <div>{{ reservation.customer_name }}</div>
                        <div class="text-xs text-base-content/60">{{ reservation.customer_email }}</div>
                    </td>
                    <td>{{ formatRange(reservation.start_time, reservation.end_time) }}</td>
                    <td>{{ Number(reservation.total_price).toFixed(2) }} &euro;</td>
                    <td>{{ reservation.variable_symbol }}</td>
                    <td><span class="badge" :class="statusBadgeClass(reservation.status)">{{ statusLabel(reservation.status) }}</span></td>
                    <td class="text-right">
                        <div class="dropdown dropdown-left">
                            <div tabindex="0" role="button" class="btn btn-sm btn-ghost">
                                <EllipsisHorizontalIcon class="w-5 h-5"/>
                            </div>
                            <ul tabindex="0" class="dropdown-content z-1 menu p-2 shadow bg-base-100 rounded-box w-56">
                                <li v-if="reservation.status === 'pending_approval'">
                                    <a @click="approve(reservation)"><CheckIcon class="w-4 h-4"/> Schváliť</a>
                                </li>
                                <li v-if="reservation.status === 'pending_approval'">
                                    <a @click="resendPayment(reservation)"><EnvelopeIcon class="w-4 h-4"/> Znova poslať platobný e-mail</a>
                                </li>
                                <li v-if="['pending_approval', 'approved'].includes(reservation.status)">
                                    <a @click="openNoteModal(reservation, 'reject')" class="text-error"><XMarkIcon class="w-4 h-4"/> Zamietnuť</a>
                                </li>
                                <li v-if="!['cancelled', 'rejected'].includes(reservation.status)">
                                    <a @click="openNoteModal(reservation, 'cancel')" class="text-error"><TrashIcon class="w-4 h-4"/> Zrušiť</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <tr v-if="!reservations.length">
                    <td colspan="7" class="text-center text-base-content/50 py-8">Žiadne rezervácie.</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Reject/Cancel note modal -->
        <dialog :class="{'modal-open': showNoteModal}" class="modal">
            <div class="modal-box">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="showNoteModal = false">✕</button>
                <h3 class="font-bold text-lg mb-4">
                    {{ noteAction === 'reject' ? 'Zamietnuť rezerváciu' : 'Zrušiť rezerváciu' }}
                </h3>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Poznámka (voliteľné, klient ju uvidí)</legend>
                    <textarea class="textarea textarea-bordered w-full" v-model="adminNote"></textarea>
                </fieldset>
                <div class="modal-action">
                    <button class="btn btn-ghost" @click="showNoteModal = false">Zrušiť</button>
                    <button class="btn btn-error" :disabled="isProcessing" @click="submitNoteAction">
                        <span v-if="isProcessing" class="loading loading-spinner"></span>
                        Potvrdiť
                    </button>
                </div>
            </div>
        </dialog>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {CheckIcon, EllipsisHorizontalIcon, EnvelopeIcon, TrashIcon, XMarkIcon} from '@heroicons/vue/24/outline';
import 'notyf/notyf.min.css';
import {showErrorToast, showSuccessToast} from '../../constants/toast.js';
import http from '../../http.js';
import {formatReservationRange} from '../../utils/datetime.js';

const reservations = ref([]);
const statusFilter = ref('');

const statuses = [
    {value: 'unverified', label: 'Čaká na overenie e-mailu'},
    {value: 'pending_approval', label: 'Čaká na platbu'},
    {value: 'approved', label: 'Schválené'},
    {value: 'rejected', label: 'Zamietnuté'},
    {value: 'cancelled', label: 'Zrušené'},
];

const statusLabel = (value) => statuses.find(s => s.value === value)?.label ?? value;

const statusBadgeClass = (status) => ({
    unverified: 'badge-ghost',
    pending_approval: 'badge-warning',
    approved: 'badge-success',
    rejected: 'badge-error',
    cancelled: 'badge-error badge-outline',
}[status] ?? 'badge-ghost');

const formatRange = formatReservationRange;

const fetchReservations = async () => {
    try {
        const query = statusFilter.value ? `?status=${statusFilter.value}` : '';
        const response = await http.request(`/api/admin/reservations${query}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Nepodarilo sa načítať rezervácie.');
        reservations.value = data;
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať rezervácie.');
    }
};

const approve = async (reservation) => {
    try {
        const response = await http.request(`/api/admin/reservations/${reservation.id}/approve`, {method: 'PUT'});
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Schválenie zlyhalo.');
        showSuccessToast('Rezervácia bola schválená.');
        await fetchReservations();
    } catch (error) {
        showErrorToast(error.message ?? 'Schválenie zlyhalo.');
    }
};

const resendPayment = async (reservation) => {
    try {
        const response = await http.request(`/api/admin/reservations/${reservation.id}/resend-payment-email`, {method: 'POST'});
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Odoslanie zlyhalo.');
        showSuccessToast('Platobný e-mail bol znova odoslaný.');
    } catch (error) {
        showErrorToast(error.message ?? 'Odoslanie zlyhalo.');
    }
};

const showNoteModal = ref(false);
const noteAction = ref('reject');
const noteTarget = ref(null);
const adminNote = ref('');
const isProcessing = ref(false);

const openNoteModal = (reservation, action) => {
    noteTarget.value = reservation;
    noteAction.value = action;
    adminNote.value = '';
    showNoteModal.value = true;
};

const submitNoteAction = async () => {
    isProcessing.value = true;
    try {
        const endpoint = noteAction.value === 'reject' ? 'reject' : 'cancel';
        const response = await http.request(`/api/admin/reservations/${noteTarget.value.id}/${endpoint}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({admin_note: adminNote.value}),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Akcia zlyhala.');
        showSuccessToast('Hotovo.');
        showNoteModal.value = false;
        await fetchReservations();
    } catch (error) {
        showErrorToast(error.message ?? 'Akcia zlyhala.');
    } finally {
        isProcessing.value = false;
    }
};

onMounted(fetchReservations);
</script>
