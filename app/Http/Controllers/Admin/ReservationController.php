<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadReportCardRequest;
use App\Http\Requests\Admin\CancelReservationRequest;
use App\Http\Requests\Admin\ChangeReservationSlotRequest;
use App\Http\Requests\Admin\StoreReservationFollowUpRequest;
use App\Http\Requests\Admin\StoreReservationRequest;
use App\Http\Requests\Admin\UpdateReservationRequest;
use App\Models\Advisor;
use App\Models\PaymentCard;
use App\Models\Reservation;
use App\Models\ReservationDocument;
use App\Models\ReservationFollowUp;
use App\Models\ReservationSlot;
use App\Services\PaymentApprovalService;
use App\Services\PublicReservationLinkService;
use App\Services\ReservationService;
use App\Services\ReservationDocumentService;
use App\Services\ReservationFollowUpService;
use App\Services\SettingsService;
use App\Services\SlotAvailabilityService;
use App\Support\PersianDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $dateFilter = $request->filled('date')
            ? PersianDate::toGregorianDate($request->input('date'))
            : null;

        $reservations = Reservation::query()
            ->with(['student.phones', 'slot.advisor', 'payment', 'paymentCard'])
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
            'advisors' => Advisor::query()->selectableConsultants()->get(),
            'statuses' => ReservationStatus::options(),
        ]);
    }

    public function create(Request $request, SlotAvailabilityService $availability, SettingsService $settings): View
    {
        $slots = $this->bookableSlots();

        return view('admin.reservations.create', [
            'reservation' => new Reservation(['slot_id' => $request->integer('slot_id') ?: null]),
            'availableSlots' => $slots,
            'slotIntervals' => $this->slotIntervals($slots, $availability, $settings->reservationDurationMinutes()),
            'slotDateGroups' => $availability->groupedIntervalsForSlots($slots, $settings->reservationDurationMinutes()),
            'examTypes' => $settings->get('exam_types', []),
            'majors' => $settings->get('majors', []),
            'defaultPrepaymentAmount' => $settings->get('default_prepayment_amount', null),
            'prepaymentPresets' => $settings->activePrepaymentAmountPresets(),
            'paymentCards' => PaymentCard::query()->where('is_active', true)->orderBy('bank_name')->get(),
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
        $reservation->load(['student.phones', 'slot.advisor', 'payment.approver', 'paymentCard', 'reportCards', 'activeFollowUp.slot.advisor', 'activityLogs.user']);
        $slots = $this->bookableSlots($reservation->slot);
        $followUp = $reservation->activeFollowUp;
        $followUpSlots = $this->bookableSlots($followUp?->slot);
        $duration = app(SettingsService::class)->reservationDurationMinutes();

        return view('admin.reservations.show', [
            'reservation' => $reservation,
            'activeFollowUp' => $followUp,
            'availableSlots' => $slots,
            'slotIntervals' => $this->slotIntervals($slots, $availability, $duration, $reservation),
            'slotDateGroups' => $availability->groupedIntervalsForSlots($slots, $duration, $reservation),
            'followUpSlotDateGroups' => $availability->groupedIntervalsForSlots($followUpSlots, $duration, null, $followUp),
            'reservationDurationMinutes' => $duration,
            'publicUrl' => route('public.reservations.show', $reservation->public_token),
        ]);
    }

    public function edit(Reservation $reservation, SettingsService $settings, SlotAvailabilityService $availability): View
    {
        $reservation->load(['student.phones', 'payment', 'paymentCard', 'slot.advisor']);
        $slots = $this->bookableSlots($reservation->slot);

        return view('admin.reservations.edit', [
            'reservation' => $reservation,
            'availableSlots' => $slots,
            'examTypes' => $settings->get('exam_types', []),
            'majors' => $settings->get('majors', []),
            'slotIntervals' => $this->slotIntervals($slots, $availability, $settings->reservationDurationMinutes(), $reservation),
            'slotDateGroups' => $availability->groupedIntervalsForSlots($slots, $settings->reservationDurationMinutes(), $reservation),
            'prepaymentPresets' => $settings->activePrepaymentAmountPresets(),
            'paymentCards' => PaymentCard::query()
                ->where(function ($query) use ($reservation): void {
                    $query->where('is_active', true);

                    if ($reservation->payment_card_id) {
                        $query->orWhere('id', $reservation->payment_card_id);
                    }
                })
                ->orderBy('bank_name')
                ->get(),
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
        $reservations->changeTime(
            $reservation,
            $slot,
            $request->validated('reserved_start_time'),
            $request->validated('reserved_end_time'),
        );

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

    public function uploadReportCard(UploadReportCardRequest $request, Reservation $reservation, ReservationDocumentService $documents): RedirectResponse
    {
        $documents->uploadReportCardFromAdmin($reservation, $request->file('report_card'), $request->user());

        return back()->with('success', 'کارنامه دانش‌آموز ثبت شد.');
    }

    public function document(Reservation $reservation, ReservationDocument $document)
    {
        abort_unless((int) $document->reservation_id === (int) $reservation->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function storeFollowUp(StoreReservationFollowUpRequest $request, Reservation $reservation, ReservationFollowUpService $followUps): RedirectResponse
    {
        $followUps->createOrUpdateFollowUp($reservation, $request->validated(), $request->user());

        return back()->with('success', 'تایم مراجعه بعدی ذخیره شد.');
    }

    public function destroyFollowUp(Reservation $reservation, ReservationFollowUp $followUp, ReservationFollowUpService $followUps): RedirectResponse
    {
        abort_unless((int) $followUp->reservation_id === (int) $reservation->id, 404);

        $followUps->deleteFollowUp($followUp, request()->user());

        return back()->with('success', 'تایم مراجعه بعدی حذف شد.');
    }

    private function slotIntervals($slots, SlotAvailabilityService $availability, int $durationMinutes, ?Reservation $ignoreReservation = null, ?ReservationFollowUp $ignoreFollowUp = null): array
    {
        return $slots
            ->mapWithKeys(fn (ReservationSlot $slot) => [
                $slot->id => $availability->generateIntervalsForSlot($slot, $durationMinutes, $ignoreReservation, $ignoreFollowUp)
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

    private function bookableSlots(?ReservationSlot $include = null)
    {
        return ReservationSlot::query()
            ->with('advisor')
            ->where(function ($query) use ($include): void {
                $query->where('status', SlotStatus::Active)
                    ->whereDate('date', '>=', Carbon::today());

                if ($include) {
                    $query->orWhere($include->getKeyName(), $include->getKey());
                }
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }
}
