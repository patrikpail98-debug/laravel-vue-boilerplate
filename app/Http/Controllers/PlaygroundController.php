<?php

namespace App\Http\Controllers;

use App\Models\Playground;
use App\Services\ReservationService;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PlaygroundController extends Controller
{
    use JsonResponseTrait;

    /**
     * Booked slots for a given playground/day, plus the booking rules the
     * frontend needs to render the slot picker (price, max duration, horizon).
     */
    public function availability(Request $request, Playground $playground): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        if (!$playground->is_active) {
            return $this->errorResponse(['message' => 'Toto ihrisko nie je dostupné.'], 404);
        }

        $service = ReservationService::instance();
        $date = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();

        if ($date->lt(Carbon::today()) || $date->gt($service->getMaxBookableDate($playground))) {
            return $this->errorResponse(['message' => 'Zvolený dátum je mimo povoleného rozsahu.'], 422);
        }

        return $this->successResponse([
            'playground' => [
                'id' => $playground->id,
                'name' => $playground->name,
                'area_name' => $playground->area->name,
                'image_url' => $playground->image_url,
                'latitude' => $playground->latitude,
                'longitude' => $playground->longitude,
            ],
            'booked_slots' => $service->getBookedSlots($playground, $date),
            'price_per_30min' => (float)$playground->price_per_30min,
            'max_duration_minutes' => $playground->max_duration_minutes,
            'max_horizon_days' => $playground->max_horizon_days,
            'slot_minutes' => ReservationService::SLOT_MINUTES,
        ]);
    }
}
