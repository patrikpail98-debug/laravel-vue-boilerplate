<template>
    <header>
        <div class="navbar bg-primary text-primary-content shadow-md px-4 md:px-8">
            <div class="navbar-start">
                <router-link to="/" class="btn btn-ghost text-lg normal-case hover:bg-primary-focus px-2">
                    Karlova Ves <span class="hidden sm:inline">&ndash; Šport</span>
                </router-link>
            </div>

            <nav aria-label="Hlavná navigácia" class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1 gap-1">
                    <li v-for="item in navItems" :key="item.path">
                        <router-link :to="item.path" class="rounded-field">{{ item.label }}</router-link>
                    </li>
                </ul>
            </nav>

            <div class="navbar-end gap-2">
                <template v-if="authStore.isAuthenticated">
                    <router-link v-if="authStore.isAdmin" to="/admin" class="btn btn-secondary btn-sm hidden sm:inline-flex">
                        Administrácia
                    </router-link>
                    <button type="button" class="btn btn-ghost btn-sm" @click="logout">Odhlásiť sa</button>
                </template>
                <router-link v-else to="/login" class="btn btn-secondary btn-sm">Prihlásenie</router-link>

                <button type="button" class="btn btn-ghost btn-square lg:hidden" @click="mobileOpen = !mobileOpen"
                        :aria-expanded="mobileOpen" aria-controls="mobile-menu" aria-label="Otvoriť menu">
                    <Bars3Icon v-if="!mobileOpen" class="w-6 h-6" aria-hidden="true"/>
                    <XMarkIcon v-else class="w-6 h-6" aria-hidden="true"/>
                </button>
            </div>
        </div>

        <nav v-if="mobileOpen" id="mobile-menu" aria-label="Mobilné menu" class="lg:hidden bg-primary text-primary-content px-4 pb-4">
            <ul class="menu">
                <li v-for="item in navItems" :key="item.path">
                    <router-link :to="item.path" @click="mobileOpen = false">{{ item.label }}</router-link>
                </li>
                <li v-if="authStore.isAuthenticated && authStore.isAdmin">
                    <router-link to="/admin" @click="mobileOpen = false">Administrácia</router-link>
                </li>
            </ul>
        </nav>
    </header>
</template>

<script setup>
import {ref} from 'vue';
import {useRouter} from 'vue-router';
import {Bars3Icon, XMarkIcon} from '@heroicons/vue/24/outline';
import {navItems} from '@/constants/navigation.js';
import {useAuthStore} from '@/stores/auth.js';

const authStore = useAuthStore();
const router = useRouter();
const mobileOpen = ref(false);

const logout = async () => {
    await authStore.logout();
    mobileOpen.value = false;
    router.push('/');
};
</script>
