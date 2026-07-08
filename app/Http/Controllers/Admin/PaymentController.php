<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPaymentRequest;
use App\Models\ReservationPayment;
use App\Services\PaymentApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = ReservationPayment::query()
            ->with(['reservation.student.phones', 'reservation.slot.advisor'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when(! $request->filled('status'), fn ($query) => $query->where('status', PaymentStatus::PendingApproval))
            ->latest('uploaded_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::options(),
        ]);
    }

    public function show(ReservationPayment $payment): View
    {
        $payment->load(['reservation.student.phones', 'reservation.slot.advisor', 'approver']);

        return view('admin.payments.show', [
            'payment' => $payment,
        ]);
    }

    public function approve(ReservationPayment $payment, PaymentApprovalService $payments): RedirectResponse
    {
        $payments->approve($payment, request()->user());

        return back()->with('success', 'فیش تأیید شد و رزرو نهایی شد.');
    }

    public function reject(RejectPaymentRequest $request, ReservationPayment $payment, PaymentApprovalService $payments): RedirectResponse
    {
        $payments->reject($payment, $request->user(), $request->validated('rejection_reason'));

        return back()->with('success', 'فیش رد شد.');
    }

    public function receipt(ReservationPayment $payment, PaymentApprovalService $payments)
    {
        return $payments->receiptResponse($payment);
    }
}
