<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPaymentRequest;
use App\Models\ReservationPayment;
use App\Services\PaymentApprovalService;
use App\Services\StudentPrivacyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $privacy = app(StudentPrivacyService::class);
        abort_unless($privacy->canViewPaymentInfo($request->user()), 403);
        $canViewPersonalData = $privacy->canViewPersonalData($request->user());

        $payments = ReservationPayment::query()
            ->with(array_filter(['reservation.student', $canViewPersonalData ? 'reservation.student.phones' : null, 'reservation.slot.advisor']))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when(! $request->filled('status'), fn ($query) => $query->where('status', PaymentStatus::PendingApproval))
            ->latest('uploaded_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::options(),
            'canViewPersonalData' => $canViewPersonalData,
        ]);
    }

    public function show(ReservationPayment $payment): View
    {
        $privacy = app(StudentPrivacyService::class);
        abort_unless($privacy->canViewPaymentInfo(request()->user()), 403);
        $canViewPersonalData = $privacy->canViewPersonalData(request()->user());
        $payment->load(array_filter(['reservation.student', $canViewPersonalData ? 'reservation.student.phones' : null, 'reservation.slot.advisor', 'approver']));

        return view('admin.payments.show', [
            'payment' => $payment,
            'canViewPersonalData' => $canViewPersonalData,
            'canViewReceipt' => $privacy->canViewReceipt(request()->user()),
        ]);
    }

    public function approve(ReservationPayment $payment, PaymentApprovalService $payments): RedirectResponse
    {
        abort_unless(app(StudentPrivacyService::class)->canViewReceipt(request()->user()), 403);
        $payments->approve($payment, request()->user());

        return back()->with('success', 'فیش تأیید شد و رزرو نهایی شد.');
    }

    public function reject(RejectPaymentRequest $request, ReservationPayment $payment, PaymentApprovalService $payments): RedirectResponse
    {
        abort_unless(app(StudentPrivacyService::class)->canViewReceipt($request->user()), 403);
        $payments->reject($payment, $request->user(), $request->validated('rejection_reason'));

        return back()->with('success', 'فیش رد شد.');
    }

    public function receipt(ReservationPayment $payment, PaymentApprovalService $payments)
    {
        abort_unless(app(StudentPrivacyService::class)->canViewReceipt(request()->user()), 403);
        return $payments->receiptResponse($payment);
    }
}
