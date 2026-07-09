<template>
    <div class="max-w-3xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold text-primary mb-6">Podmienky používania rezervačného systému</h1>

        <div class="space-y-8 text-base-content/90 leading-relaxed">
            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">1. Úvodné ustanovenia</h2>
                <p>
                    Tieto podmienky upravujú používanie online rezervačného systému športovísk Mestskej časti
                    Bratislava-Karlova Ves. Vytvorením rezervácie potvrdzujete, že ste sa s týmito podmienkami
                    oboznámili a súhlasíte s nimi.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">2. Postup rezervácie</h2>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Vyberiete si športovisko a voľný 30-minútový termín v kalendári.</li>
                    <li>Vyplníte kontaktné údaje (meno, e-mail, telefón). Registrácia nie je povinná.</li>
                    <li>Na zadaný e-mail Vám príde potvrdzujúci odkaz, ktorý je potrebné potvrdiť do
                        15 minút — inak sa termín automaticky uvoľní pre iných záujemcov.
                    </li>
                    <li>Po potvrdení e-mailu Vám zašleme platobné údaje (IBAN, variabilný symbol, suma).</li>
                    <li>Po prijatí a spárovaní platby administrátor rezerváciu schváli a zašle finálne potvrdenie.
                        Ak platba nepríde alebo údaje nesúhlasia, rezervácia môže byť zamietnutá.
                    </li>
                </ol>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">3. Platba</h2>
                <p>
                    Cena rezervácie sa vypočíta na základe zvoleného počtu 30-minútových blokov a aktuálneho
                    cenníka daného športoviska. Platba prebieha bankovým prevodom na účet mestskej časti pod
                    variabilným symbolom uvedeným v platobnom doklade.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">4. Zrušenie a zmena rezervácie</h2>
                <p>
                    Zrušenie alebo zmenu schválenej rezervácie je potrebné konzultovať s administrátorom
                    prostredníctvom kontaktných údajov uvedených na stránke
                    <router-link to="/kontakt" class="link link-primary">Kontakt</router-link>. Mestská časť si
                    vyhradzuje právo rezerváciu zrušiť z prevádzkových dôvodov (napr. údržba športoviska).
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">5. Zodpovednosť používateľa</h2>
                <p>
                    Používateľ zodpovedá za správnosť a aktuálnosť uvedených kontaktných údajov. Za škody
                    spôsobené nesprávne uvedenými údajmi nenesie prevádzkovateľ zodpovednosť.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">6. Ochrana osobných údajov</h2>
                <p>
                    Spracúvanie osobných údajov v súvislosti s rezerváciou upravuje samostatný dokument
                    <router-link to="/ochrana-osobnych-udajov" class="link link-primary">Ochrana osobných údajov</router-link>.
                </p>
            </section>
        </div>
    </div>
</template>

<script setup>
import {onMounted} from 'vue';

onMounted(() => {
    document.title = 'Podmienky používania – Karlova Ves';
});
</script>
