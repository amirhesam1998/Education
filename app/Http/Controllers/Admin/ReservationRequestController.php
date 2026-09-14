<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectReservationRequest;
use App\Models\ReservationRequest;
use App\Services\ReservationRequestService;
use App\Services\SettingsService;
use App\Support\PersianDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationRequestController extends Controller
{
    public function index(Request $request, SettingsService $settings): View
    {
        $from = PersianDate::toGregorianDate($request->input('from'));
        $to = PersianDate::toGregorianDate($request->input('to'));

        $requests = ReservationRequest::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('phone'), fn ($query) => $query->where('phone_1', 'like', '%'.$request->string('phone').'%'))
            ->when($request->filled('full_name'), fn ($query) => $query->where('full_name', 'like', '%'.$request->string('full_name').'%'))
            ->when($request->filled('exam_type'), fn ($query) => $query->whereJsonContains('exam_type', $request->string('exam_type')))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservation-requests.index', [
            'requests' => $requests,
            'examTypes' => $settings->get('exam_types', ['تجربی', 'ریاضی', 'انسانی', 'هنر', 'زبان']),
        ]);
    }

    public function show(ReservationRequest $reservationRequest): View
    {
        $reservationRequest->load(['reviewer', 'convertedReservation']);

        return view('admin.reservation-requests.show', compact('reservationRequest'));
    }

    public function approve(ReservationRequest $reservationRequest, ReservationRequestService $requests): RedirectResponse
    {
        $requests->approve($reservationRequest, request()->user());

        return back()->with('success', 'درخواست رزرو تأیید شد.');
    }

    public function reject(RejectReservationRequest $request, ReservationRequest $reservationRequest, ReservationRequestService $requests): RedirectResponse
    {
        $requests->reject($reservationRequest, $request->user(), $request->validated('rejection_reason'));

        return back()->with('success', 'درخواست رزرو رد شد.');
    }

    public function convert(ReservationRequest $reservationRequest): RedirectResponse
    {
        if (! $reservationRequest->isApproved()) {
            return back()->withErrors(['status' => 'فقط درخواست تأییدشده قابل تبدیل به رزرو است.']);
        }

        return redirect()->route('admin.reservations.create', ['reservation_request' => $reservationRequest->id]);
    }
}
