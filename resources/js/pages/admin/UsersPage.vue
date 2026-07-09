<template>
    <div class="p-6 bg-base-100 rounded-box shadow-md">
        <!-- Header with Create User Button -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-primary">Správa používateľov</h1>
            <button
                @click="openCreateModal"
                class="btn btn-primary"
                v-if="authStore.hasPermission('manage_users')"
            >
                <PlusCircleIcon class="w-5 h-5 mr-2"/>
                Vytvoriť používateľa
            </button>
        </div>

        <!-- Search and Filter -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="md:col-span-2">
                <div class="join w-full">
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Hľadať používateľov..."
                        class="input input-bordered join-item w-full"
                    />
                    <button class="btn join-item bg-base-300">
                        <MagnifyingGlassIcon class="w-5 h-5"/>
                    </button>
                </div>
            </div>
            <div>
                <select v-model="roleFilter" class="select select-bordered w-full">
                    <option value="">Všetky role</option>
                    <option v-for="role in roles" :key="role.id" :value="role.name">
                        {{ role.display_name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto h-100">
            <table class="table w-full">
                <thead>
                <tr>
                    <th class="bg-base-200">Meno</th>
                    <th class="bg-base-200">E-mail</th>
                    <th class="bg-base-200">Role</th>
                    <th class="bg-base-200">Posledné prihlásenie</th>
                    <th class="bg-base-200">Blokovaný</th>
                    <th class="bg-base-200">Oprávnenia</th>
                    <th class="bg-base-200 text-right">Akcie</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="user in filteredUsers" :key="user.id">
                    <td>
                        <div class="flex items-center space-x-3">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-10">
                                    <span>{{ userInitials(user.name) }}</span>
                                </div>
                            </div>
                            <div>
                                <div class="font-bold">{{ user.name }}</div>
                                <div class="text-sm text-gray-500">ID: {{ user.id }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ user.email }}</td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                <span
                    v-for="role in user.roles"
                    :key="role.id"
                    class="badge"
                    :class="roleBadgeClass(role.name)"
                >
                  {{ role.display_name }}
                </span>
                        </div>
                    </td>
                    <td>{{ formatDate(user.updated_at) }}</td>
                    <td>{{ user.is_blocked ? 'Áno' : 'Nie' }}</td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                <span
                    v-for="permission in user.permissions"
                    :key="permission"
                    class="badge badge-outline"
                >
                  {{ permission }}
                </span>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="dropdown dropdown-left">
                            <div tabindex="0" role="button" class="btn btn-sm btn-ghost">
                                <EllipsisHorizontalIcon class="w-5 h-5"/>
                            </div>
                            <ul tabindex="0"
                                class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                <li>
                                    <a @click="openEditModal(user)">
                                        <PencilIcon class="w-4 h-4"/>
                                        Upraviť
                                    </a>
                                </li>
                                <li>
                                    <a @click="openPasswordResetModal(user)">
                                        <KeyIcon class="w-4 h-4"/>
                                        Zmeniť heslo
                                    </a>
                                </li>
                                <li v-if="user.id !== authStore.user?.id">
                                    <a @click="confirmDeleteUser(user)" class="text-error">
                                        <TrashIcon class="w-4 h-4"/>
                                        Vymazať
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-8">
            <div class="join">
                <button
                    class="join-item btn"
                    :class="{ 'btn-disabled': currentPage === 1 }"
                    @click="currentPage--"
                >
                    «
                </button>
                <button class="join-item btn">Stránka {{ currentPage }}</button>
                <button
                    class="join-item btn"
                    :class="{ 'btn-disabled': users.length < perPage }"
                    @click="currentPage++"
                >
                    »
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredUsers.length === 0" class="text-center py-12">
            <div class="flex justify-center mb-4">
                <UserGroupIcon class="w-16 h-16 text-gray-300"/>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Žiadni používatelia</h3>
            <p class="text-gray-500 mb-6">
                Neboli nájdení žiadni používatelia.
            </p>
            <button @click="resetFilters" class="btn btn-outline">
                Zrušiť filtre
            </button>
        </div>

        <!-- Create/Edit User Modal -->
        <dialog :class="{'modal-open': showUserModal}" class="modal">
            <div class="modal-box w-11/12 max-w-3xl">
                <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="closeModal"
                >
                    ✕
                </button>
                <h3 class="font-bold text-lg mb-6">
                    {{ isEditing ? 'Upraviť používateľa' : 'Vytvoriť používateľa' }}
                </h3>

                <form @submit.prevent="submitUserForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Meno a priezvisko</span>
                            </label>
                            <input
                                type="text"
                                v-model="userForm.name"
                                placeholder="Zadajte meno a priezvisko"
                                class="input input-bordered w-full"
                                required
                            />
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">E-mailová adresa</span>
                            </label>
                            <input
                                type="email"
                                v-model="userForm.email"
                                placeholder="Zadajte e-mailovú adresu"
                                class="input input-bordered w-full"
                                required
                            />
                        </div>

                        <div v-if="!isEditing" class="form-control">
                            <label class="label">
                                <span class="label-text">Heslo</span>
                            </label>
                            <input
                                type="password"
                                v-model="userForm.password"
                                placeholder="Zadajte heslo"
                                class="input input-bordered w-full"
                                required
                            />
                        </div>

                        <div v-if="!isEditing" class="form-control">
                            <label class="label">
                                <span class="label-text">Potvrďte heslo</span>
                            </label>
                            <input
                                type="password"
                                v-model="userForm.password_confirmation"
                                placeholder="Potvrďte heslo"
                                class="input input-bordered w-full"
                                required
                            />
                        </div>

                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text">Role</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="flex items-center"
                                >
                                    <input
                                        type="radio"
                                        :id="`role-${role.id}`"
                                        :value="role.id"
                                        v-model="userForm.roles"
                                        class="radio radio-primary mr-2"
                                        :checked="userForm.roles.includes(role.id)"
                                    />
                                    <label :for="`role-${role.id}`" class="cursor-pointer">
                                        <span class="font-medium">{{ role.display_name }}</span>
                                        <p class="text-sm text-gray-500">{{ role.description }}</p>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text mb-2">Správa</span>
                            </label>
                            <div class="grid grid-cols-1 gap-3">
                                <div
                                    class="flex items-start"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="userForm.is_blocked"
                                        class="checkbox mr-2"
                                        :checked="userForm.is_blocked"
                                    />
                                    <label class="cursor-pointer">
                                        <span class="font-medium">Používateľ je blokovaný</span>
                                        <p class="text-sm text-gray-500">Blokovaní používatelia sa nemôžu prihlásiť.</p>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-action">
                        <button
                            type="button"
                            class="btn btn-ghost"
                            @click="closeModal"
                        >
                            Zrušiť
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="isSubmitting"
                        >
                            <span v-if="isSubmitting" class="loading loading-spinner"></span>
                            {{ isEditing ? 'Uložiť' : 'Vytvoriť' }}
                        </button>
                    </div>
                </form>
            </div>
        </dialog>

        <!-- Password Reset Modal -->
        <dialog :class="{'modal-open': showPasswordResetModal}" class="modal">
            <div class="modal-box w-11/12 max-w-md">
                <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="showPasswordResetModal = false"
                >
                    ✕
                </button>
                <h3 class="font-bold text-lg mb-4">
                    Zmena hesla pre {{ selectedUser?.name }}
                </h3>

                <form @submit.prevent="resetPassword">
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">Nové heslo</span>
                        </label>
                        <input
                            type="password"
                            v-model="passwordForm.password"
                            placeholder="Zadajte nové heslo"
                            class="input input-bordered w-full"
                            required
                        />
                    </div>

                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">Potvrďte nové heslo</span>
                        </label>
                        <input
                            type="password"
                            v-model="passwordForm.password_confirmation"
                            placeholder="Potvrďte nové heslo"
                            class="input input-bordered w-full"
                            required
                        />
                    </div>

                    <div class="modal-action">
                        <button
                            type="button"
                            class="btn btn-ghost"
                            @click="showPasswordResetModal = false"
                        >
                            Zrušiť
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="isSubmittingPassword"
                        >
                            <span v-if="isSubmittingPassword" class="loading loading-spinner"></span>
                            Uložiť heslo
                        </button>
                    </div>
                </form>
            </div>
        </dialog>

        <!-- Delete Confirmation Modal -->
        <dialog :class="{'modal-open': showDeleteModal}" class="modal">
            <div class="modal-box">
                <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="showDeleteModal = false"
                >
                    ✕
                </button>
                <h3 class="font-bold text-lg mb-2">Potvrdiť vymazanie</h3>
                <p class="py-4">
                    Naozaj vymazať používateľa <strong>{{ selectedUser?.name }}</strong>?
                    Táto akcia je nevratná.
                </p>
                <div class="modal-action">
                    <button
                        class="btn btn-ghost"
                        @click="showDeleteModal = false"
                    >
                        Zrušiť
                    </button>
                    <button
                        class="btn btn-error"
                        @click="deleteUser"
                        :disabled="isDeleting"
                    >
                        <span v-if="isDeleting" class="loading loading-spinner"></span>
                        Áno, vymazať používateľa
                    </button>
                </div>
            </div>
        </dialog>
    </div>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import {useAuthStore} from '@/stores/auth';
import {
    PlusCircleIcon,
    PencilIcon,
    TrashIcon,
    EllipsisHorizontalIcon,
    MagnifyingGlassIcon,
    UserGroupIcon,
    KeyIcon
} from '@heroicons/vue/24/outline';
import 'notyf/notyf.min.css';
import {showErrorToast, showSuccessToast} from "../../constants/toast.js";
import http from "../../http.js";

const authStore = useAuthStore();

// User data
const users = ref([]);
const roles = ref([]);
const currentPage = ref(1);
const perPage = 10;

// Search and filters
const searchQuery = ref('');
const roleFilter = ref('');

// Modals state
const showUserModal = ref(false);
const showDeleteModal = ref(false);
const showPasswordResetModal = ref(false);

// Form states
const isEditing = ref(false);
const isSubmitting = ref(false);
const isDeleting = ref(false);
const isSubmittingPassword = ref(false);

// Selected user for actions
const selectedUser = ref(null);

// Forms
const userForm = ref({
    id: null,
    name: '',
    email: '',
    is_blocked: false,
    password: '',
    password_confirmation: '',
    roles: []
});

const passwordForm = ref({
    password: '',
    password_confirmation: ''
});

// Fetch users and roles
const fetchUsers = async () => {
    try {
        const response = await http.request('/api/admin/user-management');

        if (!response.ok) throw new Error('Načítanie používateľov zlyhalo');

        const data = await response.json();
        users.value = data.users;
        roles.value = data.roles;
    } catch (error) {
        showErrorToast('Chyba pri načítaní používateľov.');
        console.error('Error fetching users:', error);
    }
};

// Filtered users based on search and filters
const filteredUsers = computed(() => {
    let result = users.value;

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query)
        );
    }

    // Apply role filter
    if (roleFilter.value) {
        result = result.filter(user =>
            user.roles.some(role => role.name === roleFilter.value)
        );
    }

    // Apply pagination
    const startIndex = (currentPage.value - 1) * perPage;
    return result.slice(startIndex, startIndex + perPage);
});

