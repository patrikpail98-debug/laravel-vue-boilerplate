<template>
    <GlobalLoadingCurtain ref="globalLoader" />
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:btn focus:btn-primary">
        Preskočiť na obsah
    </a>
    <div class="min-h-screen flex flex-col">
        <Header />
        <main id="main-content" class="flex-grow">
            <router-view />
        </main>
        <Footer />
    </div>
    <CookieConsentBanner />
</template>

<script setup>
import Header from './components/layout/Header.vue';
import Footer from './components/layout/Footer.vue';
import {getCurrentInstance, onMounted, ref} from "vue";
import GlobalLoadingCurtain from "@/components/ui/GlobalLoadingCurtain.vue";
import CookieConsentBanner from "@/components/ui/CookieConsentBanner.vue";
import {initAnalyticsFromStoredConsent} from "@/utils/analytics.js";

const globalLoader = ref(null);

// Provide loader methods to all components
const showLoader = () => globalLoader.value?.show();
const hideLoader = () => globalLoader.value?.hide();

// Make loader accessible via app instance
const app = getCurrentInstance();
app.appContext.config.globalProperties.$loader = { show: showLoader, hide: hideLoader };

onMounted(() => {
    initAnalyticsFromStoredConsent();
});
</script>
