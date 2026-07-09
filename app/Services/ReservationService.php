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
     * @return array<int, string> ISO datetime strings of booked slot starts
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
                $booked[] = $slot->toIso8601String();
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

        $slots = $durationMinutes / self::SLOT_MINUTES;

        return round($slots * (float)$playground->price_per_30min, 2);
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
