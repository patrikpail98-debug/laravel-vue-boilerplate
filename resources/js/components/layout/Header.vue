<template>
    <header :class="isHeroOverlay ? 'absolute top-0 inset-x-0 z-20' : 'relative'">
        <div class="navbar px-4 md:px-8 text-primary-content"
             :class="isHeroOverlay ? 'bg-gradient-to-b from-black/50 to-transparent' : 'bg-primary shadow-md'">
            <div class="navbar-start">
                <router-link to="/" class="btn btn-ghost text-lg normal-case hover:bg-white/10 px-2 gap-2">
                    <img src="/images/erb-karlova-ves.png" alt="Erb Mestskej časti Bratislava-Karlova Ves" class="h-9 w-auto"/>
                    <span class="!text-primary-content">Karlova Ves <span class="hidden sm:inline">&ndash; Šport</span></span>
                </router-link>
            </div>

            <nav aria-label="Hlavná navigácia" class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1 gap-1">
                    <li v-for="item in navItems" :key="item.path">
                        <router-link :to="item.path"
                                     class="!text-primary-content font-medium rounded-field hover:!bg-white/15 [&.router-link-exact-active]:!bg-secondary [&.router-link-exact-active]:!text-secondary-content">
                            {{ item.label }}
                        </router-link>
                    </li>
                </ul>
            </nav>

            <div class="navbar-end gap-2">
                <template v-if="authStore.isAuthenticated">
                    <router-link v-if="authStore.hasPermission('view_admin')" to="/admin" class="btn btn-secondary btn-sm hidden sm:inline-flex">
                        Administrácia
                    </router-link>
                    <router-link v-else to="/user" class="btn btn-secondary btn-sm hidden sm:inline-flex">
                        Môj profil
                    </router-link>
                    <button type="button" class="btn btn-ghost btn-sm !text-primary-content hover:!bg-white/15" @click="logout">Odhlásiť sa</button>
                </template>
                <router-link v-else to="/login" class="btn btn-secondary btn-sm">Prihlásenie</router-link>

                <button type="button" class="btn btn-ghost btn-square !text-primary-content hover:!bg-white/15 lg:hidden" @click="mobileOpen = !mobileOpen"
                        :aria-expanded="mobileOpen" aria-controls="mobile-menu" aria-label="Otvoriť menu">
                    <Bars3Icon v-if="!mobileOpen" class="w-6 h-6" aria-hidden="true"/>
                    <XMarkIcon v-else class="w-6 h-6" aria-hidden="true"/>
                </button>
            </div>
        </div>

        <nav v-if="mobileOpen" id="mobile-menu" aria-label="Mobilné menu" class="lg:hidden bg-primary text-primary-content px-4 pb-4">
            <ul class="menu">
                <li v-for="item in navItems" :key="item.path">
                    <router-link :to="item.path" class="!text-primary-content hover:!bg-white/15 [&.router-link-exact-active]:!bg-secondary [&.router-link-exact-active]:!text-secondary-content"
                                 @click="mobileOpen = false">{{ item.label }}</router-link>
                </li>
                <li v-if="authStore.isAuthenticated">
                    <router-link v-if="authStore.hasPermission('view_admin')" to="/admin" class="!text-primary-content hover:!bg-white/15" @click="mobileOpen = false">Administrácia</router-link>
                    <router-link v-else to="/user" class="!text-primary-content hover:!bg-white/15" @click="mobileOpen = false">Môj profil</router-link>
                </li>
            </ul>
        </nav>
    </header>
</template>

<script setup>
import {computed, ref} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import {Bars3Icon, XMarkIcon} from '@heroicons/vue/24/outline';
import {navItems} from '@/constants/navigation.js';
import {useAuthStore} from '@/stores/auth.js';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const mobileOpen = ref(false);

// On the home page the header floats transparently over the hero photo so
// the two read as one seamless section; everywhere else it stays a normal
// solid navbar. The mobile dropdown below it always keeps its own solid
// background, so it stays readable either way.
const isHeroOverlay = computed(() => route.path === '/');

const logout = async () => {
    await authStore.logout();
    mobileOpen.value = false;
    router.push('/');
};
</script>
