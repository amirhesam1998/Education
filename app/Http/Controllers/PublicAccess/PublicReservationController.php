<?php

namespace App\Http\Controllers\PublicAccess;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadReportCardRequest;
use App\Http\Requests\PublicAccess\CompleteReservationRequest;
use App\Http\Requests\PublicAccess\UploadReceiptRequest;
use App\Models\ReservationDocument;
use App\Models\FieldSelectionPlan;
use App\Services\PaymentApprovalService;
use App\Services\PublicReservationLinkService;
use App\Services\ReservationDocumentService;
use App\Services\ReservationFlowService;
use App\Services\ReservationService;
use App\Services\SettingsService;
use App\Services\FieldSelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicReservationController extends Controller
{
    public function show(string $token, PublicReservationLinkService $links, ReservationService $reservations, SettingsService $settings, ReservationFlowService $flow, FieldSelectionService $fieldSelections): View
    {
        $reservation = $links->validateToken($token);
        $this->expireIfOverdue($reservation, $links, $reservations);
        $reservation->refresh()->load(['student.phones', 'slot.advisor', 'advisor', 'payment', 'paymentCard', 'reportCards']);

        if (! $links->isAccessible($reservation)) {
            return view('public.reservation.blocked', ['message' => $links->getBlockedReasonMessage($reservation)]);
        }

        $visibleFieldSelectionPlans = $fieldSelections->getStudentVisiblePlans($reservation);
        $publishedFieldSelectionPlan = $visibleFieldSelectionPlans->first();

        return view('public.reservation.show', [
            'reservation' => $reservation,
            'settings' => $settings,
            'flowSteps' => $flow->getPublicFlowSteps($reservation),
            'flowMessage' => $flow->getStatusMessage($reservation),
            'showCompletionWarning' => $flow->shouldShowCompletionWarning($reservation),
            'showPaymentWarning' => $flow->shouldShowPaymentWarning($reservation),
            'isLinkExpired' => $links->isExpired($reservation),
            'canUploadReportCard' => $this->canUploadReportCard($reservation, $links),
            'publishedFieldSelectionPlan' => $publishedFieldSelectionPlan,
            'visibleFieldSelectionPlans' => $visibleFieldSelectionPlans,
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

    public function uploadReportCard(
        UploadReportCardRequest $request,
        string $token,
        PublicReservationLinkService $links,
        ReservationDocumentService $documents,
    ): RedirectResponse {
        $reservation = $links->validateToken($token);

        if (! $this->canUploadReportCard($reservation, $links)) {
            return back()->withErrors(['report_card' => 'امکان آپلود کارنامه برای این رزرو وجود ندارد. لطفاً با آموزشگاه تماس بگیرید.']);
        }

        $documents->uploadReportCardFromStudent($reservation, $request->file('report_card'));

        return back()->with('success', 'کارنامه شما با موفقیت ثبت شده است.');
    }

    public function reportCard(string $token, ReservationDocument $document, PublicReservationLinkService $links)
    {
        $reservation = $links->validateToken($token);

        if (! $links->isAccessible($reservation)) {
            return view('public.reservation.blocked', ['message' => $links->getBlockedReasonMessage($reservation)]);
        }
        abort_unless((int) $document->reservation_id === (int) $reservation->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function fieldSelection(string $token, Request $request, PublicReservationLinkService $links, ReservationService $reservations, FieldSelectionService $fieldSelections): View
    {
        $reservation = $links->validateToken($token);
        $this->expireIfOverdue($reservation, $links, $reservations);
        $reservation->refresh()->load(['student.phones', 'slot.advisor', 'advisor']);

        if (! $links->isAccessible($reservation)) {
            return view('public.reservation.blocked', ['message' => $links->getBlockedReasonMessage($reservation)]);
        }

        $visiblePlans = $fieldSelections->getStudentVisiblePlans($reservation);
        $plan = $request->filled('plan')
            ? $fieldSelections->getStudentVisiblePlanOrFail($reservation, FieldSelectionPlan::query()->findOrFail($request->integer('plan')))
            : $visiblePlans->first();
        $plan?->load('items');

        return view('public.field-selection.show', compact('reservation', 'plan', 'visiblePlans'));
    }

    public function showFieldSelection(string $token, FieldSelectionPlan $plan, PublicReservationLinkService $links, ReservationService $reservations, FieldSelectionService $fieldSelections): View
    {
        $reservation = $links->validateToken($token);
        $this->expireIfOverdue($reservation, $links, $reservations);

        if (! $links->isAccessible($reservation)) {
            return view('public.reservation.blocked', ['message' => $links->getBlockedReasonMessage($reservation)]);
        }

        $plan = $fieldSelections->getStudentVisiblePlanOrFail($reservation, $plan);
        $plan->load('items');
        $visiblePlans = $fieldSelections->getStudentVisiblePlans($reservation);

        return view('public.field-selection.show', compact('reservation', 'plan', 'visiblePlans'));
    }

    public function printFieldSelection(string $token, FieldSelectionPlan $plan, PublicReservationLinkService $links, ReservationService $reservations, FieldSelectionService $fieldSelections): View
    {
        $reservation = $links->validateToken($token);
        $this->expireIfOverdue($reservation, $links, $reservations);

        if (! $links->isAccessible($reservation)) {
            return view('public.reservation.blocked', ['message' => $links->getBlockedReasonMessage($reservation)]);
        }

        $plan = $fieldSelections->getStudentVisiblePlanOrFail($reservation, $plan);
        $plan->load(['items', 'student', 'reservation.slot.advisor', 'reservation.advisor']);
        $canViewStudentPersonalInfo = true;

        return view('field-selection.print', compact('plan', 'canViewStudentPersonalInfo'));
    }

    public function fieldSelectionPrint(string $token, Request $request, PublicReservationLinkService $links, ReservationService $reservations, FieldSelectionService $fieldSelections): View
    {
        $reservation = $links->validateToken($token);
        $this->expireIfOverdue($reservation, $links, $reservations);

        if (! $links->isAccessible($reservation)) {
            return view('public.reservation.blocked', ['message' => $links->getBlockedReasonMessage($reservation)]);
        }

        $visiblePlans = $fieldSelections->getStudentVisiblePlans($reservation);
        $plan = $request->filled('plan')
            ? $fieldSelections->getStudentVisiblePlanOrFail($reservation, FieldSelectionPlan::query()->findOrFail($request->integer('plan')))
            : $visiblePlans->first();

        $plan?->load(['items', 'student', 'reservation.slot.advisor', 'reservation.advisor']);
        $canViewStudentPersonalInfo = true;

        abort_unless($plan, 404);

        return view('field-selection.print', compact('plan', 'canViewStudentPersonalInfo'));
    }

    private function canUsePublicForm($reservation, PublicReservationLinkService $links): bool
    {
        return $links->isAccessible($reservation)
            && $reservation->status instanceof ReservationStatus
            && $reservation->status->allowsPublicUpdates();
    }

    private function canUploadReportCard($reservation, PublicReservationLinkService $links): bool
    {
        return $links->isAccessible($reservation)
            && $reservation->status instanceof ReservationStatus
            && ! in_array($reservation->status, [
                ReservationStatus::Expired,
                ReservationStatus::Cancelled,
                ReservationStatus::Completed,
                ReservationStatus::NoShow,
            ], true);
    }

    private function expireIfOverdue($reservation, PublicReservationLinkService $links, ReservationService $reservations): void
    {
        if (
            $reservation->payment_deadline_at
            && $reservation->payment_deadline_at->isPast()
            && $reservation->prepayment_required
            && $reservation->status instanceof ReservationStatus
            && in_array($reservation->status, [ReservationStatus::PendingCompletion, ReservationStatus::PendingPrepayment], true)
            && $reservation->payment?->status !== \App\Enums\PaymentStatus::Approved
        ) {
            $reservations->expire($reservation);
        }
    }
}
