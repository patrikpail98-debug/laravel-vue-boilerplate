<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <h1 class="text-2xl font-bold text-primary">Rezervácie</h1>
            <div class="flex flex-wrap gap-2">
                <input type="text" class="input input-bordered" v-model="searchQuery"
                       placeholder="Hľadať meno, e-mail, VS..."/>
                <select v-model="statusFilter" class="select select-bordered">
                    <option value="">Všetky stavy</option>
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
                <button type="button" class="btn btn-secondary" @click="openCreateModal">
                    <PlusCircleIcon class="w-5 h-5"/>
                    Vytvoriť rezerváciu
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3 mb-6 p-4 bg-base-200 rounded-box">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">Report platieb od</legend>
                <input type="date" class="input input-bordered" v-model="reportFrom"/>
            </fieldset>
            <fieldset class="fieldset">
                <legend class="fieldset-legend">do</legend>
                <input type="date" class="input input-bordered" v-model="reportTo"/>
            </fieldset>
            <button type="button" class="btn btn-primary" :disabled="!reportFrom || !reportTo || exporting" @click="exportCsv">
                <span v-if="exporting" class="loading loading-spinner"></span>
                <DocumentArrowDownIcon v-else class="w-4 h-4"/>
                Exportovať CSV
            </button>
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
                    <th class="bg-base-200">Platba</th>
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
                    <td>{{ paymentMethodLabel(reservation.payment_method) }}</td>
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
                                <li v-if="reservation.status === 'approved'">
                                    <a @click="downloadPaymentSummary(reservation)"><DocumentArrowDownIcon class="w-4 h-4"/> Stiahnuť súhrn platby (PDF)</a>
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
                    <td colspan="8" class="text-center text-base-content/50 py-8">Žiadne rezervácie.</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-center mt-6">
            <div class="join">
                <button class="join-item btn" :class="{ 'btn-disabled': currentPage === 1 }" @click="currentPage--">«</button>
                <button class="join-item btn">Stránka {{ currentPage }} / {{ lastPage }}</button>
                <button class="join-item btn" :class="{ 'btn-disabled': currentPage >= lastPage }" @click="currentPage++">»</button>
            </div>
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

        <!-- Manual (off-system contract) reservation modal -->
        <dialog :class="{'modal-open': showCreateModal}" class="modal">
            <div class="modal-box max-w-2xl">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="showCreateModal = false">✕</button>
                <h3 class="font-bold text-lg mb-1">Vytvoriť rezerváciu</h3>
                <p class="text-sm text-base-content/70 mb-4">
                    Pre termíny dohodnuté mimo systému (napr. podpísanou zmluvou) - termín sa hneď nastaví ako
                    schválený a zablokuje sa v kalendári. Bežné limity pre verejné rezervácie (max. dĺžka, horizont,
                    otváracie hodiny) sa tu neuplatňujú, kontroluje sa len že sa termín neprekrýva s inou rezerváciou.
                </p>

                <form @submit.prevent="submitManualReservation" class="space-y-4">
                    <p class="text-xs text-base-content/60">
                        Polia označené <span class="text-error" aria-hidden="true">*</span> sú povinné.
                    </p>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">
                            Ihrisko <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                        </legend>
                        <select class="select select-bordered w-full" v-model="manualForm.playground_id" required>
                            <option :value="null" disabled>Vyberte ihrisko</option>
                            <option v-for="playground in playgrounds" :key="playground.id" :value="playground.id">
                                {{ playground.name }} ({{ playground.area?.name }})
                            </option>
                        </select>
                    </fieldset>

                    <div class="grid grid-cols-3 gap-4">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                Dátum <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="date" class="input input-bordered w-full" v-model="manualForm.date" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                Začiatok <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="time" step="1800" class="input input-bordered w-full" v-model="manualForm.startTime" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                Trvanie (min) <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="number" min="30" step="30" class="input input-bordered w-full" v-model.number="manualForm.durationMinutes" required/>
                        </fieldset>
                    </div>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">
                            Meno a priezvisko / názov firmy <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                        </legend>
                        <input type="text" class="input input-bordered w-full" v-model="manualForm.customer_name" required/>
                    </fieldset>
                    <div class="grid grid-cols-2 gap-4">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                E-mail <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="email" class="input input-bordered w-full" v-model="manualForm.customer_email" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                Telefón <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="tel" class="input input-bordered w-full" v-model="manualForm.customer_phone" required/>
                        </fieldset>
                    </div>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">
                            Ulica <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                        </legend>
                        <input type="text" class="input input-bordered w-full" v-model="manualForm.street" required/>
                    </fieldset>
                    <div class="grid grid-cols-3 gap-4">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                Mesto <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="text" class="input input-bordered w-full" v-model="manualForm.city" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">
                                PSČ <span class="text-error" aria-hidden="true">*</span><span class="sr-only"> (povinné)</span>
                            </legend>
                            <input type="text" class="input input-bordered w-full" v-model="manualForm.postcode" required/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">IČO</legend>
                            <input type="text" class="input input-bordered w-full" v-model="manualForm.ico"/>
                        </fieldset>
                    </div>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Poznámka (interná)</legend>
                        <textarea class="textarea textarea-bordered w-full" v-model="manualForm.admin_note"
                                  placeholder="Napr. odkaz na zmluvu, číslo objednávky..."></textarea>
                    </fieldset>

                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" @click="showCreateModal = false">Zrušiť</button>
                        <button type="submit" class="btn btn-primary" :disabled="isCreating">
                            <span v-if="isCreating" class="loading loading-spinner"></span>
                            Vytvoriť a schváliť
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
    </div>
