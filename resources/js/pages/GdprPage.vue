<template>
    <div class="max-w-2xl mx-auto px-4 py-16 text-center">
        <h1 class="text-3xl font-bold text-primary mb-4">Ochrana osobných údajov</h1>
        <p class="text-base-content/80 mb-8">
            Informácie o spracúvaní osobných údajov Mestskou časťou Bratislava-Karlova Ves nájdete v oficiálnom
            dokumente na stránke ppprotect.sk.
        </p>
        <a href="https://ppprotect.sk/dokument/mestska-cast-bratislava-karlova-ves/" target="_blank" rel="noopener"
           class="btn btn-primary btn-lg">
            Zobraziť dokument o ochrane osobných údajov
        </a>
    </div>
</template>

<script setup>
import {onMounted} from 'vue';

onMounted(() => {
    document.title = 'Ochrana osobných údajov – Karlova Ves';
});
</script>
