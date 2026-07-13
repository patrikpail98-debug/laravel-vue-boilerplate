<template>
    <!-- Deliberately no <transition>/fade here: show()/hide() can fire back-to-
         back within the same tick on a fast redirect, and a CSS leave
         transition that gets interrupted before its transitionend fires
         leaves Vue waiting forever to unmount this - a real, reproduced bug
         (confirmed via the component's actual isLoading state going false
         while the element stayed stuck in the DOM with mismatched
         fade-enter-from/fade-leave-from classes). A plain v-if has no
         intermediate state to get stuck in. -->
    <div
        v-if="isLoading"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-base-100"
    >
        <span class="loading loading-spinner loading-lg text-primary"></span>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const isLoading = ref(false);

// Safety net: even if some future navigation path fails to call hide() (see
// app.js's router guard for the primary fix), the curtain never blocks the
// app for more than a few seconds.
const SAFETY_TIMEOUT_MS = 8000;
let safetyTimer = null;

const show = () => {
    isLoading.value = true;
    clearTimeout(safetyTimer);
    safetyTimer = setTimeout(() => {
        isLoading.value = false;
    }, SAFETY_TIMEOUT_MS);
};

const hide = () => {
    clearTimeout(safetyTimer);
    isLoading.value = false;
};

// Expose methods for other components to use
defineExpose({ show, hide });
</script>
