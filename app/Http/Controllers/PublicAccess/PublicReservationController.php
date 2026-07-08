<?php

namespace App\Http\Controllers\PublicAccess;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicAccess\CompleteReservationRequest;
use App\Http\Requests\PublicAccess\UploadReceiptRequest;
use App\Services\PaymentApprovalService;
use App\Services\PublicReservationLinkService;
use App\Services\ReservationService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicReservationController extends Controller
{
    public function show(string $token, PublicReservationLinkService $links, ReservationService $reservations, SettingsService $settings): View
    {
        $reservation = $links->validateToken($token);
        $this->expireIfOverdue($reservation, $links, $reservations);
        $reservation->refresh()->load(['student.phones', 'slot.advisor', 'advisor', 'payment']);

        return view('public.reservation.show', [
            'reservation' => $reservation,
            'settings' => $settings,
            'isLinkExpired' => $links->isExpired($reservation),
        ]);
    }

    public function complete(
        CompleteReservationRequest $request,
        string $token,
        PublicReservationLinkService $links,
        ReservationService $reservations,
    ): RedirectResponse {
        $reservation = $links->validateToken($token);

        if (! $this->canUsePublicForm($reservation, $links)) {
            return back()->withErrors(['reservation' => 'امکان بروزرسانی این رزرو وجود ندارد. لطفاً با آموزشگاه تماس بگیرید.']);
        }

        $reservations->updateStudentMissingFields($reservation, $request->validated());

        if (filled($request->validated('student_note'))) {
            $reservation->forceFill(['student_note' => $request->validated('student_note')])->save();
        }

        return back()->with('success', 'اطلاعات شما ثبت شد.');
    }

    public function uploadReceipt(
        UploadReceiptRequest $request,
        string $token,
        PublicReservationLinkService $links,
        PaymentApprovalService $payments,
    ): RedirectResponse {
        $reservation = $links->validateToken($token);

        if (! $this->canUsePublicForm($reservation, $links)) {
            return back()->withErrors(['receipt_image' => 'امکان آپلود فیش برای این رزرو وجود ندارد. لطفاً با آموزشگاه تماس بگیرید.']);
        }

        $payments->uploadReceipt($reservation, $request->file('receipt_image'));

        return back()->with('success', 'فیش شما ثبت شد و در انتظار تأیید آموزشگاه است.');
    }

    private function canUsePublicForm($reservation, PublicReservationLinkService $links): bool
    {
        return ! $links->isExpired($reservation)
            && $reservation->status instanceof ReservationStatus
            && $reservation->status->allowsPublicUpdates();
    }

    private function expireIfOverdue($reservation, PublicReservationLinkService $links, ReservationService $reservations): void
    {
        if (
            $links->isExpired($reservation)
            && $reservation->status instanceof ReservationStatus
            && in_array($reservation->status->value, ReservationStatus::pendingValues(), true)
        ) {
            $reservations->expire($reservation);
        }
    }
}
