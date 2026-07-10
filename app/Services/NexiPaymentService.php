<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Mail\ReservationCardPaidMail;
use App\Mail\ReservationSportNotificationMail;
use App\Models\PaymentTransaction;
use App\Models\Reservation;
use App\Models\Setting;
use App\Traits\CanInstantiate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Wraps the Nexi XPay Hosted Payment Page API (plain HTTP calls, not the SDK -
 * see the implementation plan for why). The redirect back from the hosted
 * page and the notificationUrl webhook are both untrusted triggers: neither
 * is ever taken at face value. Both call verifyAndSync(), which re-fetches
 * the order status from Nexi itself (authenticated with our own API key)
 * before touching the reservation, so a forged callback can at worst cause a
 * harmless re-check.
 */
class NexiPaymentService
{
    use CanInstantiate;

    /**
     * Creates (or reuses) a Nexi hosted-page order for this reservation and
     * returns the URL to redirect the customer to.
     *
     * @throws PaymentGatewayException
     */
    public function initiate(Reservation $reservation): string
    {
        $reusable = $reservation->paymentTransactions()
            ->whereIn('status', PaymentTransaction::REUSABLE_STATUSES)
            ->where('created_at', '>', Carbon::now()->subMinutes(Reservation::PAYMENT_HOLD_MINUTES))
            ->whereNotNull('hosted_page_url')
            ->latest()
            ->first();

        if ($reusable) {
            return $reusable->hosted_page_url;
        }

        $amountCents = (int)round((float)$reservation->total_price * 100);

        do {
            // Nexi orderId: alphanumeric plus a small punctuation set, max 27 chars.
            $orderId = Str::random(27);
        } while (PaymentTransaction::query()->where('order_id', $orderId)->exists());

        // Persisted before the outbound call so we have a durable audit row
        // even if the HTTP request to Nexi fails or times out.
        $transaction = PaymentTransaction::query()->create([
            'reservation_id' => $reservation->id,
            'provider' => PaymentTransaction::PROVIDER_NEXI_XPAY,
            'order_id' => $orderId,
            'status' => PaymentTransaction::STATUS_PENDING,
            'amount_cents' => $amountCents,
            'currency' => 'EUR',
        ]);

        $returnUrl = rtrim(config('app.url'), '/') . '/rezervacia/platba/navrat?' . http_build_query([
            'reservation_id' => $reservation->id,
            'order_id' => $orderId,
        ]);

        $payload = [
            'paymentSession' => [
                'amount' => (string)$amountCents,
                'language' => 'SLK',
                'resultUrl' => $returnUrl,
                'cancelUrl' => $returnUrl . '&result=CANCEL',
                'captureType' => 'IMPLICIT',
                'actionType' => 'PAY',
            ],
            'order' => [
                'orderId' => $orderId,
                'amount' => (string)$amountCents,
                'currency' => 'EUR',
                'customerId' => $reservation->customer_email,
                'description' => Str::limit('Rezervacia: ' . $reservation->playground->name, 50, ''),
                'customerInfo' => [
                    'cardHolderEmail' => $reservation->customer_email,
                    'cardHolderName' => $reservation->customer_name,
                    'homePhone' => $reservation->customer_phone,
                ],
            ],
        ];

        try {
            $response = Http::withHeaders($this->headers())
                ->post($this->baseUrl() . '/orders/hpp', $payload);
        } catch (Throwable $e) {
            Log::error('Nexi order creation exception', ['reservation_id' => $reservation->id, 'order_id' => $orderId, 'error' => $e->getMessage()]);
            $transaction->update(['status' => PaymentTransaction::STATUS_ERROR]);
            throw new PaymentGatewayException('Platobná brána je momentálne nedostupná. Skúste to prosím neskôr.');
        }

        $data = $response->json();

        if ($response->failed() || empty($data['hostedPage'])) {
            Log::error('Nexi order creation failed', ['reservation_id' => $reservation->id, 'order_id' => $orderId, 'status' => $response->status(), 'body' => $data]);
            $transaction->update(['status' => PaymentTransaction::STATUS_ERROR, 'raw_response' => $data]);
            throw new PaymentGatewayException($data['message'] ?? 'Platobná brána zamietla požiadavku.');
        }

        $transaction->update([
            'hosted_page_url' => $data['hostedPage'],
            'security_token' => $data['securityToken'] ?? null,
            'raw_response' => $data,
        ]);

        return $data['hostedPage'];
    }

