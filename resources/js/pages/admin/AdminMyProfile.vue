<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center mb-8">
            <UserCircleIcon class="w-8 h-8 mr-3 text-primary"/>
            <h1 class="text-2xl font-bold text-primary">Môj profil</h1>
        </div>

        <div class="p-6 bg-base-200 rounded-box mb-6">
            <h2 class="text-xl font-semibold mb-6">Dvojfaktorové overenie (2FA)</h2>

            <div v-if="!tfaEnabled">
                <p class="mb-4">Zabezpečte si účet pomocou dvojfaktorového overenia.</p>
                <div class="flex gap-4">
                    <button @click="enableTfaViaApp" class="btn btn-primary" :disabled="tfaLoading">
                        <span v-if="tfaLoading" class="loading loading-spinner"></span>
                        Použiť autentifikačnú aplikáciu
                    </button>
                    <button @click="enableTfaViaEmail" class="btn btn-secondary" :disabled="tfaLoading">
                        <span v-if="tfaLoading" class="loading loading-spinner"></span>
                        Použiť e-mail
                    </button>
                </div>
            </div>

            <div v-else>
                <div class="alert alert-success mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Dvojfaktorové overenie je zapnuté. Môžete ho použiť
                        <strong v-if="tfaMethod === 'app'">cez aplikáciu</strong>
                        <strong v-if="tfaMethod === 'email'">cez e-mail</strong>.
                    </span>
                </div>
                <button @click="openDisableTfaModal" class="btn btn-error" :disabled="tfaLoading">
                    <span v-if="tfaLoading" class="loading loading-spinner"></span>
                    Vypnúť 2FA
                </button>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="p-6 bg-base-200 rounded-box">
            <h2 class="text-xl font-semibold mb-6">Zmena hesla</h2>

            <form @submit.prevent="updatePassword">
                <div class="space-y-4">
                    <!-- Current Password -->
                    <div class="form-control">
                        <label for="current_password" class="label">
                            <span class="label-text">Aktuálne heslo</span>
                        </label>
                        <input
                            id="current_password"
                            v-model="form.current_password"
                            type="password"
                            placeholder="••••••••"
                            class="input input-bordered w-full"
                            required
                        />
                    </div>

                    <!-- New Password -->
                    <div class="form-control">
                        <label for="password" class="label">
                            <span class="label-text">Nové heslo</span>
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="Aspoň 8 znakov, veľké/malé písmená, čísla, symboly"
                            class="input input-bordered w-full"
                            required
                        />
                    </div>

                    <!-- Confirm New Password -->
                    <div class="form-control">
                        <label for="password_confirmation" class="label">
                            <span class="label-text">Potvrďte nové heslo</span>
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Zopakujte nové heslo"
                            class="input input-bordered w-full"
                            required
                        />
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex justify-end">
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="isSaving"
                    >
                        <span v-if="isSaving" class="loading loading-spinner"></span>
                        <KeyIcon v-else class="w-5 h-5 mr-2"/>
                        Zmeniť heslo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <dialog :class="{'modal-open': showTfaModal}" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Nastavenie dvojfaktorového overenia</h3>
            <p class="py-4">Naskenujte QR kód pomocou aplikácie (napr. Google Authenticator).</p>

            <img v-if="qrCodeDataUri" :src="qrCodeDataUri" alt="QR kód pre dvojfaktorové overenie"
                 class="bg-white p-4 inline-block rounded-lg shadow-inner" />

            <p class="py-4">Následne zadajte kód z aplikácie.</p>

            <div class="form-control">
                <input type="text" v-model="tfaCode" placeholder="123456" class="input input-bordered" />
            </div>

            <div class="modal-action">
                <button class="btn btn-ghost" @click="cancelTFA">Zrušiť</button>
                <button class="btn btn-primary" @click="confirmTFA" :disabled="tfaLoading">Potvrdiť</button>
            </div>
        </div>
    </dialog>

    <dialog :class="{'modal-open': showDisableTfaModal}" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Vypnúť dvojfaktorové overenie</h3>
            <p class="py-4">Pre potvrdenie zadajte svoje aktuálne heslo.</p>

            <form @submit.prevent="disableTFA">
                <div class="form-control">
                    <input
                        type="password"
                        v-model="disablePassword"
                        placeholder="Aktuálne heslo"
                        class="input input-bordered w-full"
                        autocomplete="current-password"
                        required
                    />
                </div>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" @click="cancelDisableTfa">Zrušiť</button>
                    <button type="submit" class="btn btn-error" :disabled="tfaLoading || !disablePassword">
                        <span v-if="tfaLoading" class="loading loading-spinner"></span>
                        Vypnúť 2FA
                    </button>
                </div>
            </form>
        </div>
    </dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import {UserCircleIcon, KeyIcon} from '@heroicons/vue/24/outline';
import http from "../../http.js";
import 'notyf/notyf.min.css';
import {showErrorToast, showSuccessToast} from "../../constants/toast.js";
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const tfaEnabled = computed(() => authStore.user?.two_factor_enabled);
const tfaMethod = computed(() => authStore.user?.two_factor_method);
const tfaLoading = ref(false);
const showTfaModal = ref(false);
const showDisableTfaModal = ref(false);
const disablePassword = ref('');
const qrCode = ref('');
const tfaCode = ref('');

