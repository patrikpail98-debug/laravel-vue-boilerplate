// Reservation timestamps from the API are true UTC instants (ISO strings with
// a "Z" suffix). They must always be displayed in the facility's own
// timezone, regardless of the viewer's browser/OS timezone, otherwise a
// visitor or admin outside Slovakia would see a shifted (wrong) time.
const FACILITY_TIMEZONE = 'Europe/Bratislava';

const dateTimeFormatter = new Intl.DateTimeFormat('sk-SK', {
    timeZone: FACILITY_TIMEZONE,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

const timeFormatter = new Intl.DateTimeFormat('sk-SK', {
    timeZone: FACILITY_TIMEZONE,
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

const partsToMap = (parts) => Object.fromEntries(parts.map((p) => [p.type, p.value]));

/**
 * Formats a reservation's start/end (UTC ISO strings) as
 * "dd.mm.yyyy HH:MM–HH:MM" in facility-local (Europe/Bratislava) time.
 */
export const formatReservationRange = (start, end) => {
    const parts = partsToMap(dateTimeFormatter.formatToParts(new Date(start)));
    const endTime = timeFormatter.format(new Date(end));
    return `${parts.day}.${parts.month}.${parts.year} ${parts.hour}:${parts.minute}–${endTime}`;
};
