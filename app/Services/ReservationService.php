<?php

namespace App\Services;

use App\Models\Playground;
use App\Models\Reservation;
use App\Traits\CanInstantiate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReservationService
{
    use CanInstantiate;

    public const SLOT_MINUTES = 30;

    /**
     * Returns the list of 30-minute slot start times for the given day that are
     * already taken by an active reservation on the given playground.
     *
     * Keys are plain wall-clock "Y-m-d\TH:i" strings (no timezone offset) so
     * they compare equal to the slot keys the frontend builds from local date
     * parts - reservations are stored/compared as naive local times throughout
     * this app, so introducing real UTC/offset conversion here would make
     * already-booked slots silently fail to match and appear bookable.
     *
     * @return array<int, string>
     */
    public function getBookedSlots(Playground $playground, Carbon $date): array
    {
        $dayStart = $date->clone()->startOfDay();
        $dayEnd = $date->clone()->endOfDay();

        $reservations = $playground->reservations()
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->where('start_time', '<', $dayEnd)
            ->where('end_time', '>', $dayStart)
            ->get(['start_time', 'end_time', 'status', 'created_at']);

        $booked = [];

        foreach ($reservations as $reservation) {
            if ($reservation->isExpiredHold()) {
                continue;
            }

            $slot = $reservation->start_time->clone();
            while ($slot->lt($reservation->end_time)) {
                $booked[] = $slot->format('Y-m-d\TH:i');
                $slot->addMinutes(self::SLOT_MINUTES);
            }
        }

        return array_values(array_unique($booked));
    }

    /**
     * The last date (inclusive) that can be booked on this playground, based on today.
     */
    public function getMaxBookableDate(Playground $playground): Carbon
    {
        return Carbon::today()->addDays($playground->max_horizon_days);
    }

    /**
     * Validates a requested booking window and returns its price.
     * Throws InvalidArgumentException with a user-facing message on any violation.
     */
    public function validateAndPrice(Playground $playground, Carbon $start, Carbon $end): float
    {
        if (!$playground->is_active) {
            throw new InvalidArgumentException('Toto ihrisko momentálne nie je dostupné na rezerváciu.');
        }

        if ($start->gte($end)) {
            throw new InvalidArgumentException('Čas ukončenia musí byť po čase začiatku.');
        }

        if ($start->minute % self::SLOT_MINUTES !== 0 || $start->second !== 0) {
            throw new InvalidArgumentException('Rezervácia musí začínať na celej alebo polhodine.');
        }

        $durationMinutes = $start->diffInMinutes($end);

        if ($durationMinutes % self::SLOT_MINUTES !== 0) {
            throw new InvalidArgumentException('Dĺžka rezervácie musí byť násobkom 30 minút.');
        }

        if ($durationMinutes > $playground->max_duration_minutes) {
            throw new InvalidArgumentException("Maximálna dĺžka rezervácie je {$playground->max_duration_minutes} minút.");
        }

        if ($start->lt(Carbon::now())) {
            throw new InvalidArgumentException('Nie je možné rezervovať termín v minulosti.');
        }

        if ($start->gt($this->getMaxBookableDate($playground)->endOfDay())) {
            throw new InvalidArgumentException("Rezervovať je možné najviac {$playground->max_horizon_days} dní dopredu.");
        }

        if ($this->hasOverlap($playground, $start, $end)) {
            throw new InvalidArgumentException('Vybraný termín je už obsadený, zvoľte iný.');
        }

        if (!$this->isWithinOpeningHours($playground, $start, $end)) {
            throw new InvalidArgumentException('Vybraný termín je mimo otváracích hodín tohto ihriska.');
        }

        $slots = $durationMinutes / self::SLOT_MINUTES;

        return round($slots * (float)$playground->price_per_30min, 2);
    }

    /**
     * Checks the requested (same-day) window against the playground's weekly
     * opening hours. A playground with no configured hours is unrestricted.
     */
    public function isWithinOpeningHours(Playground $playground, Carbon $start, Carbon $end): bool
    {
        $hours = $playground->openingHoursFor($start);

        if ($hours === null) {
            return true;
        }

        if (!empty($hours['is_closed']) || empty($hours['opens_at']) || empty($hours['closes_at'])) {
            return false;
        }

        return $start->format('H:i') >= $hours['opens_at'] && $end->format('H:i') <= $hours['closes_at'];
    }

    /**
     * Per-day summary (closed / fully booked) for every day from today through
     * the playground's booking horizon, computed with a single query so the
     * frontend can grey out whole days in the date picker up front instead of
     * only disabling individual slots after the user picks a date.
     *
     * @return array<int, array{date: string, closed: bool, fully_booked: bool}>
     */
    public function getHorizonDaySummaries(Playground $playground): array
    {
        $today = Carbon::today();
        $maxDate = $this->getMaxBookableDate($playground);

        $reservations = $playground->reservations()
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->where('start_time', '<', $maxDate->clone()->endOfDay())
            ->where('end_time', '>', $today)
            ->get(['start_time', 'end_time', 'status', 'created_at']);

        $bookedCountByDate = [];

        foreach ($reservations as $reservation) {
            if ($reservation->isExpiredHold()) {
                continue;
            }

            $slot = $reservation->start_time->clone();
            while ($slot->lt($reservation->end_time)) {
                $dateKey = $slot->toDateString();
                $bookedCountByDate[$dateKey] = ($bookedCountByDate[$dateKey] ?? 0) + 1;
                $slot->addMinutes(self::SLOT_MINUTES);
            }
        }

        $summaries = [];
        $date = $today->clone();

        while ($date->lte($maxDate)) {
            $hours = $playground->openingHoursFor($date);
            $closed = $hours !== null && (!empty($hours['is_closed']) || empty($hours['opens_at']) || empty($hours['closes_at']));
            $fullyBooked = false;

            if (!$closed && $hours !== null) {
                [$openHour, $openMinute] = array_map('intval', explode(':', $hours['opens_at']));
                [$closeHour, $closeMinute] = array_map('intval', explode(':', $hours['closes_at']));
                $totalSlots = (($closeHour * 60 + $closeMinute) - ($openHour * 60 + $openMinute)) / self::SLOT_MINUTES;
                $bookedCount = $bookedCountByDate[$date->toDateString()] ?? 0;
                $fullyBooked = $totalSlots > 0 && $bookedCount >= $totalSlots;
            }

            $summaries[] = [
                'date' => $date->toDateString(),
                'closed' => $closed,
                'fully_booked' => $fullyBooked,
            ];

            $date->addDay();
        }

        return $summaries;
    }

    public function hasOverlap(Playground $playground, Carbon $start, Carbon $end, ?int $excludeReservationId = null): bool
    {
        $reservations = $playground->reservations()
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->when($excludeReservationId, fn($query) => $query->where('id', '!=', $excludeReservationId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->get(['id', 'status', 'created_at']);

        foreach ($reservations as $reservation) {
            if (!$reservation->isExpiredHold()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates a unique variable symbol in the format YYYYMMDDXXX.
     */
    public function generateVariableSymbol(): string
    {
        $prefix = Carbon::now()->format('Ymd');

        do {
            $suffix = str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $candidate = $prefix . $suffix;
        } while (Reservation::query()->where('variable_symbol', $candidate)->exists());

        return $candidate;
    }

    public function generateVerificationToken(): string
    {
        return Str::random(40);
    }
}
