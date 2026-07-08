<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicReservationLinkService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly SettingsService $settings,
    ) {
    }

    public function generate(Reservation $reservation): Reservation
    {
        $reservation->forceFill([
            'public_token' => $this->newToken(),
            'public_token_expires_at' => $this->expiresAt($reservation),
        ])->save();

        $this->activityLog->log('public_link_generated', $reservation, auth()->user());

        return $reservation;
    }

    public function regenerate(Reservation $reservation): Reservation
    {
        $old = ['public_token' => $reservation->public_token];

        $reservation->forceFill([
            'public_token' => $this->newToken(),
            'public_token_expires_at' => $this->expiresAt($reservation),
            'public_token_used_at' => null,
        ])->save();

        $this->activityLog->log('public_link_regenerated', $reservation, auth()->user(), $old, [
            'public_token' => $reservation->public_token,
        ]);

        return $reservation;
    }

    public function validateToken(string $token): Reservation
    {
        $reservation = Reservation::query()
            ->with(['student.phones', 'slot.advisor', 'advisor', 'payment'])
            ->where('public_token', $token)
            ->first();

        if (! $reservation) {
            throw new NotFoundHttpException();
        }

        if (! $reservation->public_token_used_at) {
            $reservation->forceFill(['public_token_used_at' => now()])->save();
        }

        return $reservation;
    }

    public function isExpired(Reservation $reservation): bool
    {
        $expiresAt = $reservation->public_token_expires_at;

        return $expiresAt !== null && $expiresAt->isPast();
    }

    private function newToken(): string
    {
        do {
            $token = Str::random(80);
        } while (Reservation::query()->where('public_token', $token)->exists());

        return $token;
    }

    private function expiresAt(Reservation $reservation): Carbon
    {
        if ($reservation->prepayment_required && $reservation->payment_deadline_at) {
            return $reservation->payment_deadline_at;
        }

        $hours = max(1, (int) $this->settings->get('default_public_link_expiration_hours', 48));

        return now()->addHours($hours);
    }
}
