<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadReportCardRequest;
use App\Http\Requests\Admin\CancelReservationRequest;
use App\Http\Requests\Admin\DisablePublicLinkRequest;
use App\Http\Requests\Admin\ChangeReservationSlotRequest;
use App\Http\Requests\Admin\StoreReservationFollowUpRequest;
use App\Http\Requests\Admin\StoreReservationRequest;
use App\Http\Requests\Admin\UpdateReservationRequest;
use App\Http\Requests\Admin\UpdateReservationExtraInfoRequest;
use App\Models\Advisor;
use App\Models\PaymentCard;
use App\Models\Reservation;
use App\Models\ReservationRequest;
use App\Models\ReservationDocument;
use App\Models\Student;
use App\Models\StudentPhone;
use App\Models\ReservationFollowUp;
use App\Models\ReservationSlot;
use App\Services\PaymentApprovalService;
use App\Services\PublicReservationLinkService;
use App\Services\ReservationService;
use App\Services\ReservationExtraInfoService;
use App\Services\ReservationRequestService;
use App\Services\ReservationDocumentService;
use App\Services\ReservationFollowUpService;
use App\Services\SettingsService;
use App\Services\SlotAvailabilityService;
use App\Services\StudentPrivacyService;
use App\Support\PersianDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $privacy = app(StudentPrivacyService::class);
        $canViewPersonalData = $privacy->canViewPersonalData($request->user());
        $canViewPaymentInfo = $privacy->canViewPaymentInfo($request->user());

        $dateFilter = $request->filled('date')
            ? PersianDate::toGregorianDate($request->input('date'))
            : null;

        $reservations = Reservation::query()
            ->with(array_filter([
                'student',
                $canViewPersonalData ? 'student.phones' : null,
                'slot.advisor',
                $canViewPaymentInfo ? 'payment' : null,
            ]))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('advisor_id'), fn ($query) => $query->where('advisor_id', $request->integer('advisor_id')))
            ->when($dateFilter, fn ($query) => $query->whereHas('slot', fn ($slot) => $slot->whereDate('date', $dateFilter)))
            ->when($request->filled('student_name'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('full_name', 'like', '%'.$request->string('student_name').'%')))
            ->when($canViewPersonalData && $request->filled('phone'), fn ($query) => $query->whereHas('student.phones', fn ($phone) => $phone->where('phone', 'like', '%'.$request->string('phone').'%')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'advisors' => Advisor::query()->selectableConsultants()->get(),
            'statuses' => ReservationStatus::options(),
            'canViewPersonalData' => $canViewPersonalData,
            'canViewPaymentInfo' => $canViewPaymentInfo,
        ]);
    }

    public function create(Request $request, SlotAvailabilityService $availability, SettingsService $settings): View
    {
        $slots = $this->bookableSlots();
        $sourceRequest = $request->integer('reservation_request')
            ? ReservationRequest::query()->findOrFail($request->integer('reservation_request'))
            : null;

        if ($sourceRequest) {
            abort_unless($request->user()->can('convert_reservation_requests'), 403);
            abort_unless($sourceRequest->isApproved(), 422, 'فقط درخواست تأییدشده قابل تبدیل به رزرو است.');
        }

        $reservation = new Reservation(['slot_id' => $request->integer('slot_id') ?: null]);

        if ($sourceRequest) {
            $student = new Student([
                'full_name' => $sourceRequest->full_name,
                'major' => $sourceRequest->major,
                'region' => in_array($sourceRequest->region, Student::regionOptions(), true) ? $sourceRequest->region : null,
                'score' => $sourceRequest->score,
                'exam_type' => $sourceRequest->exam_type,
            ]);
            $student->setRelation('phones', new Collection(array_filter([
                new StudentPhone(['phone' => $sourceRequest->phone_1, 'is_primary' => true]),
                $sourceRequest->phone_2 ? new StudentPhone(['phone' => $sourceRequest->phone_2, 'is_primary' => false]) : null,
            ])));
            $reservation->setRelation('student', $student);
            $reservation->admin_note = $sourceRequest->description;
        }

        return view('admin.reservations.create', [
            'reservation' => $reservation,
            'reservationRequest' => $sourceRequest,
            'availableSlots' => $slots,
            'slotIntervals' => $this->slotIntervals($slots, $availability),
            'slotDateGroups' => $availability->groupedIntervalsForSlots($slots),
            'examTypes' => $settings->get('exam_types', []),
            'majors' => $settings->get('majors', []),
            'regionOptions' => Student::regionOptions(),
            'defaultPrepaymentAmount' => $settings->get('default_prepayment_amount', null),
            'prepaymentPresets' => $settings->activePrepaymentAmountPresets(),
            'paymentCards' => PaymentCard::query()->where('is_active', true)->orderBy('bank_name')->get(),
            'defaultDeadlineHours' => $settings->get('default_payment_deadline_hours', 24),
        ]);
    }

    public function store(StoreReservationRequest $request, ReservationService $reservations, ReservationRequestService $reservationRequests): RedirectResponse
    {
        $data = $request->validated();
        $sourceRequestId = $data['reservation_request_id'] ?? null;
        $reservation = $sourceRequestId
            ? $this->convertReservationRequest($sourceRequestId, $data, $request, $reservationRequests)
            : $reservations->createFromAdmin($data);

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', 'رزرو با موفقیت ثبت شد و لینک امن ساخته شد.');
    }

    private function convertReservationRequest(int $requestId, array $data, Request $request, ReservationRequestService $reservationRequests): Reservation
    {
        abort_unless($request->user()->can('convert_reservation_requests'), 403);

        return $reservationRequests->convertToReservation(
            ReservationRequest::query()->findOrFail($requestId),
            $data,
            $request->user(),
        );
    }

    public function show(Reservation $reservation, SlotAvailabilityService $availability, StudentPrivacyService $privacy, SettingsService $settings): View
    {
        $user = request()->user();
        $canViewReservationSensitiveInfo = $privacy->canViewReservationSensitiveInfo($user);
        $canViewPersonalData = $privacy->canViewPersonalData($user);
        $canViewPaymentInfo = $privacy->canViewPaymentInfo($user);
        $canViewReceipt = $privacy->canViewReceipt($user);
        $canViewStudentPublicLink = $privacy->canViewStudentPublicLink($user);
        $canViewReservationDocuments = $user->can('view_reservation_documents');

        $reservation->load(array_filter([
            'student',
            $canViewPersonalData ? 'student.phones' : null,
            'slot.advisor',
            ($canViewPaymentInfo || $canViewReceipt) ? 'payment.approver' : null,
            $canViewPaymentInfo ? 'paymentCard' : null,
            $canViewReservationDocuments ? 'reportCards' : null,
            'activeFollowUp.slot.advisor',
            $canViewReservationSensitiveInfo ? 'activityLogs.user' : null,
            'fieldSelectionPlans.items',
            'fieldSelectionPlans.creator',
            'academicInfo',
        ]));
        $slots = $this->bookableSlots($reservation->slot);
        $followUp = $reservation->activeFollowUp;
        $followUpSlots = $this->bookableSlots($followUp?->slot);

        return view('admin.reservations.show', [
            'reservation' => $reservation,
            'activeFollowUp' => $followUp,
            'availableSlots' => $slots,
            'slotIntervals' => $this->slotIntervals($slots, $availability, $reservation),
            'slotDateGroups' => $availability->groupedIntervalsForSlots($slots, null, $reservation),
            'followUpSlotDateGroups' => $availability->groupedIntervalsForSlots($followUpSlots, null, null, $followUp),
            'canViewReservationSensitiveInfo' => $canViewReservationSensitiveInfo,
            'canViewPersonalData' => $canViewPersonalData,
            'canViewPaymentInfo' => $canViewPaymentInfo,
            'canViewReceipt' => $canViewReceipt,
            'canViewStudentPublicLink' => $canViewStudentPublicLink,
            'canViewReservationDocuments' => $canViewReservationDocuments,
            'educationalSummary' => $privacy->educationalSummary($reservation, $user),
            'publicUrl' => $canViewStudentPublicLink ? route('public.reservations.show', $reservation->public_token) : null,
            'majors' => $settings->get('majors', []),
        ]);
    }

    public function updateExtraInfo(UpdateReservationExtraInfoRequest $request, Reservation $reservation, ReservationExtraInfoService $extraInfo, StudentPrivacyService $privacy): RedirectResponse
    {
        $data = $request->validated();
        $canUpdatePhone = $privacy->canViewStudentContactData($request->user());

        if (! $canUpdatePhone) {
            unset($data['phone']);
        }

        $extraInfo->updateForReservation($reservation, $data, $request->user(), $canUpdatePhone);

        return back()->with('success', 'اطلاعات تکمیلی با موفقیت ذخیره شد.');
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
            'regionOptions' => Student::regionOptions(),
            'slotIntervals' => $this->slotIntervals($slots, $availability, $reservation),
            'slotDateGroups' => $availability->groupedIntervalsForSlots($slots, null, $reservation),
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
        abort_unless(app(StudentPrivacyService::class)->canViewStudentPublicLink(request()->user()), 403);
        $links->regenerate($reservation);

        return back()->with('success', 'لینک جدید ساخته شد.');
    }

    public function disablePublicLink(DisablePublicLinkRequest $request, Reservation $reservation, PublicReservationLinkService $links): RedirectResponse
    {
        abort_unless(app(StudentPrivacyService::class)->canViewStudentPublicLink($request->user()), 403);
        $links->disable($reservation, $request->user(), $request->validated('reason'));

        return back()->with('success', 'لینک عمومی رزرو موقتاً غیرفعال شد.');
    }

    public function enablePublicLink(Reservation $reservation, PublicReservationLinkService $links): RedirectResponse
    {
        abort_unless(app(StudentPrivacyService::class)->canViewStudentPublicLink(request()->user()), 403);
        $links->enable($reservation, request()->user());

        return back()->with('success', 'لینک عمومی رزرو فعال شد.');
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
        abort_unless(app(StudentPrivacyService::class)->canViewReceipt(request()->user()), 403);
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
        abort_unless(request()->user()->can('view_reservation_documents'), 403);
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

    private function slotIntervals($slots, SlotAvailabilityService $availability, ?Reservation $ignoreReservation = null, ?ReservationFollowUp $ignoreFollowUp = null): array
    {
        return $slots
            ->mapWithKeys(fn (ReservationSlot $slot) => [
                $slot->id => $availability->generateIntervalsForSlot($slot, null, $ignoreReservation, $ignoreFollowUp)
                    ->map(fn (array $interval) => [
                        'value' => $interval['value'],
                        'label' => $interval['label'],
                        'duration_minutes' => $interval['duration_minutes'],
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
