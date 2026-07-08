<?php

namespace App\Console\Commands;

use App\Services\ReservationExpirationService;
use Illuminate\Console\Command;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';

    protected $description = 'Expire overdue pending reservations and release their slots through status logic.';

    public function handle(ReservationExpirationService $expiration): int
    {
        $count = $expiration->expireOverdueReservations();
        $expiration->releaseExpiredSlots();

        $this->info("Expired reservations: {$count}");

        return self::SUCCESS;
    }
}