</template>

<script setup>
import {onMounted, ref, watch} from 'vue';
import {CheckIcon, DocumentArrowDownIcon, EllipsisHorizontalIcon, EnvelopeIcon, PlusCircleIcon, TrashIcon, XMarkIcon} from '@heroicons/vue/24/outline';
import 'notyf/notyf.min.css';
import {showErrorToast, showSuccessToast} from '../../constants/toast.js';
import http from '../../http.js';
import {formatReservationRange} from '../../utils/datetime.js';

const reservations = ref([]);
const statusFilter = ref('');
const searchQuery = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const reportFrom = ref('');
const reportTo = ref('');
const exporting = ref(false);

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

const paymentMethodLabel = (method) => ({
    card: 'Karta',
    bank_transfer: 'Prevod',
}[method] ?? '—');

const formatRange = formatReservationRange;

const fetchReservations = async () => {
    try {
        const params = new URLSearchParams({page: currentPage.value});
        if (statusFilter.value) params.set('status', statusFilter.value);
        if (searchQuery.value) params.set('search', searchQuery.value);

        const response = await http.request(`/api/admin/reservations?${params.toString()}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Nepodarilo sa načítať rezervácie.');
        reservations.value = data.data;
        lastPage.value = data.last_page;
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať rezervácie.');
    }
};

let searchDebounce = null;
watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        currentPage.value = 1;
        fetchReservations();
    }, 300);
});

watch(statusFilter, () => {
    currentPage.value = 1;
    fetchReservations();
});

watch(currentPage, fetchReservations);

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

const downloadPaymentSummary = async (reservation) => {
    try {
        const response = await http.request(`/api/admin/reservations/${reservation.id}/payment-summary-pdf`);
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

const exportCsv = async () => {
    exporting.value = true;
    try {
        const params = new URLSearchParams({from: reportFrom.value, to: reportTo.value});
        if (statusFilter.value) params.set('status', statusFilter.value);

        const response = await http.request(`/api/admin/reservations/export?${params.toString()}`);
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message ?? 'Nepodarilo sa vygenerovať report.');
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `report-platieb-${reportFrom.value}_${reportTo.value}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa vygenerovať report.');
    } finally {
        exporting.value = false;
    }
};

const playgrounds = ref([]);

const fetchPlaygrounds = async () => {
    try {
        const response = await http.request('/api/admin/facilities/playgrounds');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Nepodarilo sa načítať ihriská.');
        playgrounds.value = data;
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať ihriská.');
    }
};

const showCreateModal = ref(false);
const isCreating = ref(false);
const emptyManualForm = () => ({
    playground_id: null,
    date: '',
    startTime: '',
    durationMinutes: 60,
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    street: '',
    city: '',
    postcode: '',
    ico: '',
    admin_note: '',
});
const manualForm = ref(emptyManualForm());

const openCreateModal = () => {
    manualForm.value = emptyManualForm();
    showCreateModal.value = true;
};

const submitManualReservation = async () => {
    isCreating.value = true;
    try {
        const start = new Date(`${manualForm.value.date}T${manualForm.value.startTime}:00`);
        const end = new Date(start.getTime() + manualForm.value.durationMinutes * 60000);
        const pad = (n) => String(n).padStart(2, '0');
        const toLocal = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;

        const response = await http.request('/api/admin/reservations/manual', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                playground_id: manualForm.value.playground_id,
                customer_name: manualForm.value.customer_name,
                customer_email: manualForm.value.customer_email,
                customer_phone: manualForm.value.customer_phone,
                street: manualForm.value.street,
                city: manualForm.value.city,
                postcode: manualForm.value.postcode,
                ico: manualForm.value.ico,
                admin_note: manualForm.value.admin_note,
                start_time: toLocal(start),
                end_time: toLocal(end),
            }),
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Vytvorenie rezervácie zlyhalo.');

        showSuccessToast('Rezervácia bola vytvorená a schválená.');
        showCreateModal.value = false;
        currentPage.value = 1;
        await fetchReservations();
    } catch (error) {
        showErrorToast(error.message ?? 'Vytvorenie rezervácie zlyhalo.');
    } finally {
        isCreating.value = false;
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

onMounted(() => {
    fetchReservations();
    fetchPlaygrounds();
});
</script>