// Render the (server-generated, trusted) QR SVG as an <img> data URI instead of
// v-html: an <img>-loaded SVG cannot execute script, so this stays safe even if
// the source were ever compromised.
const qrCodeDataUri = computed(() =>
    qrCode.value ? `data:image/svg+xml;utf8,${encodeURIComponent(qrCode.value)}` : ''
);

const enableTfaViaApp = async () => {
    tfaLoading.value = true;
    try {
        const response = await http.request('/api/user/two-factor-authentication/enable', { method: 'POST' });
        const data = await response.json();
        qrCode.value = data.qr_code_svg;
        showTfaModal.value = true;
    } catch (error) {
        showErrorToast('Nepodarilo sa zapnúť 2FA.');
    } finally {
        tfaLoading.value = false;
    }
};

const enableTfaViaEmail = async () => {
    tfaLoading.value = true;
    try {
        await http.request('/api/user/two-factor-authentication/enable-email', { method: 'POST' });
        await authStore.fetchUser(); // Refresh user state
        showSuccessToast('2FA bolo zapnuté cez e-mail.');
    } catch (error) {
        showErrorToast('Nepodarilo sa zapnúť 2FA.');
    } finally {
        tfaLoading.value = false;
    }
};

const confirmTFA = async () => {
    tfaLoading.value = true;
    try {
        await http.request('/api/user/two-factor-authentication/confirm', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ code: tfaCode.value }),
        });
        await authStore.fetchUser();
        showSuccessToast('2FA bolo úspešne zapnuté!');
        showTfaModal.value = false;
        qrCode.value = '';
        tfaCode.value = '';
    } catch (error) {
        showErrorToast('Neplatný 2FA kód.');
    } finally {
        tfaLoading.value = false;
    }
};

const openDisableTfaModal = () => {
    disablePassword.value = '';
    showDisableTfaModal.value = true;
};

const cancelDisableTfa = () => {
    showDisableTfaModal.value = false;
    disablePassword.value = '';
};

const disableTFA = async () => {
    // The server requires the current password to disable 2FA, so a stolen
    // session token alone can't turn it off. The password is collected via the
    // masked input in the disable modal (never a plaintext prompt()).
    if (!disablePassword.value) return;
    tfaLoading.value = true;
    try {
        const response = await http.request('/api/user/two-factor-authentication/disable', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: disablePassword.value }),
        });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message || 'Nepodarilo sa vypnúť 2FA.');
        }
        showDisableTfaModal.value = false;
        disablePassword.value = '';
        await authStore.fetchUser();
        showSuccessToast('2FA bolo vypnuté!');
    } catch (error) {
        // Keep the modal open on a wrong password so the user can retry.
        showErrorToast(error.message || 'Nepodarilo sa vypnúť 2FA.');
    } finally {
        tfaLoading.value = false;
    }
};

const cancelTFA = () => {
    // Just close the setup modal. Any secret generated by enable() but never
    // confirmed stays unused (two_factor_enabled is still false) and is
    // overwritten the next time setup starts - no privileged disable call, so
    // cancelling never triggers the password prompt above.
    showTfaModal.value = false;
    qrCode.value = '';
    tfaCode.value = '';
};

// Reactive state for the form fields
const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Loading state for the submit button
const isSaving = ref(false);

// Function to handle form submission
const updatePassword = async () => {
    // Mirrors the server's actual rule (Password::min(8)->mixedCase()->numbers()->symbols()
    // in UserController::updatePassword) so a rejection shows up immediately
    // instead of only after a round-trip to the server.
    if (form.value.password.length < 8
        || !/[a-z]/.test(form.value.password)
        || !/[A-Z]/.test(form.value.password)
        || !/[0-9]/.test(form.value.password)
        || !/[^a-zA-Z0-9]/.test(form.value.password)) {
        showErrorToast('Heslo musí mať aspoň 8 znakov a obsahovať veľké aj malé písmeno, číslicu a symbol.');
        return;
    }

    if (form.value.password !== form.value.password_confirmation) {
        showErrorToast('Heslá sa nezhodujú.');
        return;
    }

    isSaving.value = true;

    try {
        const response = await http.request('/api/user/password', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            // Handle validation errors from Laravel
            if (response.status === 422 && data.errors) {
                const errorMessages = Object.values(data.errors).flat().join(' ');
                showErrorToast(errorMessages || 'Opravte prosím chyby vo formulári.');
            } else {
                throw new Error(data.message || 'Heslo sa nepodarilo zmeniť.');
            }
            return; // Stop execution on validation error
        }

        showSuccessToast(data.message || 'Heslo bolo zmenené.');
        // Reset form after successful submission
        form.value.current_password = '';
        form.value.password = '';
        form.value.password_confirmation = '';

    } catch (error) {
        console.error('Error updating password:', error);
        showErrorToast(error.message || 'Neočakávaná chyba.');
    } finally {
        isSaving.value = false;
    }
};
</script>