    /**
     * The single, idempotent routine both the return-page check and the
     * webhook call into. Re-fetches authoritative order status from Nexi and,
     * only if the reservation is still awaiting_payment, applies the outcome.
     * Safe to call multiple times / concurrently for the same order.
     */
    public function verifyAndSync(string $orderId): ?Reservation
    {
        $transaction = PaymentTransaction::query()->where('order_id', $orderId)->first();

        if (!$transaction) {
            Log::warning('Nexi verifyAndSync called for unknown order_id', ['order_id' => $orderId]);
            return null;
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->get($this->baseUrl() . "/orders/{$orderId}");
        } catch (Throwable $e) {
            Log::error('Nexi order status check exception', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return $transaction->reservation;
        }

        $data = $response->json();

        if ($response->failed()) {
            Log::error('Nexi order status check failed', ['order_id' => $orderId, 'status' => $response->status(), 'body' => $data]);
            return $transaction->reservation;
        }

        $outcome = $this->interpretOutcome($data ?? [], $transaction->amount_cents);

        return DB::transaction(function () use ($transaction, $data, $outcome, $orderId) {
            $transaction->update([
                'raw_response' => $data,
                'last_operation_type' => $data['orderStatus']['lastOperationType'] ?? null,
                'status' => match ($outcome) {
                    'authorized' => PaymentTransaction::STATUS_AUTHORIZED,
                    'declined' => PaymentTransaction::STATUS_DECLINED,
                    default => $transaction->status,
                },
                'verified_at' => Carbon::now(),
            ]);

            /** @var Reservation $reservation */
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($transaction->reservation_id);

            // Already processed by a concurrent webhook/redirect check, or the
            // hold already expired server-side - no-op to stay idempotent.
            if ($reservation->status !== Reservation::STATUS_AWAITING_PAYMENT) {
                return $reservation;
            }

            if ($outcome === 'authorized') {
                $reservation->update(['status' => Reservation::STATUS_APPROVED, 'verified_at' => Carbon::now()]);
                $reservation->loadMissing('playground.area');

                Mail::to($reservation->customer_email)->send(new ReservationCardPaidMail($reservation));

                $sportEmail = Setting::query()->where('key', Setting::SPORT_NOTIFICATION_EMAIL_KEY)->value('value');
                if ($sportEmail) {
                    Mail::to($sportEmail)->send(new ReservationSportNotificationMail($reservation));
                }
            } elseif ($outcome === 'declined') {
                $reservation->update(['status' => Reservation::STATUS_CANCELLED]);
            }
            // 'pending': no definitive outcome yet (e.g. customer still on the
            // hosted page) - leave as awaiting_payment, still holding the slot
            // until PAYMENT_HOLD_MINUTES elapses and the expiry command cancels it.

            Log::info('Nexi payment verified', ['order_id' => $orderId, 'reservation_id' => $reservation->id, 'outcome' => $outcome]);

            return $reservation;
        });
    }

    /**
     * Interprets a GET /orders/{orderId} response into 'authorized',
     * 'declined' or 'pending'.
     *
     * The response has two relevant parts: a flat `orderStatus` summary
     * (lastOperationType/authorizedAmount/capturedAmount) and an `operations`
     * array where each entry carries its own operationType + operationResult
     * (e.g. AUTHORIZATION/EXECUTED, or AUTHORIZATION/DECLINED). The result is
     * what actually distinguishes a genuine decline from a still-open
     * authorization, so every operation is scanned rather than only trusting
     * the top-level lastOperationType.
     *
     * Deliberately conservative on anything ambiguous/incomplete: falls
     * through to 'pending' rather than guessing, so a reservation is never
     * wrongly approved - it stays held until a later check confirms it or
     * the hold expires.
     */
    private function interpretOutcome(array $orderData, int $expectedAmountCents): string
    {
        $orderStatus = $orderData['orderStatus'] ?? [];
        $operations = $orderData['operations'] ?? [];

        if (in_array($orderStatus['lastOperationType'] ?? null, ['CANCEL', 'VOID', 'REFUND'], true)) {
            return 'declined';
        }

        $isAuthorized = false;
        $isDenied = false;

        foreach ($operations as $operation) {
            if (!in_array($operation['operationType'] ?? null, ['AUTHORIZATION', 'CAPTURE'], true)) {
                continue;
            }

            $result = $operation['operationResult'] ?? null;

            if (in_array($result, ['AUTHORIZED', 'EXECUTED'], true)) {
                $isAuthorized = true;
            }

            if (in_array($result, ['DECLINED', 'REFUSED', 'ERROR'], true)) {
                $isDenied = true;
            }
        }

        if ($isDenied && !$isAuthorized) {
            return 'declined';
        }

        if ($isAuthorized) {
            $authorizedAmount = isset($orderStatus['authorizedAmount']) ? (int)$orderStatus['authorizedAmount'] : null;
            $capturedAmount = isset($orderStatus['capturedAmount']) ? (int)$orderStatus['capturedAmount'] : null;
            $confirmedAmount = $capturedAmount ?? $authorizedAmount;

            // Extra safety net beyond the operation result itself: only trust
            // the authorization if the confirmed amount matches what we asked
            // for. A missing/mismatched amount on an otherwise-authorized
            // operation falls through to 'pending' instead of being approved.
            if ($confirmedAmount !== null && $confirmedAmount >= $expectedAmountCents) {
                return 'authorized';
            }
        }

        return 'pending';
    }

    private function headers(): array
    {
        return [
            'X-API-KEY' => config('services.nexi.api_key'),
            'Correlation-Id' => (string)Str::uuid(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.nexi.base_url'), '/');
    }
}
