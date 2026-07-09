<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex items-center mb-8">
            <Cog6ToothIcon class="w-8 h-8 mr-3 text-primary"/>
            <h1 class="text-2xl font-bold text-primary">Nastavenia systému</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div>
                <!-- General Settings Card -->
                <div class="p-6 bg-base-200 rounded-box mb-8">
                    <h2 class="text-xl font-semibold mb-6">Všeobecné nastavenia</h2>
                    <div class="space-y-4">
                        <div class="form-control">
                            <label class="label cursor-pointer">
                                <span class="label-text">Povoliť registráciu nových používateľov</span>
                                <input type="checkbox" class="toggle toggle-primary"
                                       v-model="settings['auth.registrations.enabled']"/>
                            </label>
                        </div>
                        <div class="form-control">
                            <label for="max_upload_size" class="label">
                                <span class="label-text">Maximálna veľkosť nahrávaného súboru (KB)</span>
                            </label>
                            <input
                                id="max_upload_size"
                                v-model.number="settings['media.upload.max_size_kb']"
                                type="number"
                                class="input input-bordered w-full"
                            />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button @click="saveSettings" class="btn btn-primary" :disabled="isSaving">
                            <span v-if="isSaving" class="loading loading-spinner"></span>
                            Uložiť
                        </button>
                    </div>
                </div>

                <!-- Reservation Payment Settings Card -->
                <div class="p-6 bg-base-200 rounded-box mb-8">
                    <h2 class="text-xl font-semibold mb-6">Platobné údaje pre rezervácie</h2>
                    <div class="space-y-4">
                        <div class="form-control">
                            <label for="org_name" class="label">
                                <span class="label-text">Názov organizácie</span>
                            </label>
                            <input
                                id="org_name"
                                v-model="settings['org.name']"
                                type="text"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <div class="form-control">
                            <label for="org_iban" class="label">
                                <span class="label-text">IBAN</span>
                            </label>
                            <input
                                id="org_iban"
                                v-model="settings['org.iban']"
                                type="text"
                                placeholder="SK00 0000 0000 0000 0000 0000"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <div class="form-control">
                            <label for="org_bank_name" class="label">
                                <span class="label-text">Názov banky (voliteľné)</span>
                            </label>
                            <input
                                id="org_bank_name"
                                v-model="settings['org.bank_name']"
                                type="text"
                                class="input input-bordered w-full"
                            />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button @click="saveSettings" class="btn btn-primary" :disabled="isSaving">
                            <span v-if="isSaving" class="loading loading-spinner"></span>
                            Uložiť
                        </button>
                    </div>
                </div>

                <!-- Contact Settings Card -->
                <div class="p-6 bg-base-200 rounded-box mb-8">
                    <h2 class="text-xl font-semibold mb-6">Kontaktné údaje</h2>
                    <div class="space-y-4">
                        <div class="form-control">
                            <label for="contact_address" class="label">
                                <span class="label-text">Adresa</span>
                            </label>
                            <input
                                id="contact_address"
                                v-model="settings['contact.address']"
                                type="text"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <div class="form-control">
                            <label for="contact_phone" class="label">
                                <span class="label-text">Telefón</span>
                            </label>
                            <input
                                id="contact_phone"
                                v-model="settings['contact.phone']"
                                type="text"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <div class="form-control">
                            <label for="contact_email" class="label">
                                <span class="label-text">E-mail</span>
                            </label>
                            <input
                                id="contact_email"
                                v-model="settings['contact.email']"
                                type="email"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <div class="form-control">
                            <label for="contact_hours" class="label">
                                <span class="label-text">Úradné hodiny</span>
                            </label>
                            <input
                                id="contact_hours"
                                v-model="settings['contact.hours']"
                                type="text"
                                placeholder="Po-Pi: 8:00 - 16:00"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label for="contact_latitude" class="label">
                                    <span class="label-text">Súradnice úradu – latitude</span>
                                </label>
                                <input
                                    id="contact_latitude"
                                    v-model="settings['contact.latitude']"
                                    type="text"
                                    placeholder="48.1717"
                                    class="input input-bordered w-full"
                                />
                            </div>
                            <div class="form-control">
                                <label for="contact_longitude" class="label">
                                    <span class="label-text">Súradnice úradu – longitude</span>
                                </label>
                                <input
                                    id="contact_longitude"
                                    v-model="settings['contact.longitude']"
                                    type="text"
                                    placeholder="17.0574"
                                    class="input input-bordered w-full"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button @click="saveSettings" class="btn btn-primary" :disabled="isSaving">
                            <span v-if="isSaving" class="loading loading-spinner"></span>
                            Uložiť
                        </button>
                    </div>
                </div>

                <!-- System Actions Card -->
                <div class="p-6 bg-base-200 rounded-box">
                    <h2 class="text-xl font-semibold mb-6">Systémové akcie</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm">Príklad príkazu</p>
                            <button @click="runArtisanCommand('app:example')"
                                    class="btn btn-secondary btn-sm"
                                    :disabled="isRunningCommand">
                                <span v-if="isRunningCommand" class="loading loading-spinner"></span>
                                Spustiť príkaz
                            </button>
                        </div>
                    </div>
                    <div v-if="commandOutput" class="mt-4">
                        <h4 class="font-bold text-sm">Výstup príkazu:</h4>
                        <pre class="bg-base-300 text-xs p-3 rounded-md overflow-x-auto whitespace-pre-wrap">{{
                                commandOutput
                            }}</pre>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <div class="p-6 bg-base-200 rounded-box mb-8">
                    <h2 class="text-xl font-semibold mb-6">Nastavenia e-mailu</h2>
                    <div class="form-control mb-4">
                        <label for="test_email" class="label">
                            <span class="label-text">Odoslať testovací e-mail</span>
                        </label>
                        <div class="join w-full">
                            <input
                                id="test_email"
                                v-model="testEmail"
                                type="email"
                                placeholder="admin@example.com"
                                class="input input-bordered join-item w-full"
                                @keyup.enter="sendTestEmail"
                            />
                            <button @click="sendTestEmail" class="btn btn-secondary join-item"
                                    :disabled="isSendingTestEmail">
                                <span v-if="isSendingTestEmail" class="loading loading-spinner"></span>
                                Odoslať
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cache Management Card -->
                <div class="p-6 bg-base-200 rounded-box">
                    <h2 class="text-xl font-semibold mb-6">Správa cache</h2>
                    <div class="form-control mb-4">
                        <label for="cache_pattern" class="label">
                            <span class="label-text">Nájsť kľúče podľa vzoru (napr. `example:*`)</span>
                        </label>
                        <div class="join w-full">
                            <input
                                id="cache_pattern"
                                v-model="cachePattern"
                                type="text"
                                placeholder="*"
                                class="input input-bordered join-item w-full"
                                @keyup.enter="fetchCacheKeys"
                            />
                            <button @click="fetchCacheKeys" class="btn btn-secondary join-item"
                                    :disabled="isLoadingKeys">
                                <span v-if="isLoadingKeys" class="loading loading-spinner"></span>
                                Hľadať
                            </button>
                        </div>
                    </div>

                    <div v-if="cacheKeys.length > 0" class="mb-4 max-h-60 overflow-y-auto bg-base-300 p-3 rounded-md">
                        <ul class="list-disc list-inside text-sm">
                            <li v-for="key in cacheKeys" :key="key">{{ key }}</li>
                        </ul>
                    </div>
                    <p v-if="searched && cacheKeys.length === 0" class="text-sm text-center my-4">Žiadne kľúče neboli nájdené.</p>

                    <div class="flex justify-end space-x-2">
                        <button @click="flushCache(cachePattern)" class="btn btn-warning btn-sm"
                                :disabled="!cachePattern || isLoadingKeys">Vymazať kľúče
                        </button>
                        <button @click="flushCache()" class="btn btn-error btn-sm">Vymazať všetko</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import {Cog6ToothIcon} from '@heroicons/vue/24/outline';
