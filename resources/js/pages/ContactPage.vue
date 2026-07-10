<template>
    <div class="max-w-4xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold text-primary mb-6">Kontakt</h1>

        <div class="grid gap-8 md:grid-cols-2">
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title">{{ settings['org.name'] || 'Mestská časť Bratislava-Karlova Ves' }}</h2>
                    <address class="not-italic space-y-2 mt-2">
                        <p v-if="settings['contact.person']">
                            <span class="font-medium">Kontaktná osoba:</span> {{ settings['contact.person'] }}
                        </p>
                        <p v-if="settings['contact.address']">
                            <span class="font-medium">Adresa:</span> {{ settings['contact.address'] }}
                        </p>
                        <p v-if="settings['contact.phone']">
                            <span class="font-medium">Telefón:</span>
                            <a :href="`tel:${settings['contact.phone']}`" class="link link-primary">{{ settings['contact.phone'] }}</a>
                        </p>
                        <p v-if="settings['contact.email']">
                            <span class="font-medium">E-mail:</span>
                            <a :href="`mailto:${settings['contact.email']}`" class="link link-primary">{{ settings['contact.email'] }}</a>
                        </p>
                        <p v-if="settings['contact.hours']">
                            <span class="font-medium">Úradné hodiny:</span> {{ settings['contact.hours'] }}
                        </p>
                    </address>
                    <p v-if="!hasAnyContactInfo" class="text-base-content/60">
                        Kontaktné údaje budú čoskoro doplnené.
                    </p>
                    <p class="mt-4 text-sm text-base-content/70">
                        Komunikácia prebieha výhradne prostredníctvom e-mailu.
                    </p>
                </div>
            </div>

            <FacilityMap v-if="officeMarker" :markers="[officeMarker]" :center="{lat: officeMarker.latitude, lng: officeMarker.longitude}"
                         :zoom="16" height="100%" :hide-list-on-desktop="false"/>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import http from '@/http.js';
import FacilityMap from '@/components/ui/FacilityMap.vue';

const settings = ref({});

const hasAnyContactInfo = computed(() =>
    ['contact.address', 'contact.phone', 'contact.email', 'contact.person', 'contact.hours'].some(key => settings.value[key])
);

const officeMarker = computed(() => {
    const lat = settings.value['contact.latitude'];
    const lng = settings.value['contact.longitude'];
    if (!lat || !lng) return null;

    return {
        id: 'office',
        name: settings.value['org.name'] || 'Miestny úrad',
        subtitle: settings.value['contact.address'] ?? '',
        latitude: Number(lat),
        longitude: Number(lng),
    };
});

onMounted(async () => {
    document.title = 'Kontakt – Karlova Ves';

    try {
        const response = await http.request('/api/public-settings');
        if (response.ok) {
            settings.value = await response.json();
        }
    } catch {
        settings.value = {};
    }
});
</script>
