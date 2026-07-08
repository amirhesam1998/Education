<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSlotRequest;
use App\Http\Requests\Admin\UpdateSlotRequest;
use App\Models\Advisor;
use App\Models\ReservationSlot;
use App\Services\SlotAvailabilityService;
use App\Services\SlotTimelineService;
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

        $slots = ReservationSlot::query()
            ->with('advisor')
            ->when($dateFilter, fn ($query) => $query->whereDate('date', $dateFilter))
            ->when($request->filled('advisor_id'), fn ($query) => $query->where('advisor_id', $request->integer('advisor_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        return view('admin.slots.index', [
            'slots' => $slots,
            'advisors' => Advisor::query()->orderBy('name')->get(),
            'statuses' => SlotStatus::options(),
            'availability' => $availability,
        ]);
    }

    public function create(): View
    {
        return view('admin.slots.create', [
            'slot' => new ReservationSlot(['capacity' => 1, 'status' => SlotStatus::Active]),
            'advisors' => Advisor::query()->where('status', 'active')->orderBy('name')->get(),
            'statuses' => SlotStatus::options(),
        ]);
    }

    public function show(Request $request, ReservationSlot $slot, SlotAvailabilityService $availability, SlotTimelineService $timeline): View
    {
        $slot->load(['advisor', 'reservations.student.phones', 'reservations.payment']);

        $filters = [
            'advisor_id' => $request->filled('advisor_id') ? $request->integer('advisor_id') : null,
            'status' => $request->filled('status') ? (string) $request->string('status') : null,
            'availability' => $request->filled('availability') ? (string) $request->string('availability') : null,
        ];

        return view('admin.slots.show', [
            'slot' => $slot,
            'advisors' => Advisor::query()->orderBy('name')->get(),
            'activeReservationsCount' => $availability->countLoadedActiveReservations($slot),
            'remainingCapacity' => $availability->remainingCapacity($slot),
            'timelineRows' => $timeline->rowsForDate($slot, $filters),
            'statusOptions' => $timeline->statusOptions(),
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

    public function edit(ReservationSlot $slot): View
    {
        return view('admin.slots.edit', [
            'slot' => $slot,
            'advisors' => Advisor::query()->orderBy('name')->get(),
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
        if ($availability->countActiveReservations($slot) > 0) {
            return back()->withErrors(['slot' => 'این تایم رزرو فعال دارد و قابل حذف نیست.']);
        }

        $slot->delete();

        return redirect()
            ->route('admin.slots.index')
            ->with('success', 'تایم حذف شد.');
    }

    private function createSingleSlot(array $data): int
    {
        ReservationSlot::query()->create([
            'advisor_id' => $data['advisor_id'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'capacity' => $data['capacity'],
            'status' => $data['status'],
            'created_by' => auth()->id(),
        ]);

        return 1;
    }

    private function createRepeatedSlots(array $data): int
    {
        $created = 0;
        $date = Carbon::parse($data['repeat_start_date']);
        $endDate = Carbon::parse($data['repeat_end_date']);

        while ($date->lte($endDate)) {
            $cursor = Carbon::parse($date->toDateString().' '.$data['daily_start_time']);
            $dayEnd = Carbon::parse($date->toDateString().' '.$data['daily_end_time']);

            while ($cursor->copy()->addMinutes((int) $data['interval_minutes'])->lte($dayEnd)) {
                $slotEnd = $cursor->copy()->addMinutes((int) $data['interval_minutes']);

                ReservationSlot::query()->create([
                    'advisor_id' => $data['advisor_id'],
                    'date' => $date->toDateString(),
                    'start_time' => $cursor->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
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
}