// Initialize user initials
const userInitials = (name) => {
    if (!name) return '?';
    const names = name.split(' ');
    return names.map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

// Role badge styling
const roleBadgeClass = (roleName) => {
    switch (roleName) {
        case 'admin':
            return 'badge-primary';
        case 'editor':
            return 'badge-secondary';
        default:
            return 'badge-neutral';
    }
};

// Modal handlers
const openCreateModal = () => {
    userForm.value = {
        id: null,
        name: '',
        email: '',
        is_blocked: false,
        password: '',
        password_confirmation: '',
        roles: []
    };
    isEditing.value = false;
    showUserModal.value = true;
};

const openEditModal = (user) => {
    selectedUser.value = user;
    userForm.value = {
        id: user.id,
        name: user.name,
        email: user.email,
        is_blocked: user.is_blocked,
        password: '',
        password_confirmation: '',
        roles: user.roles.map(role => role.id)
    };
    isEditing.value = true;
    showUserModal.value = true;
};

const openPasswordResetModal = (user) => {
    selectedUser.value = user;
    passwordForm.value = {
        password: '',
        password_confirmation: ''
    };
    showPasswordResetModal.value = true;
};

const confirmDeleteUser = (user) => {
    selectedUser.value = user;
    showDeleteModal.value = true;
};

const closeModal = () => {
    showUserModal.value = false;
    showPasswordResetModal.value = false;
};

// Form submissions
const submitUserForm = async () => {
    isSubmitting.value = true;

    try {
        const url = isEditing.value
            ? `/api/admin/user-management/update/${userForm.value.id}`
            : '/api/admin/user-management/create';

        const method = isEditing.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authStore.token}`
            },
            body: JSON.stringify(userForm.value)
        });

        if (!response.ok) throw new Error('Uloženie používateľa zlyhalo');

        await fetchUsers();
        showUserModal.value = false;
    } catch (error) {
        console.error('Error saving user:', error);
        showErrorToast('Chyba pri ukladaní používateľa');
    } finally {
        isSubmitting.value = false;
    }
};

const resetPassword = async () => {
    isSubmittingPassword.value = true;

    try {
        const response = await fetch(`/api/admin/user-management/reset/${selectedUser.value.id}/password`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authStore.token}`
            },
            body: JSON.stringify(passwordForm.value)
        });

        if (!response.ok) throw new Error('Reset hesla zlyhal');

        showSuccessToast('Heslo bolo úspešne zmenené');
        showPasswordResetModal.value = false;
    } catch (error) {
        console.error('Error resetting password:', error);
        showErrorToast('Chyba pri zmene hesla');
    } finally {
        isSubmittingPassword.value = false;
    }
};

const deleteUser = async () => {
    isDeleting.value = true;

    try {
        const response = await fetch(`/api/admin/user-management/delete/${selectedUser.value.id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authStore.token}`
            }
        });

        if (!response.ok) throw new Error('Vymazanie používateľa zlyhalo');

        await fetchUsers();
        showDeleteModal.value = false;
    } catch (error) {
        console.error('Error deleting user:', error);
        showErrorToast('Chyba pri mazaní používateľa');
    } finally {
        isDeleting.value = false;
    }
};

// Reset filters
const resetFilters = () => {
    searchQuery.value = '';
    roleFilter.value = '';
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('sk-SK', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Initialize component
onMounted(() => {
    if (authStore.hasPermission('manage_users')) {
        fetchUsers();
    }
});
</script>
