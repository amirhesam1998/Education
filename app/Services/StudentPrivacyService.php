<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;

class StudentPrivacyService
{
    public function canViewStudentName(User $user): bool
    {
        return true;
    }

    public function canViewStudentContactData(User $user): bool
    {
        return $this->canViewPersonalData($user);
    }

    public function canViewPersonalData(User $user): bool
    {
        return $user->can('view_student_personal_data');
    }

    public function canViewReservationSensitiveInfo(User $user): bool
    {
        return $user->can('view_reservation_sensitive_info');
    }

    public function canViewPaymentInfo(User $user): bool
    {
        return $user->can('view_reservation_payment_info');
    }

    public function canViewReceipt(User $user): bool
    {
        return $user->can('view_prepayment_receipts');
    }

    public function canViewStudentPublicLink(User $user): bool
    {
        return $user->can('view_student_public_link');
    }

    /** @return array<string, string|int|null> */
    public function educationalSummary(Reservation $reservation, User $user): array
    {
        return [
            'reservation_code' => '#'.$reservation->id,
            'advisor' => $reservation->advisor?->name ?: $reservation->slot?->advisor?->name,
            'exam_type' => $reservation->student?->examTypeLabel(),
            'major' => $reservation->student?->major,
            'region' => $reservation->student?->region,
            'score' => $reservation->student?->score,
        ];
    }
}
