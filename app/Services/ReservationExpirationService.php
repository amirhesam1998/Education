<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class ReservationExpirationService
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    public function expireOverdueReservations(): int
    {
        $count = 0;

        Reservation::query()
            ->whereIn('status', ReservationStatus::pendingValues())
            ->where(function ($query): void {
                $query->where(fn ($inner) => $inner
                    ->whereNotNull('payment_deadline_at')
                    ->where('payment_deadline_at', '<', now()))
                    ->orWhere(fn ($inner) => $inner
                        ->whereNotNull('public_token_expires_at')
                        ->where('public_token_expires_at', '<', now()));
            })
            ->select('id')
            ->chunkById(100, function ($reservations) use (&$count): void {
                foreach ($reservations as $row) {
                    DB::transaction(function () use ($row, &$count): void {
                        $reservation = Reservation::query()
                            ->with('payment')
                            ->whereKey($row->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $reservation || ! in_array($reservation->status->value, ReservationStatus::pendingValues(), true)) {
                            return;
                        }

                        $this->reservationService->expire($reservation);

                        if ($reservation->payment && $reservation->payment->status !== PaymentStatus::Expired) {
                            $reservation->payment->forceFill(['status' => PaymentStatus::Expired])->save();
                        }

                        $count++;
                    });
                }
            });

        return $count;
    }

    public function releaseExpiredSlots(): int
    {
        // Slot availability is derived from reservation status; expired reservations no longer lock slots.
        return 0;
    }
}
