<!-- resources/js/components/ui/SlotPicker.vue -->
<template>
    <div>
        <div class="flex gap-2 overflow-x-auto pb-2 mb-4 date-range-picker">
            <button v-for="day in days" :key="day.iso" type="button"
                    class="flex flex-col items-center justify-center rounded-box px-3 py-2 min-w-16 border"
                    :class="day.iso === selectedDate ? 'bg-primary text-primary-content border-primary' : 'border-base-300 hover:bg-base-200'"
                    @click="selectDate(day.iso)">
                <span class="text-xs uppercase">{{ day.weekday }}</span>
                <span class="calendar-day font-semibold" :class="{today: day.isToday}">{{ day.dayNumber }}</span>
            </button>
        </div>

        <div v-if="loading" class="flex justify-center py-8">
            <span class="loading loading-spinner loading-lg"></span>
        </div>

        <template v-else>
            <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                <button v-for="slot in slots" :key="slot.iso" type="button"
                        class="btn btn-sm"
                        :class="slotClass(slot)"
                        :disabled="slot.booked || slot.past"
                        @click="toggleSlot(slot)">
                    {{ slot.label }}
                </button>
            </div>

            <p v-if="selection.length" class="mt-4 text-sm">
                Vybraný termín: <strong>{{ selectionSummary }}</strong>
                &mdash; cena: <strong>{{ totalPrice.toFixed(2) }} &euro;</strong>
            </p>
        </template>
    </div>
</template>

<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import http from '@/http.js';
import {showErrorToast} from '../../constants/toast.js';

const props = defineProps({
    playgroundId: {type: [Number, String], required: true},
    maxHorizonDays: {type: Number, default: 14},
    maxDurationMinutes: {type: Number, default: 120},
    pricePer30Min: {type: Number, default: 0},
    slotMinutes: {type: Number, default: 30},
});

const emit = defineEmits(['update:selection', 'loaded']);

const OPEN_HOUR = 7;
const CLOSE_HOUR = 22;

const days = ref([]);
const selectedDate = ref(null);
const bookedIso = ref([]);
const loading = ref(false);
const selection = ref([]); // array of slot ISO strings, contiguous, ascending

const buildDays = () => {
    const list = [];
    const weekdays = ['Ne', 'Po', 'Ut', 'St', 'Št', 'Pi', 'So'];
    for (let i = 0; i <= props.maxHorizonDays; i++) {
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        d.setDate(d.getDate() + i);
        list.push({
            iso: toDateKey(d),
            weekday: weekdays[d.getDay()],
            dayNumber: d.getDate(),
            isToday: i === 0,
        });
    }
    days.value = list;
};

const toDateKey = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const slots = computed(() => {
    if (!selectedDate.value) return [];

    const [y, m, d] = selectedDate.value.split('-').map(Number);
    const list = [];
    const now = new Date();

    for (let minutes = OPEN_HOUR * 60; minutes < CLOSE_HOUR * 60; minutes += props.slotMinutes) {
        const start = new Date(y, m - 1, d, 0, minutes, 0, 0);
        const hh = String(start.getHours()).padStart(2, '0');
        const mm = String(start.getMinutes()).padStart(2, '0');
        list.push({
            iso: start.toISOString(),
            label: `${hh}:${mm}`,
            booked: bookedIso.value.includes(start.toISOString()),
            past: start < now,
        });
    }

    return list;
});

const slotClass = (slot) => {
    if (selection.value.includes(slot.iso)) {
        return 'btn-primary';
    }
    if (slot.booked || slot.past) {
        return 'btn-disabled opacity-40';
    }
    return 'btn-outline';
};

const selectDate = async (iso) => {
    selectedDate.value = iso;
    selection.value = [];
    emitSelection();
    await fetchAvailability();
};

const fetchAvailability = async () => {
    loading.value = true;
    try {
        const response = await http.request(`/api/playgrounds/${props.playgroundId}/availability?date=${selectedDate.value}`);
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message ?? 'Nepodarilo sa načítať dostupné termíny.');
        }
        bookedIso.value = data.booked_slots ?? [];
        emit('loaded', data);
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať dostupné termíny.');
    } finally {
        loading.value = false;
    }
};

const maxSlotsInSelection = computed(() => props.maxDurationMinutes / props.slotMinutes);

const toggleSlot = (slot) => {
    if (slot.booked || slot.past) return;

    const list = slots.value;
    const clickedIndex = list.findIndex(s => s.iso === slot.iso);

    if (selection.value.length === 0) {
        selection.value = [slot.iso];
        emitSelection();
        return;
    }

    const lastIso = selection.value[selection.value.length - 1];

    // Clicking the last selected slot again shrinks the selection by one.
    if (slot.iso === lastIso) {
        selection.value = selection.value.slice(0, -1);
        emitSelection();
        return;
    }

    const lastIndex = list.findIndex(s => s.iso === lastIso);

    const isImmediatelyAfter = clickedIndex === lastIndex + 1;
    const wouldExceedMax = selection.value.length + 1 > maxSlotsInSelection.value;
    const isFree = !list[clickedIndex]?.booked && !list[clickedIndex]?.past;

    if (isImmediatelyAfter && !wouldExceedMax && isFree) {
        selection.value = [...selection.value, slot.iso];
    } else {
        selection.value = [slot.iso];
    }

    emitSelection();
};

const selectionSummary = computed(() => {
    if (!selection.value.length) return '';
    const start = new Date(selection.value[0]);
    const end = new Date(selection.value[selection.value.length - 1]);
    end.setMinutes(end.getMinutes() + props.slotMinutes);
    const fmt = (d) => `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    return `${fmt(start)} - ${fmt(end)}`;
});

const totalPrice = computed(() => selection.value.length * props.pricePer30Min);

const emitSelection = () => {
    if (!selection.value.length) {
        emit('update:selection', null);
        return;
    }

    const start = new Date(selection.value[0]);
    const end = new Date(selection.value[selection.value.length - 1]);
    end.setMinutes(end.getMinutes() + props.slotMinutes);

    emit('update:selection', {
        start_time: toLocalIso(start),
        end_time: toLocalIso(end),
        total_price: totalPrice.value,
    });
};

const toLocalIso = (date) => {
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:00`;
};

watch(() => props.playgroundId, () => {
    buildDays();
    selectDate(days.value[0].iso);
});

onMounted(() => {
    buildDays();
    selectDate(days.value[0].iso);
});
</script>
