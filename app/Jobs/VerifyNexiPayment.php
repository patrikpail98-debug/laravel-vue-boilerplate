<?php

namespace App\Jobs;

use App\Services\NexiPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched by the Nexi webhook so the (potentially slow) authoritative
 * status check happens off the request thread - the webhook route itself
 * just enqueues this and returns 200 immediately.
 */
class VerifyNexiPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(public string $orderId)
    {
    }

    public function handle(NexiPaymentService $service): void
    {
        $service->verifyAndSync($this->orderId);
    }
}
