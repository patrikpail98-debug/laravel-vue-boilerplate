<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md space-y-10">
        <!-- Areas -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold text-primary">Areály</h1>
                <button class="btn btn-primary" @click="openCreateAreaModal">
                    <PlusCircleIcon class="w-5 h-5 mr-2"/>
                    Nový areál
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                    <tr>
                        <th class="bg-base-200">Názov</th>
                        <th class="bg-base-200">Adresa</th>
                        <th class="bg-base-200">Ihriská</th>
                        <th class="bg-base-200 text-right">Akcie</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="area in areas" :key="area.id">
                        <td class="font-bold">{{ area.name }}</td>
                        <td>{{ area.address }}</td>
                        <td>{{ area.playgrounds?.length ?? 0 }}</td>
                        <td class="text-right space-x-1">
                            <button class="btn btn-sm btn-ghost" @click="openEditAreaModal(area)">
                                <PencilIcon class="w-4 h-4"/>
                            </button>
                            <button class="btn btn-sm btn-ghost text-error" @click="confirmDeleteArea(area)">
                                <TrashIcon class="w-4 h-4"/>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!areas.length">
                        <td colspan="4" class="text-center text-base-content/50 py-8">Žiadne areály.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Playgrounds -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold text-primary">Ihriská</h1>
                <button class="btn btn-primary" :disabled="!areas.length" @click="openCreatePlaygroundModal">
                    <PlusCircleIcon class="w-5 h-5 mr-2"/>
                    Nové ihrisko
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                    <tr>
                        <th class="bg-base-200">Foto</th>
                        <th class="bg-base-200">Názov</th>
                        <th class="bg-base-200">Areál</th>
                        <th class="bg-base-200">Cena / 30 min</th>
                        <th class="bg-base-200">Max. dĺžka</th>
                        <th class="bg-base-200">Horizont</th>
                        <th class="bg-base-200">Aktívne</th>
                        <th class="bg-base-200 text-right">Akcie</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="playground in playgrounds" :key="playground.id">
                        <td>
                            <div class="w-12 h-12 rounded bg-base-200 overflow-hidden flex items-center justify-center">
                                <img v-if="playground.image_url" :src="playground.image_url" :alt="playground.name" class="w-full h-full object-cover"/>
                                <span v-else class="text-xs text-base-content/40">—</span>
                            </div>
                        </td>
                        <td class="font-bold">{{ playground.name }}</td>
                        <td>{{ playground.area?.name }}</td>
                        <td>{{ Number(playground.price_per_30min).toFixed(2) }} &euro;</td>
                        <td>{{ playground.max_duration_minutes }} min</td>
                        <td>{{ playground.max_horizon_days }} dní</td>
                        <td>
                            <span class="badge" :class="playground.is_active ? 'badge-success' : 'badge-ghost'">
                                {{ playground.is_active ? 'Áno' : 'Nie' }}
                            </span>
                        </td>
                        <td class="text-right space-x-1">
                            <button class="btn btn-sm btn-ghost" @click="openEditPlaygroundModal(playground)">
                                <PencilIcon class="w-4 h-4"/>
                            </button>
                            <button class="btn btn-sm btn-ghost text-error" @click="confirmDeletePlayground(playground)">
                                <TrashIcon class="w-4 h-4"/>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!playgrounds.length">
                        <td colspan="8" class="text-center text-base-content/50 py-8">Žiadne ihriská.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Area modal -->
        <dialog :class="{'modal-open': showAreaModal}" class="modal">
            <div class="modal-box">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="showAreaModal = false">✕</button>
                <h3 class="font-bold text-lg mb-6">{{ isEditingArea ? 'Upraviť areál' : 'Nový areál' }}</h3>

                <form @submit.prevent="submitAreaForm" class="space-y-4">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Názov</legend>
                        <input type="text" class="input input-bordered w-full" v-model="areaForm.name" required/>
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Adresa</legend>
                        <input type="text" class="input input-bordered w-full" v-model="areaForm.address" required/>
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Popis</legend>
                        <textarea class="textarea textarea-bordered w-full" v-model="areaForm.description"></textarea>
                    </fieldset>

                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" @click="showAreaModal = false">Zrušiť</button>
                        <button type="submit" class="btn btn-primary" :disabled="isSubmittingArea">
                            <span v-if="isSubmittingArea" class="loading loading-spinner"></span>
                            {{ isEditingArea ? 'Uložiť' : 'Vytvoriť' }}
                        </button>
                    </div>
                </form>
            </div>
        </dialog>

        <!-- Playground modal -->
        <dialog :class="{'modal-open': showPlaygroundModal}" class="modal">
            <div class="modal-box">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="showPlaygroundModal = false">✕</button>
                <h3 class="font-bold text-lg mb-6">{{ isEditingPlayground ? 'Upraviť ihrisko' : 'Nové ihrisko' }}</h3>

                <form @submit.prevent="submitPlaygroundForm" class="space-y-4">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Areál</legend>
                        <select class="select select-bordered w-full" v-model="playgroundForm.area_id" required>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Názov</legend>
                        <input type="text" class="input input-bordered w-full" v-model="playgroundForm.name" required/>
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Popis</legend>
                        <textarea class="textarea textarea-bordered w-full" rows="3" v-model="playgroundForm.description"
                                  placeholder="Popis ihriska zobrazený na jeho detailovej stránke"></textarea>
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Cena za 30 minút (&euro;)</legend>
                        <input type="number" step="0.01" min="0" class="input input-bordered w-full" v-model.number="playgroundForm.price_per_30min" required/>
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Maximálna dĺžka rezervácie (minúty)</legend>
                        <input type="number" step="30" min="30" class="input input-bordered w-full" v-model.number="playgroundForm.max_duration_minutes" required/>
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Rezervovať dopredu (dni)</legend>
                        <input type="number" min="1" class="input input-bordered w-full" v-model.number="playgroundForm.max_horizon_days" required/>
                    </fieldset>
                    <div class="grid grid-cols-2 gap-4">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Zemepisná šírka (latitude)</legend>
                            <input type="number" step="0.0000001" class="input input-bordered w-full" v-model.number="playgroundForm.latitude" placeholder="48.1717"/>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Zemepisná dĺžka (longitude)</legend>
                            <input type="number" step="0.0000001" class="input input-bordered w-full" v-model.number="playgroundForm.longitude" placeholder="17.0574"/>
                        </fieldset>
                    </div>
                    <p class="text-xs text-base-content/60 -mt-2">Súradnice určujú polohu ihriska na mape športovísk. Nájdete ich napr. kliknutím pravým tlačidlom na miesto v OpenStreetMap/Google Maps.</p>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Otváracie hodiny</legend>
                        <div class="space-y-2">
                            <div v-for="day in weekDays" :key="day.key" class="flex items-center gap-3">
                                <span class="w-24 text-sm shrink-0">{{ day.label }}</span>
                                <label class="flex items-center gap-1 text-sm shrink-0">
                                    <input type="checkbox" class="checkbox checkbox-sm" v-model="playgroundForm.opening_hours[day.key].is_closed"/>
                                    Zatvorené
                                </label>
                                <template v-if="!playgroundForm.opening_hours[day.key].is_closed">
                                    <input type="time" class="input input-bordered input-sm" v-model="playgroundForm.opening_hours[day.key].opens_at"/>
                                    <span class="text-sm">&ndash;</span>
                                    <input type="time" class="input input-bordered input-sm" v-model="playgroundForm.opening_hours[day.key].closes_at"/>
                                </template>
                            </div>
                        </div>
                    </fieldset>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="checkbox" v-model="playgroundForm.is_active"/>
                        Aktívne (viditeľné na rezerváciu)
                    </label>

                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" @click="showPlaygroundModal = false">Zrušiť</button>
                        <button type="submit" class="btn btn-primary" :disabled="isSubmittingPlayground">
                            <span v-if="isSubmittingPlayground" class="loading loading-spinner"></span>
                            {{ isEditingPlayground ? 'Uložiť' : 'Vytvoriť' }}
                        </button>
                    </div>
                </form>

                <div v-if="isEditingPlayground" class="mt-6 pt-6 border-t border-base-300">
                    <h4 class="font-semibold mb-3">Obrázok ihriska</h4>
                    <div class="flex items-center gap-4">
                        <div class="w-24 h-24 rounded bg-base-200 overflow-hidden flex items-center justify-center shrink-0">
                            <img v-if="editingPlaygroundImageUrl" :src="editingPlaygroundImageUrl" :alt="playgroundForm.name" class="w-full h-full object-cover"/>
                            <span v-else class="text-xs text-base-content/40">Bez fotky</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <input ref="imageInput" type="file" accept="image/png,image/jpeg,image/webp" class="file-input file-input-bordered file-input-sm"
                                   @change="onImageFileSelected"/>
                            <div class="flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary" :disabled="!selectedImageFile || isUploadingImage" @click="uploadPlaygroundImage">
                                    <span v-if="isUploadingImage" class="loading loading-spinner loading-xs"></span>
                                    Nahrať obrázok
                                </button>
                                <button v-if="editingPlaygroundImageUrl" type="button" class="btn btn-sm btn-ghost text-error" :disabled="isUploadingImage" @click="removePlaygroundImage">
                                    Odstrániť
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </dialog>

        <!-- Delete confirmation -->
        <dialog :class="{'modal-open': showDeleteModal}" class="modal">
            <div class="modal-box">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="showDeleteModal = false">✕</button>
                <h3 class="font-bold text-lg mb-2">Potvrdiť vymazanie</h3>
                <p class="py-4">Naozaj vymazať <strong>{{ deleteTarget?.name }}</strong>? Táto akcia je nevratná.</p>
                <div class="modal-action">
                    <button class="btn btn-ghost" @click="showDeleteModal = false">Zrušiť</button>
                    <button class="btn btn-error" :disabled="isDeleting" @click="performDelete">
                        <span v-if="isDeleting" class="loading loading-spinner"></span>
                        Áno, vymazať
                    </button>
                </div>
            </div>
        </dialog>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {PencilIcon, PlusCircleIcon, TrashIcon} from '@heroicons/vue/24/outline';
