<template>
    <footer class="bg-base-300 text-base-content mt-auto">
        <div class="max-w-6xl mx-auto px-4 py-10 grid gap-8 sm:grid-cols-3">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <img src="/images/erb-karlova-ves.png" alt="Erb Mestskej časti Bratislava-Karlova Ves" class="h-10 w-auto"/>
                    <h2 class="font-bold text-lg">{{ settings['org.name'] || 'Mestská časť Bratislava-Karlova Ves' }}</h2>
                </div>
                <address class="not-italic text-sm space-y-1">
                    <p v-if="settings['contact.address']">{{ settings['contact.address'] }}</p>
                    <p v-if="settings['contact.phone']">
                        <a :href="`tel:${settings['contact.phone']}`" class="link link-hover">{{ settings['contact.phone'] }}</a>
                    </p>
                    <p v-if="settings['contact.email']">
                        <a :href="`mailto:${settings['contact.email']}`" class="link link-hover">{{ settings['contact.email'] }}</a>
                    </p>
                    <p v-if="settings['contact.hours']">{{ settings['contact.hours'] }}</p>
                </address>
            </div>

            <nav aria-label="Odkazy">
                <h2 class="font-bold text-lg mb-2">Odkazy</h2>
                <ul class="text-sm space-y-1">
                    <li><router-link to="/rezervacia" class="link link-hover">Rezervácia športovísk</router-link></li>
                    <li><router-link to="/mapa" class="link link-hover">Mapa športovísk</router-link></li>
                    <li><router-link to="/kontakt" class="link link-hover">Kontakt</router-link></li>
                    <li><a href="https://www.karlovaves.sk" target="_blank" rel="noopener" class="link link-hover">Hlavná stránka karlovaves.sk</a></li>
                </ul>
            </nav>

            <nav aria-label="Právne informácie">
                <h2 class="font-bold text-lg mb-2">Právne informácie</h2>
                <ul class="text-sm space-y-1">
                    <li><router-link to="/ochrana-osobnych-udajov" class="link link-hover">Ochrana osobných údajov</router-link></li>
                    <li><router-link to="/podmienky-pouzivania" class="link link-hover">Podmienky používania</router-link></li>
                    <li><router-link to="/login" class="link link-hover">Prihlásenie do administrácie</router-link></li>
                </ul>
            </nav>
        </div>

        <div class="border-t border-base-content/10 py-4 text-center text-xs text-base-content/60">
            &copy; {{ new Date().getFullYear() }} {{ settings['org.name'] || 'Mestská časť Bratislava-Karlova Ves' }}. Všetky práva vyhradené.
        </div>
    </footer>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import http from '@/http.js';

const settings = ref({});

onMounted(async () => {
    try {
        const response = await http.request('/api/public-settings');
        if (response.ok) {
            settings.value = await response.json();
        }
    } catch {
        // Footer degrades gracefully without contact details if this fails.
    }
});
</script>
