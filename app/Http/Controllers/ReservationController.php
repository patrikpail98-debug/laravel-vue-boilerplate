<?php

namespace App\Http\Controllers;

use App\Mail\ReservationPaymentMail;
use App\Mail\ReservationVerificationMail;
use App\Models\Playground;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Laravel\Sanctum\PersonalAccessToken;

class ReservationController extends Controller
{
    use JsonResponseTrait;

    /**
     * Create a reservation. Works for guests and, if a valid Bearer token is
     * present, links the reservation to the authenticated user (login stays optional).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'playground_id' => 'required|exists:playgrounds,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:30',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
        ]);

        $service = ReservationService::instance();

        try {
            $reservation = DB::transaction(function () use ($validated, $service, $request) {
                $playground = Playground::query()->lockForUpdate()->findOrFail($validated['playground_id']);

                $start = Carbon::parse($validated['start_time']);
                $end = Carbon::parse($validated['end_time']);

                $price = $service->validateAndPrice($playground, $start, $end);

                return Reservation::query()->create([
                    'playground_id' => $playground->id,
                    'user_id' => $this->resolveOptionalUser($request)?->id,
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'],
                    'start_time' => $start,
                    'end_time' => $end,
                    'variable_symbol' => $service->generateVariableSymbol(),
                    'total_price' => $price,
                    'status' => Reservation::STATUS_UNVERIFIED,
                    'verification_token' => $service->generateVerificationToken(),
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse(['message' => $e->getMessage()], 422);
        }

        Mail::to($reservation->customer_email)->send(new ReservationVerificationMail($reservation));

        return $this->successResponse([
            'message' => 'Rezervácia bola vytvorená. Na Váš e-mail sme poslali potvrdzujúci odkaz.',
            'reservation' => $reservation,
        ], 201);
    }

    /**
     * Confirms a guest's email via the link sent by ReservationVerificationMail.
     */
    public function verify(int $id, string $token): JsonResponse
    {
        $reservation = Reservation::query()->find($id);

        if (!$reservation || !hash_equals((string)$reservation->verification_token, $token)) {
            return $this->errorResponse(['message' => 'Neplatný alebo expirovaný odkaz.'], 404);
        }

        if ($reservation->status !== Reservation::STATUS_UNVERIFIED) {
            return $this->errorResponse(['message' => 'Táto rezervácia je už spracovaná.'], 422);
        }

        if ($reservation->isExpiredHold()) {
            $reservation->update(['status' => Reservation::STATUS_CANCELLED]);
            return $this->errorResponse(['message' => 'Platnosť odkazu vypršala, termín bol uvoľnený. Prosím, vytvorte rezerváciu znova.'], 410);
        }

        $reservation->update([
            'status' => Reservation::STATUS_PENDING_APPROVAL,
            'verified_at' => Carbon::now(),
        ]);

        Mail::to($reservation->customer_email)->send(new ReservationPaymentMail($reservation));

        return $this->successResponse([
            'message' => 'Rezervácia bola overená. Platobné údaje sme Vám poslali e-mailom.',
            'reservation' => $reservation,
        ]);
    }

    private function resolveOptionalUser(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        return PersonalAccessToken::findToken($token)?->tokenable;
    }
}
