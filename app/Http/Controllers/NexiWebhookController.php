<?php

namespace App\Http\Controllers;

use App\Jobs\VerifyNexiPayment;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NexiWebhookController extends Controller
{
    use JsonResponseTrait;

    /**
     * Server-to-server notification from Nexi (paymentSession.notificationUrl).
     * The payload's claimed status is never trusted directly - only the
     * orderId is used, purely as a trigger to queue an authoritative re-check
     * via NexiPaymentService::verifyAndSync(). See that service's docblock
     * for the full reasoning.
     */
    public function handle(Request $request): JsonResponse
    {
        $orderId = $request->input('orderId')
            ?? $request->input('order.orderId')
            ?? $request->input('order_id');

        if (!$orderId || !is_string($orderId)) {
            Log::warning('Nexi webhook received without a usable orderId', ['payload' => $request->all()]);
            return $this->successResponse(['message' => 'ignored']);
        }

        VerifyNexiPayment::dispatch($orderId);

        return $this->successResponse(['message' => 'ok']);
    }
}
