<!-- resources/js/components/ui/SlotPicker.vue -->
<template>
    <div>
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <button type="button" class="btn btn-sm btn-circle btn-ghost" :disabled="!canGoPrev"
                        aria-label="Predchádzajúci mesiac" @click="goPrevMonth">
                    <ChevronLeftIcon class="w-5 h-5"/>
                </button>
                <span class="font-semibold capitalize">{{ monthLabel }}</span>
                <button type="button" class="btn btn-sm btn-circle btn-ghost" :disabled="!canGoNext"
                        aria-label="Nasledujúci mesiac" @click="goNextMonth">
                    <ChevronRightIcon class="w-5 h-5"/>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-xs uppercase text-base-content/60 mb-1">
                <span v-for="wd in weekdayLabels" :key="wd">{{ wd }}</span>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <button v-for="cell in calendarDays" :key="cell.iso" type="button"
                        class="aspect-square flex items-center justify-center rounded-box border text-sm"
                        :class="dayClass(cell)"
                        :disabled="!isSelectableDay(cell)"
                        :title="cellTitle(cell)"
                        @click="selectDate(cell.iso)">
                    <span :class="{'font-bold underline': cell.isToday}">{{ cell.dayNumber }}</span>
                </button>
            </div>
        </div>

        <div v-if="loading" class="flex justify-center py-8">
            <span class="loading loading-spinner loading-lg"></span>
        </div>

        <template v-else>
            <p v-if="isClosedToday" class="text-center text-base-content/60 py-8">
                V tento deň je ihrisko zatvorené. Vyberte prosím iný dátum.
            </p>

            <template v-else>
                <p v-if="dayOpeningHours" class="text-sm text-base-content/60 mb-2">
                    Otváracie hodiny: {{ dayOpeningHours.opens_at }} &ndash; {{ dayOpeningHours.closes_at }}
                </p>

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
        </template>
    </div>
</template>

<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {ChevronLeftIcon, ChevronRightIcon} from '@heroicons/vue/24/outline';
import http from '@/http.js';
import {showErrorToast} from '../../constants/toast.js';

const props = defineProps({
    playgroundId: {type: [Number, String], required: true},
    maxHorizonDays: {type: Number, default: 60},
    maxDurationMinutes: {type: Number, default: 120},
    pricePer30Min: {type: Number, default: 0},
    slotMinutes: {type: Number, default: 30},
});

const emit = defineEmits(['update:selection', 'loaded']);

// Fallback window used only when a playground has no opening hours configured at all.
const FALLBACK_OPEN_HOUR = 7;
const FALLBACK_CLOSE_HOUR = 22;

const weekdayLabels = ['Po', 'Ut', 'St', 'Št', 'Pi', 'So', 'Ne'];

// Bookable window: today through today + horizonDayCount - 1. Populated once
// in initDays() from fetchDayAvailability(), which reflects the playground's
// real max_horizon_days from the backend.
const horizonStart = ref(null); // Date, today at local midnight
const horizonDayCount = ref(0);

// Month currently shown in the calendar grid - independent from the horizon
// bounds so the user can page between months with the nav arrows.
const viewYear = ref(new Date().getFullYear());
const viewMonth = ref(new Date().getMonth()); // 0-indexed

const selectedDate = ref(null);
const bookedIso = ref([]);
const dayOpeningHours = ref(null); // {is_closed, opens_at: 'HH:MM', closes_at: 'HH:MM'} | null
const dayStatus = ref({}); // { 'YYYY-MM-DD': {closed, fully_booked} }
const loading = ref(false);
const selection = ref([]); // array of slot ISO strings, contiguous, ascending

const isClosedToday = computed(() => Boolean(dayOpeningHours.value?.is_closed));

const isDayUnavailable = (iso) => {
    const status = dayStatus.value[iso];
    return Boolean(status?.closed || status?.fully_booked);
};

const toDateKey = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const isoAtOffset = (offset) => {
    const d = new Date(horizonStart.value);
    d.setDate(d.getDate() + offset);
    return toDateKey(d);
};

const horizonEndIso = computed(() => {
    if (!horizonStart.value || !horizonDayCount.value) return null;
    return isoAtOffset(horizonDayCount.value - 1);
});

const inHorizon = (iso) => {
    if (!horizonStart.value || !horizonDayCount.value) return false;
    return iso >= toDateKey(horizonStart.value) && iso <= horizonEndIso.value;
};

// Flat 6-week (42 day) grid for the visible month, Monday-first. Days
// belonging to the previous/next month are included (dimmed, inert) so the
// grid keeps a consistent shape.
const calendarDays = computed(() => {
    const year = viewYear.value;
    const month = viewMonth.value;
    const firstOfMonth = new Date(year, month, 1);
    const firstWeekday = (firstOfMonth.getDay() + 6) % 7; // Monday = 0
    const gridStart = new Date(year, month, 1 - firstWeekday);
    const todayIso = toDateKey(new Date());

    const cells = [];
    const cursor = new Date(gridStart);
    for (let i = 0; i < 42; i++) {
        cells.push({
            iso: toDateKey(cursor),
            dayNumber: cursor.getDate(),
            inCurrentMonth: cursor.getMonth() === month,
            isToday: toDateKey(cursor) === todayIso,
        });
        cursor.setDate(cursor.getDate() + 1);
    }
    return cells;
});

const isSelectableDay = (cell) => cell.inCurrentMonth && inHorizon(cell.iso) && !isDayUnavailable(cell.iso);

