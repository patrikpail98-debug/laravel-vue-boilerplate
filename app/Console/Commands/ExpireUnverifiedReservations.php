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
    protected $description = 'Cancels unverified reservations whose email confirmation hold has expired, freeing their slot';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = Reservation::query()
            ->where('status', Reservation::STATUS_UNVERIFIED)
            ->where('created_at', '<', Carbon::now()->subMinutes(Reservation::HOLD_MINUTES))
            ->update(['status' => Reservation::STATUS_CANCELLED]);

        $this->info("Expired {$count} unverified reservation(s).");
    }
}