import 'notyf/notyf.min.css';
import {showErrorToast, showSuccessToast} from '../../constants/toast.js';
import http from '../../http.js';

const areas = ref([]);
const playgrounds = ref([]);

const weekDays = [
    {key: 'mon', label: 'Pondelok'},
    {key: 'tue', label: 'Utorok'},
    {key: 'wed', label: 'Streda'},
    {key: 'thu', label: 'Štvrtok'},
    {key: 'fri', label: 'Piatok'},
    {key: 'sat', label: 'Sobota'},
    {key: 'sun', label: 'Nedeľa'},
];

const buildDefaultOpeningHours = () => Object.fromEntries(
    weekDays.map(day => [day.key, {
        is_closed: day.key === 'sat' || day.key === 'sun',
        opens_at: '08:00',
        closes_at: '20:00',
    }])
);

const fetchAreas = async () => {
    const response = await http.request('/api/admin/facilities/areas');
    const data = await response.json();
    if (!response.ok) throw new Error(data.message ?? 'Nepodarilo sa načítať areály.');
    areas.value = data;
};

const fetchPlaygrounds = async () => {
    const response = await http.request('/api/admin/facilities/playgrounds');
    const data = await response.json();
    if (!response.ok) throw new Error(data.message ?? 'Nepodarilo sa načítať ihriská.');
    playgrounds.value = data;
};