import http from "../../http.js";
import 'notyf/notyf.min.css';
import {showErrorToast, showSuccessToast} from "../../constants/toast.js";

// State for settings
const settings = ref({
    'auth.registrations.enabled': false,
    'media.upload.max_size_kb': 2048,
    'org.name': '',
    'org.iban': '',
    'org.bank_name': '',
    'contact.address': '',
    'contact.phone': '',
    'contact.email': '',
    'contact.hours': '',
    'contact.latitude': '',
    'contact.longitude': '',
});
const isSaving = ref(false);

// State for cache management
const cachePattern = ref('');
const cacheKeys = ref([]);
const isLoadingKeys = ref(false);
const searched = ref(false);

// State for system actions
const isRunningCommand = ref(false);
const commandOutput = ref('');

const testEmail = ref('');
const isSendingTestEmail = ref(false);

const sendTestEmail = async () => {
    if (!testEmail.value) {
        showErrorToast('Zadajte prosím e-mail.');
        return;
    }

    isSendingTestEmail.value = true;
    try {
        const response = await http.request('/api/admin/settings/send-test-email', {
            method: 'POST',
            body: JSON.stringify({email: testEmail.value}),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Chyba pri odosielaní testovacieho e-mailu.');
        }

        showSuccessToast(data.message);
    } catch (error) {
        showErrorToast(error.message);
        console.error(error);
    } finally {
        isSendingTestEmail.value = false;
    }
};

const fetchSettings = async () => {
    try {
        const response = await http.request('/api/admin/settings');
        const data = await response.json();
        // Coerce boolean-like strings to actual booleans for the toggle
        data['auth.registrations.enabled'] = (data['auth.registrations.enabled'] === '1' || data['auth.registrations.enabled'] === true);
        settings.value = data;
    } catch (error) {
        showErrorToast('Nastavenia sa nepodarilo načítať.');
        console.error(error);
    }
};

const saveSettings = async () => {
    isSaving.value = true;
    try {
        const response = await http.request('/api/admin/settings', {
            method: 'PUT',
            body: JSON.stringify({settings: settings.value}),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(JSON.stringify(errorData.errors) || 'Chyba pri ukladaní nastavení.');
        }
        showSuccessToast('Nastavenia boli uložené.');
    } catch (error) {
        showErrorToast('Chyba pri ukladaní nastavení: ' + error);
        console.error(error);
    } finally {
        isSaving.value = false;
    }
};

const fetchCacheKeys = async () => {
    isLoadingKeys.value = true;
    searched.value = true;
    cacheKeys.value = [];
    try {
        const response = await http.request(`/api/admin/settings/cache-keys?pattern=${encodeURIComponent(cachePattern.value)}`);
        cacheKeys.value = await response.json();
    } catch (error) {
        showErrorToast('Chyba pri načítaní cache kľúčov.');
        console.error(error);
    } finally {
        isLoadingKeys.value = false;
    }
};

const flushCache = async (pattern = null) => {
    const confirmation = pattern
        ? `Vymazať kľúče zodpovedajúce "${pattern}"?`
        : 'Naozaj chcete vymazať všetky cache kľúče? Táto akcia môže dočasne spomaliť aplikáciu.';

    if (!confirm(confirmation)) return;

    try {
        const response = await http.request('/api/admin/settings/flush-cache', {
            method: 'POST',
            body: JSON.stringify({pattern: pattern}),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        showSuccessToast(data.message);
        // Refresh keys list if a pattern was used
        if (pattern) {
            await fetchCacheKeys();
        } else {
            cacheKeys.value = [];
        }
    } catch (error) {
        showErrorToast('Chyba pri mazaní cache.');
        console.error(error);
    }
};

const runArtisanCommand = async (command) => {
    isRunningCommand.value = true;
    commandOutput.value = '';
    try {
        const response = await http.request('/api/admin/settings/run-command', {
            method: 'POST',
            body: JSON.stringify({command}),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        showSuccessToast(data.message);
        commandOutput.value = data.output || 'Výstup príkazu nie je dostupný.';
    } catch (error) {
        showErrorToast('Chyba pri spúšťaní príkazu.');
        console.error(error);
    } finally {
        isRunningCommand.value = false;
    }
};

onMounted(() => {
    fetchSettings();
});
</script>
