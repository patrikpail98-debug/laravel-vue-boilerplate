<!-- resources/js/components/ui/CookieConsentBanner.vue -->
<template>
    <div v-if="visible" class="fixed inset-x-0 bottom-0 z-50 p-4" role="dialog" aria-label="Súhlas s cookies" aria-live="polite">
        <div class="max-w-3xl mx-auto card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body p-5">
                <h2 class="font-bold text-base">Používanie cookies</h2>
                <p class="text-sm text-base-content/80">
                    Táto webová stránka používa cookies. Technické cookies sú nevyhnutné pre jej fungovanie a sú vždy
                    zapnuté. Analytické cookies (Google Analytics) používame len s Vaším súhlasom na zlepšovanie
                    stránky. Viac informácií nájdete v
                    <router-link to="/ochrana-osobnych-udajov" class="link link-primary">ochrane osobných údajov</router-link>.
                </p>

                <div v-if="detailsOpen" class="mt-3 space-y-3">
                    <label class="flex items-center justify-between gap-4">
                        <span>
                            <span class="font-medium">Nevyhnutné cookies</span>
                            <span class="block text-xs text-base-content/60">Potrebné pre základné fungovanie stránky, nemožno vypnúť.</span>
                        </span>
                        <input type="checkbox" class="toggle toggle-primary" checked disabled/>
                    </label>
                    <label class="flex items-center justify-between gap-4">
                        <span>
                            <span class="font-medium">Analytické cookies</span>
                            <span class="block text-xs text-base-content/60">Google Analytics – pomáha nám pochopiť návštevnosť stránky.</span>
                        </span>
                        <input type="checkbox" class="toggle toggle-primary" v-model="analyticsEnabled"/>
                    </label>
                </div>

                <div class="card-actions justify-end mt-4 flex-wrap gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" @click="detailsOpen = !detailsOpen">
                        {{ detailsOpen ? 'Skryť podrobnosti' : 'Podrobnosti' }}
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" @click="reject">Odmietnuť</button>
                    <button v-if="detailsOpen" type="button" class="btn btn-secondary btn-sm" @click="saveSelection">
                        Uložiť nastavenia
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" @click="acceptAll">Prijať všetko</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import {getConsent, setConsent} from '@/utils/analytics.js';

const visible = ref(getConsent() === null);
const detailsOpen = ref(false);
const analyticsEnabled = ref(false);

const acceptAll = () => {
    setConsent(true);
    visible.value = false;
};

const reject = () => {
    setConsent(false);
    visible.value = false;
};

const saveSelection = () => {
    setConsent(analyticsEnabled.value);
    visible.value = false;
};
</script>
