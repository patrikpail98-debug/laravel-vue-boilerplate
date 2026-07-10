<!-- resources/js/pages/admin/DashboardPage.vue -->
<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Prehľad</h1>

        <div v-if="loading" class="flex justify-center py-16">
            <span class="loading loading-spinner loading-lg"></span>
        </div>

        <template v-else>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title text-primary text-base">Rezervácie celkovo</h2>
                        <p class="text-4xl font-bold">{{ stats.reservations?.total ?? 0 }}</p>
                        <p class="text-sm text-base-content/60">{{ stats.reservations?.this_month ?? 0 }} tento mesiac</p>
                    </div>
                </div>
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title text-primary text-base">Nadchádzajúce (schválené)</h2>
                        <p class="text-4xl font-bold">{{ stats.reservations?.upcoming ?? 0 }}</p>
                    </div>
                </div>
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title text-primary text-base">Tržby celkovo</h2>
                        <p class="text-4xl font-bold">{{ formatEur(stats.revenue?.total_eur) }}</p>
                        <p class="text-sm text-base-content/60">{{ formatEur(stats.revenue?.this_month_eur) }} tento mesiac</p>
                    </div>
                </div>
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title text-primary text-base">Používatelia</h2>
                        <p class="text-4xl font-bold">{{ stats.users?.total ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title text-primary mb-2">Rezervácie podľa stavu</h2>
                        <ul class="space-y-2">
                            <li v-for="status in statusBreakdown" :key="status.value" class="flex items-center justify-between">
                                <span class="badge" :class="status.badgeClass">{{ status.label }}</span>
                                <span class="font-semibold">{{ status.count }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title text-primary mb-2">Areály a ihriská</h2>
                        <ul class="space-y-2">
                            <li class="flex items-center justify-between">
                                <span>Areály</span>
                                <span class="font-semibold">{{ stats.facilities?.areas ?? 0 }}</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span>Ihriská celkovo</span>
                                <span class="font-semibold">{{ stats.facilities?.playgrounds ?? 0 }}</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span>Aktívne ihriská</span>
                                <span class="font-semibold">{{ stats.facilities?.active_playgrounds ?? 0 }}</span>
                            </li>
                        </ul>

                        <div class="divider my-2"></div>

                        <h3 class="font-semibold mb-2">Najobľúbenejšie ihriská</h3>
                        <ol v-if="stats.top_playgrounds?.length" class="space-y-1 list-decimal list-inside text-sm">
                            <li v-for="playground in stats.top_playgrounds" :key="playground.name">
                                {{ playground.name }}
                                <span class="text-base-content/60" v-if="playground.area_name">({{ playground.area_name }})</span>
                                &mdash; {{ playground.reservations }} rezervácií
                            </li>
                        </ol>
                        <p v-else class="text-sm text-base-content/50">Zatiaľ žiadne schválené rezervácie.</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import http from "../../http.js";
import 'notyf/notyf.min.css';
import {showErrorToast} from "../../constants/toast.js";

const stats = ref({});
const loading = ref(true);

const statusLabels = {
    unverified: 'Čaká na overenie e-mailu',
    awaiting_payment: 'Čaká na platbu kartou',
    pending_approval: 'Čaká na platbu',
    approved: 'Schválené',
    rejected: 'Zamietnuté',
    cancelled: 'Zrušené',
};

const statusBadgeClasses = {
    unverified: 'badge-ghost',
    awaiting_payment: 'badge-warning',
    pending_approval: 'badge-warning',
    approved: 'badge-success',
    rejected: 'badge-error',
    cancelled: 'badge-error badge-outline',
};

const statusBreakdown = computed(() => {
    const byStatus = stats.value.reservations?.by_status ?? {};
    return Object.keys(statusLabels).map(value => ({
        value,
        label: statusLabels[value],
        badgeClass: statusBadgeClasses[value],
        count: byStatus[value] ?? 0,
    }));
});

const formatEur = (value) => `${Number(value ?? 0).toFixed(2)} €`;

const fetchStatistics = async () => {
    try {
        const response = await http.request('/api/admin/statistics');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Nepodarilo sa načítať štatistiky.');
        stats.value = data;
    } catch (error) {
        showErrorToast(error.message ?? 'Chyba pri načítaní štatistík.');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchStatistics);
</script>