const dayClass = (cell) => {
    if (!cell.inCurrentMonth) {
        return 'text-base-content/20 border-transparent';
    }
    if (!inHorizon(cell.iso) || isDayUnavailable(cell.iso)) {
        return 'bg-base-300 text-base-content/40 border-base-300 cursor-not-allowed';
    }
    return cell.iso === selectedDate.value ? 'bg-primary text-primary-content border-primary' : 'border-base-300 hover:bg-base-200';
};

const cellTitle = (cell) => {
    if (!cell.inCurrentMonth || !inHorizon(cell.iso) || !isDayUnavailable(cell.iso)) return null;
    return dayStatus.value[cell.iso]?.closed ? 'Zatvorené' : 'Obsadené';
};

const monthLabel = computed(() => {
    const label = new Intl.DateTimeFormat('sk-SK', {month: 'long', year: 'numeric'}).format(new Date(viewYear.value, viewMonth.value, 1));
    return label.charAt(0).toUpperCase() + label.slice(1);
});

const startMonthKey = computed(() => horizonStart.value ? `${horizonStart.value.getFullYear()}-${horizonStart.value.getMonth()}` : null);
const endMonthKey = computed(() => {
    if (!horizonEndIso.value) return null;
    const [y, m] = horizonEndIso.value.split('-').map(Number);
    return `${y}-${m - 1}`;
});
const viewMonthKey = computed(() => `${viewYear.value}-${viewMonth.value}`);

const canGoPrev = computed(() => startMonthKey.value !== null && viewMonthKey.value !== startMonthKey.value);
const canGoNext = computed(() => endMonthKey.value !== null && viewMonthKey.value !== endMonthKey.value);

const goPrevMonth = () => {
    if (!canGoPrev.value) return;
    let m = viewMonth.value - 1;
    let y = viewYear.value;
    if (m < 0) {
        m = 11;
        y -= 1;
    }
    viewMonth.value = m;
    viewYear.value = y;
};

const goNextMonth = () => {
    if (!canGoNext.value) return;
    let m = viewMonth.value + 1;
    let y = viewYear.value;
    if (m > 11) {
        m = 0;
        y += 1;
    }
    viewMonth.value = m;
    viewYear.value = y;
};

const setViewToIso = (iso) => {
    const [y, m] = iso.split('-').map(Number);
    viewYear.value = y;
    viewMonth.value = m - 1;
};

const slots = computed(() => {
    if (!selectedDate.value || isClosedToday.value) return [];

    const [y, m, d] = selectedDate.value.split('-').map(Number);
    const list = [];
    const now = new Date();

    const [openHour, openMinute] = (dayOpeningHours.value?.opens_at ?? `${FALLBACK_OPEN_HOUR}:00`).split(':').map(Number);
    const [closeHour, closeMinute] = (dayOpeningHours.value?.closes_at ?? `${FALLBACK_CLOSE_HOUR}:00`).split(':').map(Number);
    const startMinutes = openHour * 60 + openMinute;
    const endMinutes = closeHour * 60 + closeMinute;

    for (let minutes = startMinutes; minutes < endMinutes; minutes += props.slotMinutes) {
        const start = new Date(y, m - 1, d, 0, minutes, 0, 0);
        const hh = String(start.getHours()).padStart(2, '0');
        const mm = String(start.getMinutes()).padStart(2, '0');
        // Plain local wall-clock key (no UTC conversion) - must match the
        // backend's "Y-m-d\TH:i" format exactly, otherwise already-booked
        // slots silently fail to match and appear clickable.
        const iso = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}T${hh}:${mm}`;
        list.push({
            iso,
            label: `${hh}:${mm}`,
            booked: bookedIso.value.includes(iso),
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
        return 'btn-disabled bg-base-300 text-base-content/40 border-base-300 cursor-not-allowed';
    }
    return 'btn-outline';
};

const selectDate = async (iso) => {
    if (!inHorizon(iso) || isDayUnavailable(iso)) return;

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
        dayOpeningHours.value = data.opening_hours ?? null;
        emit('loaded', data);
    } catch (error) {
        showErrorToast(error.message ?? 'Nepodarilo sa načítať dostupné termíny.');
    } finally {
        loading.value = false;
    }
};

// Returns the number of bookable calendar days (today through the
// playground's real horizon), or null if the request failed.
const fetchDayAvailability = async () => {
    try {
        const response = await http.request(`/api/playgrounds/${props.playgroundId}/day-availability`);
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message ?? 'Nepodarilo sa načítať dostupnosť termínov.');
        }
        dayStatus.value = Object.fromEntries(data.map(day => [day.date, day]));
        return data.length;
    } catch (error) {
        // Non-fatal: individual slots still get disabled once a date is selected.
        console.error('Failed to load day availability', error);
        return null;
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

const firstAvailableDayIso = () => {
    for (let i = 0; i < horizonDayCount.value; i++) {
        const iso = isoAtOffset(i);
        if (!isDayUnavailable(iso)) return iso;
    }
    return isoAtOffset(0);
};

const initDays = async () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    horizonStart.value = today;

    const dayCount = await fetchDayAvailability();
    horizonDayCount.value = dayCount ?? props.maxHorizonDays + 1;

    const firstIso = firstAvailableDayIso();
    setViewToIso(firstIso);
    await selectDate(firstIso);
};

watch(() => props.playgroundId, initDays);

onMounted(initDays);
</script>