const refreshAll = async () => {
    try {
        await Promise.all([fetchAreas(), fetchPlaygrounds()]);
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať dáta.');
    }
};

// --- Areas ---
const showAreaModal = ref(false);
const isEditingArea = ref(false);
const isSubmittingArea = ref(false);
const areaForm = ref({id: null, name: '', address: '', description: ''});

const openCreateAreaModal = () => {
    areaForm.value = {id: null, name: '', address: '', description: ''};
    isEditingArea.value = false;
    showAreaModal.value = true;
};

const openEditAreaModal = (area) => {
    areaForm.value = {id: area.id, name: area.name, address: area.address, description: area.description ?? ''};
    isEditingArea.value = true;
    showAreaModal.value = true;
};

const submitAreaForm = async () => {
    isSubmittingArea.value = true;
    try {
        const url = isEditingArea.value ? `/api/admin/facilities/areas/${areaForm.value.id}` : '/api/admin/facilities/areas';
        const response = await http.request(url, {
            method: isEditingArea.value ? 'PUT' : 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(areaForm.value),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Uloženie areálu zlyhalo.');

        showSuccessToast('Areál bol uložený.');
        showAreaModal.value = false;
        await refreshAll();
    } catch (error) {
        showErrorToast(error.message ?? 'Uloženie areálu zlyhalo.');
    } finally {
        isSubmittingArea.value = false;
    }
};

// --- Playgrounds ---
const showPlaygroundModal = ref(false);
const isEditingPlayground = ref(false);
const isSubmittingPlayground = ref(false);
const playgroundForm = ref({
    id: null,
    area_id: null,
    name: '',
    description: '',
    price_per_30min: 0,
    max_duration_minutes: 120,
    max_horizon_days: 60,
    is_active: true,
    latitude: null,
    longitude: null,
    opening_hours: buildDefaultOpeningHours(),
});
const editingPlaygroundImageUrl = ref(null);

const openCreatePlaygroundModal = () => {
    playgroundForm.value = {
        id: null,
        area_id: areas.value[0]?.id ?? null,
        name: '',
        description: '',
        price_per_30min: 0,
        max_duration_minutes: 120,
        max_horizon_days: 60,
        is_active: true,
        latitude: null,
        longitude: null,
        opening_hours: buildDefaultOpeningHours(),
    };
    editingPlaygroundImageUrl.value = null;
    isEditingPlayground.value = false;
    showPlaygroundModal.value = true;
};

const openEditPlaygroundModal = (playground) => {
    const openingHours = buildDefaultOpeningHours();
    if (playground.opening_hours) {
        weekDays.forEach(day => {
            if (playground.opening_hours[day.key]) {
                openingHours[day.key] = {...openingHours[day.key], ...playground.opening_hours[day.key]};
            }
        });
    }

    playgroundForm.value = {
        id: playground.id,
        area_id: playground.area_id,
        name: playground.name,
        description: playground.description ?? '',
        price_per_30min: Number(playground.price_per_30min),
        max_duration_minutes: playground.max_duration_minutes,
        max_horizon_days: playground.max_horizon_days,
        is_active: playground.is_active,
        latitude: playground.latitude !== null ? Number(playground.latitude) : null,
        longitude: playground.longitude !== null ? Number(playground.longitude) : null,
        opening_hours: openingHours,
    };
    editingPlaygroundImageUrl.value = playground.image_url ?? null;
    isEditingPlayground.value = true;
    showPlaygroundModal.value = true;
};

const submitPlaygroundForm = async () => {
    isSubmittingPlayground.value = true;
    try {
        const url = isEditingPlayground.value
            ? `/api/admin/facilities/playgrounds/${playgroundForm.value.id}`
            : '/api/admin/facilities/playgrounds';
        const payload = {
            ...playgroundForm.value,
            // Empty number inputs come through as '' (not null) - normalize before sending,
            // otherwise the backend's `numeric` rule rejects the request.
            latitude: playgroundForm.value.latitude === '' ? null : playgroundForm.value.latitude,
            longitude: playgroundForm.value.longitude === '' ? null : playgroundForm.value.longitude,
            opening_hours: Object.fromEntries(
                Object.entries(playgroundForm.value.opening_hours).map(([key, day]) => [key, {
                    is_closed: day.is_closed,
                    opens_at: day.is_closed || !day.opens_at ? null : day.opens_at,
                    closes_at: day.is_closed || !day.closes_at ? null : day.closes_at,
                }])
            ),
        };

        const response = await http.request(url, {
            method: isEditingPlayground.value ? 'PUT' : 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Uloženie ihriska zlyhalo.');

        showSuccessToast('Ihrisko bolo uložené.');
        showPlaygroundModal.value = false;
        await refreshAll();
    } catch (error) {
        showErrorToast(error.message ?? 'Uloženie ihriska zlyhalo.');
    } finally {
        isSubmittingPlayground.value = false;
    }
};

// --- Playground image upload ---
const imageInput = ref(null);
const selectedImageFile = ref(null);
const isUploadingImage = ref(false);

const onImageFileSelected = (event) => {
    selectedImageFile.value = event.target.files[0] ?? null;
};

const uploadPlaygroundImage = async () => {
    if (!selectedImageFile.value || !playgroundForm.value.id) return;

    isUploadingImage.value = true;
    try {
        const formData = new FormData();
        formData.append('image', selectedImageFile.value);

        const response = await http.request(`/api/admin/facilities/playgrounds/${playgroundForm.value.id}/image`, {
            method: 'POST',
            body: formData,
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Nahratie obrázka zlyhalo.');

        editingPlaygroundImageUrl.value = data.image_url;
        selectedImageFile.value = null;
        if (imageInput.value) imageInput.value.value = '';
        showSuccessToast('Obrázok bol nahratý.');
        await refreshAll();
    } catch (error) {
        showErrorToast(error.message ?? 'Nahratie obrázka zlyhalo.');
    } finally {
        isUploadingImage.value = false;
    }
};

const removePlaygroundImage = async () => {
    if (!playgroundForm.value.id) return;

    isUploadingImage.value = true;
    try {
        const response = await http.request(`/api/admin/facilities/playgrounds/${playgroundForm.value.id}/image`, {method: 'DELETE'});
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Odstránenie obrázka zlyhalo.');

        editingPlaygroundImageUrl.value = null;
        showSuccessToast('Obrázok bol odstránený.');
        await refreshAll();
    } catch (error) {
        showErrorToast(error.message ?? 'Odstránenie obrázka zlyhalo.');
    } finally {
        isUploadingImage.value = false;
    }
};

// --- Delete (shared modal for areas & playgrounds) ---
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const deleteTarget = ref(null);
const deleteType = ref('area');

const confirmDeleteArea = (area) => {
    deleteTarget.value = area;
    deleteType.value = 'area';
    showDeleteModal.value = true;
};

const confirmDeletePlayground = (playground) => {
    deleteTarget.value = playground;
    deleteType.value = 'playground';
    showDeleteModal.value = true;
};

const performDelete = async () => {
    isDeleting.value = true;
    try {
        const url = deleteType.value === 'area'
            ? `/api/admin/facilities/areas/${deleteTarget.value.id}`
            : `/api/admin/facilities/playgrounds/${deleteTarget.value.id}`;
        const response = await http.request(url, {method: 'DELETE'});
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'Vymazanie zlyhalo.');

        showSuccessToast('Záznam bol vymazaný.');
        showDeleteModal.value = false;
        await refreshAll();
    } catch (error) {
        showErrorToast(error.message ?? 'Vymazanie zlyhalo.');
    } finally {
        isDeleting.value = false;
    }
};

onMounted(refreshAll);
</script>
