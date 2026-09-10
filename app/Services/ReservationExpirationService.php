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
        private readonly SettingsService $settings,
    ) {
    }

    public function expireOverdueReservations(): int
    {
        $count = 0;

        Reservation::query()
            ->whereIn('status', [ReservationStatus::PendingCompletion, ReservationStatus::PendingPrepayment])
            ->where('prepayment_required', true)
            ->select('id')
            ->chunkById(100, function ($reservations) use (&$count): void {
                foreach ($reservations as $row) {
                    DB::transaction(function () use ($row, &$count): void {
                        $reservation = Reservation::query()
                            ->with('payment')
                            ->whereKey($row->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $reservation || ! in_array($reservation->status, [ReservationStatus::PendingCompletion, ReservationStatus::PendingPrepayment], true)) {
                            return;
                        }

                        if ($reservation->payment?->status === PaymentStatus::Approved || ! $this->deadline($reservation)->isPast()) {
                            return;
                        }

                        $this->reservationService->expire($reservation);

                        if ($reservation->payment && $reservation->payment->status !== PaymentStatus::Expired) {
                            $reservation->payment->forceFill(['status' => PaymentStatus::Expired])->save();
                        }

                        $this->activityLog->log('reservation_expired_payment_deadline', $reservation, null, null, ['deadline' => $this->deadline($reservation)->toDateTimeString()]);

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

    private function deadline(Reservation $reservation): \Illuminate\Support\Carbon
    {
        return $reservation->payment_deadline_at
            ?: $reservation->created_at->copy()->addMinutes($this->settings->defaultPaymentDeadlineMinutes());
    }
}
