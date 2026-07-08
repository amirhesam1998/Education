<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_reservations');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->can('view_reservations')
            || ($user->can('view_slots') && $reservation->advisor_id !== null);
    }

    public function create(User $user): bool
    {
        return $user->can('create_reservations');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->can('update_reservations');
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->can('cancel_reservations');
    }

    public function changeSlot(User $user, Reservation $reservation): bool
    {
        return $user->can('change_reservation_slot');
    }
}
