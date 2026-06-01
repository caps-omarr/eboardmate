<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class ExpirePendingReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     * php artisan reservations:expire-pending
     */
    protected $signature = 'reservations:expire-pending';

    /**
     * The console command description.
     */
    protected $description = 'Mark pending reservations as expired when their expiration time has passed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = Reservation::query()
            ->where('status', Reservation::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => Reservation::STATUS_EXPIRED,
                'expired_at' => now(),
            ]);

        $this->info($expiredCount . ' pending reservation(s) marked as expired.');

        return self::SUCCESS;
    }
}