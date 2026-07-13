<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Mail\ReservationCardPaymentMail;
use App\Mail\ReservationPaymentMail;
use App\Mail\ReservationVerificationMail;
use App\Models\Playground;
use App\Models\PaymentTransaction;
use App\Models\Reservation;
use App\Services\NexiPaymentService;
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
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:120',
            'postcode' => 'required|string|max:20',
            'ico' => 'nullable|string|max:20',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'payment_method' => 'required|in:' . Reservation::PAYMENT_METHOD_BANK_TRANSFER . ',' . Reservation::PAYMENT_METHOD_CARD,
        ]);

        $service = ReservationService::instance();
        $isCard = $validated['payment_method'] === Reservation::PAYMENT_METHOD_CARD;

        try {
            $reservation = DB::transaction(function () use ($validated, $service, $request, $isCard) {
                $playground = Playground::query()->lockForUpdate()->findOrFail($validated['playground_id']);

                if ($isCard && !$playground->allow_card_payment) {
                    throw new InvalidArgumentException('Toto ihrisko neumožňuje platbu kartou.');
                }

                // The frontend sends naive wall-clock times (no offset) meaning
                // facility-local time, e.g. "14:00" at the ground itself - parse
                // them as such, then convert to a true UTC instant for storage.
                $start = Carbon::parse($validated['start_time'], config('app.facility_timezone'))->setTimezone('UTC');
                $end = Carbon::parse($validated['end_time'], config('app.facility_timezone'))->setTimezone('UTC');

                $price = $service->validateAndPrice($playground, $start, $end);

                $address = trim($validated['street'] . ', ' . $validated['postcode'] . ' ' . $validated['city']);

                return Reservation::query()->create([
                    'playground_id' => $playground->id,
                    'user_id' => $this->resolveOptionalUser($request)?->id,
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'],
                    'ico' => $validated['ico'] ?? null,
                    'address' => $address,
                    'start_time' => $start,
                    'end_time' => $end,
                    'variable_symbol' => $service->generateVariableSymbol(),
                    'total_price' => $price,
                    'payment_method' => $validated['payment_method'],
                    // Card payments skip the email-verification hold: the hosted
                    // payment page (3DS) itself is the proof of a genuine attempt.
                    'status' => $isCard ? Reservation::STATUS_AWAITING_PAYMENT : Reservation::STATUS_UNVERIFIED,
                    'verification_token' => $isCard ? null : $service->generateVerificationToken(),
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse(['message' => $e->getMessage()], 422);
        }

        if ($isCard) {
            try {
                $paymentUrl = NexiPaymentService::instance()->initiate($reservation);
            } catch (PaymentGatewayException $e) {
                // Don't leave a dead slot hold behind if we couldn't even start the payment.
                $reservation->update(['status' => Reservation::STATUS_CANCELLED]);
                return $this->errorResponse(['message' => $e->getMessage()], 502);
            }

            // Backup route in case the customer closes the hosted-page tab: an
            // email with a link back to our own resume page (not the raw Nexi
            // URL, which may no longer be valid by the time they click it).
            $orderId = $reservation->paymentTransactions()->latest()->value('order_id');
            if ($orderId) {
                Mail::to($reservation->customer_email)->send(new ReservationCardPaymentMail($reservation, $orderId));
            }

            return $this->successResponse([
                'message' => 'Presmerúvame Vás na platobnú bránu.',
                'reservation' => $reservation,
                'payment_url' => $paymentUrl,
            ], 201);
        }

        Mail::to($reservation->customer_email)->send(new ReservationVerificationMail($reservation));

        return $this->successResponse([
            'message' => 'Rezervácia bola vytvorená. Na Váš e-mail sme poslali potvrdzujúci odkaz.',
            'reservation' => $reservation,
        ], 201);
    }

    /**
     * Polled by the frontend return page after a card-payment redirect.
     * Re-verifies against Nexi itself (via NexiPaymentService) rather than
     * trusting the redirect's query string - see NexiPaymentService docblock.
     */
    public function paymentStatus(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
        ]);

        $verified = NexiPaymentService::instance()->verifyAndSync($validated['order_id']);

        if (!$verified || $verified->id !== $reservation->id) {
            return $this->errorResponse(['message' => 'Neplatná platobná transakcia.'], 404);
        }

        return $this->successResponse([
            'status' => $verified->status,
            'reservation' => $verified,
        ]);
    }

    /**
     * Visited from the "Pokračovať v platbe" link in ReservationCardPaymentMail
     * when the customer closed the original hosted-page tab. Always re-checks
     * the authoritative payment status first, then either hands back a
     * (possibly reused) hosted-page URL to redirect to, or a clear error.
     */
    public function resumePayment(string $orderId): JsonResponse
    {
        $transaction = PaymentTransaction::query()->where('order_id', $orderId)->first();

        if (!$transaction) {
            return $this->errorResponse(['message' => 'Neplatný odkaz na platbu.'], 404);
        }

        NexiPaymentService::instance()->verifyAndSync($orderId);

        $reservation = $transaction->reservation()->firstOrFail();

        if ($reservation->status === Reservation::STATUS_APPROVED) {
            return $this->errorResponse(['message' => 'Táto rezervácia už bola zaplatená.'], 422);
        }

        if ($reservation->status === Reservation::STATUS_AWAITING_PAYMENT && $reservation->isExpiredPaymentHold()) {
            $reservation->update(['status' => Reservation::STATUS_CANCELLED]);
        }

        if ($reservation->status !== Reservation::STATUS_AWAITING_PAYMENT) {
            return $this->errorResponse(['message' => 'Platnosť tejto rezervácie vypršala alebo bola zrušená. Vytvorte si prosím novú rezerváciu.'], 410);
        }

        try {
            $paymentUrl = NexiPaymentService::instance()->initiate($reservation);
        } catch (PaymentGatewayException $e) {
            return $this->errorResponse(['message' => $e->getMessage()], 502);
        }

        return $this->successResponse(['payment_url' => $paymentUrl]);
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
