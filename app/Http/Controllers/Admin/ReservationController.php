<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelReservationRequest;
use App\Http\Requests\Admin\ChangeReservationSlotRequest;
use App\Http\Requests\Admin\StoreReservationRequest;
use App\Http\Requests\Admin\UpdateReservationRequest;
use App\Models\Advisor;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Services\PaymentApprovalService;
use App\Services\PublicReservationLinkService;
use App\Services\ReservationService;
use App\Services\SettingsService;
use App\Services\SlotAvailabilityService;
use App\Support\PersianDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $dateFilter = $request->filled('date')
            ? PersianDate::toGregorianDate($request->input('date'))
            : null;

        $reservations = Reservation::query()
            ->with(['student.phones', 'slot.advisor', 'payment'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('advisor_id'), fn ($query) => $query->where('advisor_id', $request->integer('advisor_id')))
            ->when($dateFilter, fn ($query) => $query->whereHas('slot', fn ($slot) => $slot->whereDate('date', $dateFilter)))
            ->when($request->filled('student_name'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('full_name', 'like', '%'.$request->string('student_name').'%')))
            ->when($request->filled('phone'), fn ($query) => $query->whereHas('student.phones', fn ($phone) => $phone->where('phone', 'like', '%'.$request->string('phone').'%')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'advisors' => Advisor::query()->orderBy('name')->get(),
            'statuses' => ReservationStatus::options(),
        ]);
    }

    public function create(SlotAvailabilityService $availability, SettingsService $settings): View
    {
        $slots = ReservationSlot::query()
            ->with('advisor')
            ->where('status', SlotStatus::Active)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('admin.reservations.create', [
            'reservation' => new Reservation(),
            'availableSlots' => $slots,
            'slotIntervals' => $this->slotIntervals($slots, $availability, $settings->reservationDurationMinutes()),
            'examTypes' => $settings->get('exam_types', []),
            'majors' => $settings->get('majors', []),
            'defaultPrepaymentAmount' => $settings->get('default_prepayment_amount', null),
            'defaultDeadlineHours' => $settings->get('default_payment_deadline_hours', 24),
            'reservationDurationMinutes' => $settings->reservationDurationMinutes(),
        ]);
    }

    public function store(StoreReservationRequest $request, ReservationService $reservations): RedirectResponse
    {
        $reservation = $reservations->createFromAdmin($request->validated());

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', 'رزرو با موفقیت ثبت شد و لینک امن ساخته شد.');
    }

    public function show(Reservation $reservation, SlotAvailabilityService $availability): View
    {
        $reservation->load(['student.phones', 'slot.advisor', 'payment.approver', 'activityLogs.user']);

        return view('admin.reservations.show', [
            'reservation' => $reservation,
            'availableSlots' => $availability->getAvailableSlots(),
            'publicUrl' => route('public.reservations.show', $reservation->public_token),
        ]);
    }

    public function edit(Reservation $reservation, SettingsService $settings, SlotAvailabilityService $availability): View
    {
        $reservation->load(['student.phones', 'payment', 'slot.advisor']);

        return view('admin.reservations.edit', [
            'reservation' => $reservation,
            'examTypes' => $settings->get('exam_types', []),
            'majors' => $settings->get('majors', []),
            'slotIntervals' => $reservation->slot
                ? $this->slotIntervals(collect([$reservation->slot]), $availability, $settings->reservationDurationMinutes(), $reservation)
                : [],
            'reservationDurationMinutes' => $settings->reservationDurationMinutes(),
        ]);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation, ReservationService $reservations): RedirectResponse
    {
        $reservations->updateFromAdmin($reservation, $request->validated());

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', 'رزرو بروزرسانی شد.');
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation, ReservationService $reservations): RedirectResponse
    {
        $reservations->cancel($reservation, $request->user(), $request->validated('reason'));

        return back()->with('success', 'رزرو لغو شد.');
    }

    public function changeSlot(ChangeReservationSlotRequest $request, Reservation $reservation, ReservationService $reservations): RedirectResponse
    {
        $slot = ReservationSlot::query()->findOrFail($request->validated('slot_id'));
        $reservations->changeSlot($reservation, $slot);

        return back()->with('success', 'تایم رزرو تغییر کرد.');
    }

    public function regenerateLink(Reservation $reservation, PublicReservationLinkService $links): RedirectResponse
    {
        $links->regenerate($reservation);

        return back()->with('success', 'لینک جدید ساخته شد.');
    }

    public function complete(Reservation $reservation, ReservationService $reservations): RedirectResponse
    {
        $reservations->markCompleted($reservation);

        return back()->with('success', 'رزرو انجام شده ثبت شد.');
    }

    public function noShow(Reservation $reservation, ReservationService $reservations): RedirectResponse
    {
        $reservations->markNoShow($reservation);

        return back()->with('success', 'عدم حضور ثبت شد.');
    }

    public function receipt(Reservation $reservation, PaymentApprovalService $payments)
    {
        abort_unless($reservation->payment, 404);

        return $payments->receiptResponse($reservation->payment);
    }

    private function slotIntervals($slots, SlotAvailabilityService $availability, int $durationMinutes, ?Reservation $ignoreReservation = null): array
    {
        return $slots
            ->mapWithKeys(fn (ReservationSlot $slot) => [
                $slot->id => $availability->generateIntervalsForSlot($slot, $durationMinutes, $ignoreReservation)
                    ->map(fn (array $interval) => [
                        'value' => $interval['value'],
                        'label' => $interval['label'],
                        'available' => $interval['available'],
                        'status_label' => $interval['status_label'],
                        'start_time' => $interval['start_time'],
                        'end_time' => $interval['end_time'],
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }
}
