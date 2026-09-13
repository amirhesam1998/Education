<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSlotRequest;
use App\Http\Requests\Admin\UpdateSlotRequest;
use App\Http\Requests\Admin\BulkSlotDayRequest;
use App\Models\Advisor;
use App\Models\ReservationSlot;
use App\Services\SlotAvailabilityService;
use App\Services\SlotTimelineService;
use App\Services\StudentPrivacyService;
use App\Support\PersianDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SlotController extends Controller
{
    public function index(Request $request, SlotAvailabilityService $availability): View
    {
        $dateFilter = $request->filled('date')
            ? PersianDate::toGregorianDate($request->input('date'))
            : null;

        return view('admin.slots.index', [
            'slotDateGroups' => $availability->getGroupedScheduleForAdmin([
                'date' => $dateFilter,
                'advisor_id' => $request->filled('advisor_id') ? $request->integer('advisor_id') : null,
                'status' => $request->filled('status') ? (string) $request->string('status') : null,
                'availability' => $request->filled('availability') ? (string) $request->string('availability') : null,
            ]),
            'advisors' => $this->consultantOptions(),
            'statuses' => SlotStatus::options(),
            'availabilityOptions' => [
                'available' => 'نوبت آزاد',
                'reserved' => 'نوبت رزرو شده',
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.slots.create', [
            'slot' => new ReservationSlot(['capacity' => 1, 'duration_minutes' => 15, 'status' => SlotStatus::Active]),
            'advisors' => $this->consultantOptions(),
            'statuses' => SlotStatus::options(),
        ]);
    }

    public function show(Request $request, ReservationSlot $slot, SlotAvailabilityService $availability, SlotTimelineService $timeline, StudentPrivacyService $privacy): View
    {
        $canViewPersonalData = $privacy->canViewPersonalData($request->user());
        $canViewPaymentInfo = $privacy->canViewPaymentInfo($request->user());
        $slot->load(array_filter(['advisor', 'reservations.student', $canViewPersonalData ? 'reservations.student.phones' : null, $canViewPaymentInfo ? 'reservations.payment' : null]));

        $filters = [
            'advisor_id' => $request->filled('advisor_id') ? $request->integer('advisor_id') : null,
            'status' => $request->filled('status') ? (string) $request->string('status') : null,
            'availability' => $request->filled('availability') ? (string) $request->string('availability') : null,
        ];

        return view('admin.slots.show', [
            'slot' => $slot,
            'advisors' => $this->consultantOptions($slot),
            'activeReservationsCount' => $availability->countLoadedActiveReservations($slot),
            'remainingCapacity' => $availability->remainingCapacity($slot),
            'timelineRows' => $timeline->rowsForDate($slot, $filters, $request->user()),
            'statusOptions' => $timeline->statusOptions(),
            'canViewPersonalData' => $canViewPersonalData,
            'canViewPaymentInfo' => $canViewPaymentInfo,
        ]);
    }

    public function store(StoreSlotRequest $request): RedirectResponse
    {
        $created = DB::transaction(fn () => $request->validated('mode') === 'repeat'
            ? $this->createRepeatedSlots($request->validated())
            : $this->createSingleSlot($request->validated()));

        return redirect()
            ->route('admin.slots.index')
            ->with('success', "تعداد {$created} تایم با موفقیت ثبت شد.");
    }

    public function edit(ReservationSlot $slot, SlotAvailabilityService $availability): View
    {
        return view('admin.slots.edit', [
            'slot' => $slot,
            'activeReservationsCount' => $availability->countActiveReservations($slot),
            'advisors' => $this->consultantOptions($slot),
            'statuses' => SlotStatus::options(),
        ]);
    }

    public function update(UpdateSlotRequest $request, ReservationSlot $slot): RedirectResponse
    {
        $slot->update($request->validated());

        return redirect()
            ->route('admin.slots.index')
            ->with('success', 'تایم با موفقیت بروزرسانی شد.');
    }

    public function destroy(ReservationSlot $slot, SlotAvailabilityService $availability): RedirectResponse
    {
        if ($slot->reservations()->exists() || $slot->followUps()->exists()) {
            return back()->withErrors(['slot' => 'این تایم سابقه رزرو دارد و برای حفظ سوابق قابل حذف نیست.']);
        }

        $slot->delete();

        return redirect()
            ->route('admin.slots.index')
            ->with('success', 'تایم حذف شد.');
    }

    public function previewDayDeletion(BulkSlotDayRequest $request, SlotAvailabilityService $availability): \Illuminate\Http\JsonResponse
    {
        $summary = $availability->previewDayDeletion(
            $request->validated('date'),
            $request->validated('advisor_id'),
            $request->user(),
        );

        if ($summary['total'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'برای این تاریخ تایمی ثبت نشده است.',
                ...$summary,
            ], 422);
        }

        return response()->json(['success' => true, ...$summary]);
    }

    public function bulkDeleteDay(BulkSlotDayRequest $request, SlotAvailabilityService $availability): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $result = ($data['action'] ?? 'delete') === 'deactivate'
            ? $availability->deactivateSlotsForDay($data['date'], $data['advisor_id'] ?? null, $request->user())
            : $availability->deleteFreeSlotsForDay($data['date'], $data['advisor_id'] ?? null, $request->user());

        $deletedCount = $result['deleted'] ?? $result['deactivated'];
        if ($deletedCount === 0 && $result['skipped'] === 0) {
            $message = 'برای این تاریخ تایمی ثبت نشده است.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['date' => $message]);
        }

        $message = ($data['action'] ?? 'delete') === 'deactivate'
            ? 'تایم‌های آزاد این روز با موفقیت غیرفعال شدند.'
            : 'تایم‌های آزاد این روز با موفقیت حذف شدند.';
        $message .= $result['skipped'] ? ' برخی تایم‌های این روز دارای رزرو هستند و حذف نشدند.' : '';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'blocked_count' => $result['skipped'],
                'total_count' => $deletedCount + $result['skipped'],
            ]);
        }

        return redirect()->route('admin.slots.index')->with('success', $message);
    }

    private function createSingleSlot(array $data): int
    {
        ReservationSlot::query()->create([
            'advisor_id' => $data['advisor_id'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $data['duration_minutes'],
            'capacity' => $data['capacity'],
            'status' => $data['status'],
            'created_by' => auth()->id(),
        ]);

        return 1;
    }

    private function consultantOptions(?ReservationSlot $slot = null)
    {
        return Advisor::query()
            ->selectableConsultants($slot?->advisor)
            ->get();
    }

    private function createRepeatedSlots(array $data): int
    {
        $created = 0;
        $date = Carbon::parse($data['repeat_start_date']);
        $endDate = Carbon::parse($data['repeat_end_date']);

        while ($date->lte($endDate)) {
            $cursor = Carbon::parse($date->toDateString().' '.$data['daily_start_time']);
            $dayEnd = $this->timeOnDate($date->toDateString(), $data['daily_end_time'], true);

            while ($cursor->copy()->addMinutes((int) $data['interval_minutes'])->lte($dayEnd)) {
                $slotEnd = $cursor->copy()->addMinutes((int) $data['interval_minutes']);

                ReservationSlot::query()->create([
                    'advisor_id' => $data['advisor_id'],
                    'date' => $date->toDateString(),
                    'start_time' => $cursor->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'duration_minutes' => $data['duration_minutes'],
                    'capacity' => $data['capacity'],
                    'status' => $data['status'],
                    'created_by' => auth()->id(),
                ]);

                $created++;
                $cursor = $slotEnd;
            }

            $date->addDay();
        }

        return $created;
    }

    private function timeOnDate(string $date, string $time, bool $endBoundary = false): Carbon
    {
        $dateTime = Carbon::parse($date.' '.$time);

        return $endBoundary && $time === '00:00' ? $dateTime->addDay() : $dateTime;
    }
}
