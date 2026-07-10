<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireUnverifiedReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-unverified-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancels reservations whose hold (email confirmation or card-payment attempt) has expired, freeing their slot';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $unverifiedCount = Reservation::query()
            ->where('status', Reservation::STATUS_UNVERIFIED)
            ->where('created_at', '<', Carbon::now()->subMinutes(Reservation::HOLD_MINUTES))
            ->update(['status' => Reservation::STATUS_CANCELLED]);

        // Abandoned card-payment attempts (customer never finished the hosted
        // page, or Nexi never called back) - free the slot once the longer
        // payment hold window elapses.
        $awaitingPaymentCount = Reservation::query()
            ->where('status', Reservation::STATUS_AWAITING_PAYMENT)
            ->where('created_at', '<', Carbon::now()->subMinutes(Reservation::PAYMENT_HOLD_MINUTES))
            ->update(['status' => Reservation::STATUS_CANCELLED]);

        $this->info("Expired {$unverifiedCount} unverified and {$awaitingPaymentCount} awaiting-payment reservation(s).");
    }
}
