<template>
    <div class="max-w-3xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold text-primary mb-6">Ochrana osobných údajov</h1>

        <div class="space-y-8 text-base-content/90 leading-relaxed">
            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">1. Prevádzkovateľ</h2>
                <p>
                    Prevádzkovateľom rezervačného systému športovísk je Mestská časť Bratislava-Karlova Ves,
                    Nám. sv. Františka 8, 842 17 Bratislava, IČO: [doplniť] (ďalej len "prevádzkovateľ").
                    V otázkach ochrany osobných údajov nás môžete kontaktovať na e-mailovej adrese uvedenej na
                    <router-link to="/kontakt" class="link link-primary">stránke Kontakt</router-link>.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">2. Aké osobné údaje spracúvame</h2>
                <p class="mb-2">Pri vytvorení rezervácie športoviska spracúvame:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>meno a priezvisko,</li>
                    <li>e-mailovú adresu (na overenie rezervácie a zasielanie platobných a informačných e-mailov),</li>
                    <li>telefónne číslo,</li>
                    <li>údaje o rezervácii (zvolené športovisko, termín, výška platby, variabilný symbol).</li>
                </ul>
                <p class="mt-2">
                    Ak si vytvoríte používateľský účet, spracúvame aj meno a e-mailovú adresu použité pri registrácii.
                    Registrácia a vytvorenie účtu nie je pre vytvorenie rezervácie potrebné.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">3. Účel a právny základ spracúvania</h2>
                <p>
                    Osobné údaje spracúvame za účelom vybavenia rezervácie športoviska, overenia totožnosti
                    žiadateľa prostredníctvom e-mailu, spárovania platby a komunikácie súvisiacej s rezerváciou
                    (potvrdenie, zamietnutie, zrušenie). Právnym základom je plnenie zmluvy/vybavenie požiadavky
                    dotknutej osoby pred uzatvorením zmluvy (čl. 6 ods. 1 písm. b) GDPR), v prípade analytických
                    cookies ide o súhlas dotknutej osoby (čl. 6 ods. 1 písm. a) GDPR).
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">4. Doba uchovávania</h2>
                <p>
                    Údaje o rezerváciách uchovávame po dobu nevyhnutnú na vybavenie rezervácie a účtovné/archivačné
                    účely v súlade s platnou legislatívou. Údaje k používateľskému účtu uchovávame po dobu jeho
                    existencie.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">5. Práva dotknutej osoby</h2>
                <p class="mb-2">V súlade s GDPR máte právo na:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>prístup k svojim osobným údajom,</li>
                    <li>opravu nesprávnych údajov,</li>
                    <li>výmaz údajov (v rozsahu, ktorý nie je v rozpore s archivačnými povinnosťami),</li>
                    <li>obmedzenie spracúvania,</li>
                    <li>námietku proti spracúvaniu,</li>
                    <li>podanie sťažnosti Úradu na ochranu osobných údajov SR.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-primary mb-2">6. Cookies</h2>
                <p class="mb-2">Na stránke používame dva druhy cookies:</p>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Typ</th>
                            <th>Účel</th>
                            <th>Súhlas</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Nevyhnutné</td>
                            <td>Prihlásenie a základné fungovanie rezervačného systému.</td>
                            <td>Nevyžaduje sa</td>
                        </tr>
                        <tr>
                            <td>Analytické (Google Analytics)</td>
                            <td>Anonymizovaná štatistika návštevnosti stránky.</td>
                            <td>Vyžaduje sa</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2">
                    Svoju voľbu ohľadom analytických cookies môžete kedykoľvek zmeniť vymazaním údajov stránky
                    vo Vašom prehliadači, čím sa lišta so súhlasom zobrazí znova.
                </p>
            </section>
        </div>
    </div>
</template>

<script setup>
import {onMounted} from 'vue';

onMounted(() => {
    document.title = 'Ochrana osobných údajov – Karlova Ves';
});
</script>
