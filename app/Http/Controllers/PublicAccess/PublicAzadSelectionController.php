<?php

namespace App\Http\Controllers\PublicAccess;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\AzadSelectionPlan;
use App\Models\Reservation;
use App\Services\Azad\AzadSelectionService;
use App\Services\PublicReservationLinkService;
use App\Services\ReservationService;
use Illuminate\View\View;

/** The student's Azad university list, opened from their secure reservation link. */
class PublicAzadSelectionController extends Controller
{
    public function show(string $token, AzadSelectionPlan $plan, PublicReservationLinkService $links, ReservationService $reservations, AzadSelectionService $selections): View
    {
        return $this->page($token, $plan, $links, $reservations, $selections, false);
    }

    public function print(string $token, AzadSelectionPlan $plan, PublicReservationLinkService $links, ReservationService $reservations, AzadSelectionService $selections): View
    {
        return $this->page($token, $plan, $links, $reservations, $selections, true);
    }

    private function page(string $token, AzadSelectionPlan $plan, PublicReservationLinkService $links, ReservationService $reservations, AzadSelectionService $selections, bool $autoPrint): View
    {
        $reservation = $links->validateToken($token);
        $this->expireIfOverdue($reservation, $reservations);

        if (! $links->isAccessible($reservation)) {
            return view('public.reservation.blocked', ['message' => $links->getBlockedReasonMessage($reservation)]);
        }

        $plan = $selections->studentVisiblePlanOrFail($reservation, $plan);
        $plan->load(['items', 'student', 'reservation.slot.advisor', 'reservation.advisor']);

        return view('azad-selection.print', [
            'plan' => $plan,
            'canViewStudentPersonalInfo' => true,
            'autoPrint' => $autoPrint,
            'backUrl' => route('public.reservations.show', $token),
            'printUrl' => route('public.reservations.azad-selection.print', [$token, $plan]),
        ]);
    }

    private function expireIfOverdue(Reservation $reservation, ReservationService $reservations): void
    {
        if (
            $reservation->payment_deadline_at
            && $reservation->payment_deadline_at->isPast()
            && $reservation->prepayment_required
            && $reservation->status instanceof ReservationStatus
            && in_array($reservation->status, [ReservationStatus::PendingCompletion, ReservationStatus::PendingPrepayment], true)
            && $reservation->payment?->status !== PaymentStatus::Approved
        ) {
            $reservations->expire($reservation);
        }
    }
}
