<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\SlotStatus;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\User;
use Illuminate\Support\Collection;

class SlotTimelineService
{
    public function __construct(
        private readonly SlotAvailabilityService $availability,
    ) {
    }

    /**
     * @param  array{advisor_id?:int|null, status?:string|null, availability?:string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function rowsForDate(ReservationSlot $selectedSlot, array $filters = [], ?User $user = null): Collection
    {
        $privacy = app(StudentPrivacyService::class);
        $canViewPersonalData = $user ? $privacy->canViewPersonalData($user) : true;
        $canViewPaymentInfo = $user ? $privacy->canViewPaymentInfo($user) : true;

        $slots = ReservationSlot::query()
            ->with([
                'advisor',
                'reservations' => fn ($query) => $query->with(['student.phones', 'payment'])->latest(),
            ])
            ->whereDate('date', $selectedSlot->date)
            ->when($filters['advisor_id'] ?? null, fn ($query, $advisorId) => $query->where('advisor_id', $advisorId))
            ->orderBy('start_time')
            ->orderBy('advisor_id')
            ->get();

        return $slots
            ->flatMap(fn (ReservationSlot $slot) => $this->rowsForSlot($slot, $selectedSlot, $canViewPersonalData, $canViewPaymentInfo))
            ->filter(fn (array $row) => $this->passesFilters($row, $filters))
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return [
            'available' => 'آزاد',
            'temporarily_locked' => 'رزرو موقت',
            'inactive' => 'غیرفعال',
            ReservationStatus::PendingCompletion->value => 'در انتظار تکمیل اطلاعات',
            ReservationStatus::PendingPrepayment->value => 'در انتظار پیش‌پرداخت',
            ReservationStatus::PendingPaymentApproval->value => 'در انتظار تأیید فیش',
            ReservationStatus::Confirmed->value => 'رزرو نهایی',
            ReservationStatus::PaymentRejected->value => 'رد شده',
            ReservationStatus::Expired->value => 'منقضی شده',
            ReservationStatus::Cancelled->value => 'لغو شده',
            ReservationStatus::Completed->value => 'انجام شده',
            ReservationStatus::NoShow->value => 'عدم حضور',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rowsForSlot(ReservationSlot $slot, ReservationSlot $selectedSlot, bool $canViewPersonalData, bool $canViewPaymentInfo): Collection
    {
        $intervals = $this->availability->generateIntervalsForSlot($slot);

        if ($intervals->isEmpty()) {
            return collect([$this->makeRow($slot, null, $selectedSlot, [
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => substr($slot->end_time, 0, 5),
            ], $canViewPersonalData, $canViewPaymentInfo)]);
        }

        return $intervals->map(function (array $interval) use ($slot, $selectedSlot): array {
            $reservation = $this->firstReservationForInterval($slot, $interval['start_time'], $interval['end_time'])
                ?? $interval['reservation'];

            return $this->makeRow($slot, $reservation, $selectedSlot, $interval, $canViewPersonalData, $canViewPaymentInfo);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function makeRow(ReservationSlot $slot, ?Reservation $reservation, ReservationSlot $selectedSlot, array $interval, bool $canViewPersonalData, bool $canViewPaymentInfo): array
    {
        $activeCount = $this->availability->countLoadedActiveReservations($slot);
        $remainingCapacity = $this->availability->remainingCapacity($slot);
        $isAvailable = (bool) ($interval['available'] ?? false);
        $reservationStatus = $reservation ? $this->reservationStatusMeta($reservation->status) : null;
        $startTime = $reservation?->assignedStartTime() ?: $interval['start_time'];
        $endTime = $reservation?->assignedEndTime() ?: $interval['end_time'];
        $intervalActiveCount = $reservation && $this->availability->isActiveReservation($reservation) ? 1 : 0;

        return [
            'slot' => $slot,
            'reservation' => $reservation,
            'time_start' => $startTime,
            'time_end' => $endTime,
            'advisor_name' => $slot->advisor?->name ?? '-',
            'student_name' => $canViewPersonalData ? ($reservation?->student?->full_name ?: '-') : ($reservation ? 'رزرو #'.$reservation->id : '-'),
            'student_phone' => $canViewPersonalData ? ($reservation?->student?->phones?->firstWhere('is_primary', true)?->phone
                ?? $reservation?->student?->phones?->first()?->phone
                ?? '-') : '-',
            'slot_status' => $this->slotStatusMeta($slot, $isAvailable, $intervalActiveCount),
            'reservation_status' => $reservationStatus,
            'payment_status' => $canViewPaymentInfo ? ($reservation?->payment?->status?->label() ?? '-') : '-',
            'active_reservations_count' => $activeCount,
            'remaining_capacity' => $remainingCapacity,
            'is_available' => $isAvailable,
            'is_reserved' => $reservation !== null,
            'is_selected' => (int) $slot->id === (int) $selectedSlot->id,
            'display_status_key' => $reservationStatus['key'] ?? $this->slotStatusMeta($slot, $isAvailable, $activeCount)['key'],
        ];
    }

    private function firstReservationForInterval(ReservationSlot $slot, string $startTime, string $endTime): ?Reservation
    {
        return $slot->reservations
            ->first(function (Reservation $reservation) use ($startTime, $endTime): bool {
                $reservationStart = substr((string) $reservation->assignedStartTime(), 0, 5);
                $reservationEnd = substr((string) $reservation->assignedEndTime(), 0, 5);

                return $reservationStart < $endTime && $reservationEnd > $startTime;
            });
    }

    /**
     * @return array{key:string, label:string, class:string}
     */
    private function slotStatusMeta(ReservationSlot $slot, bool $isAvailable, int $activeCount): array
    {
        if ($slot->status !== SlotStatus::Active) {
            return ['key' => 'inactive', 'label' => 'غیرفعال', 'class' => 'text-bg-secondary'];
        }

        if ($isAvailable) {
            return ['key' => 'available', 'label' => 'آزاد', 'class' => 'text-bg-success'];
        }

        if ($activeCount > 0) {
            return ['key' => 'temporarily_locked', 'label' => 'رزرو موقت', 'class' => 'text-bg-warning'];
        }

        return ['key' => 'temporarily_locked', 'label' => 'رزرو موقت', 'class' => 'text-bg-secondary'];
    }

    /**
     * @return array{key:string, label:string, class:string}
     */
    private function reservationStatusMeta(ReservationStatus $status): array
    {
        return match ($status) {
            ReservationStatus::PendingCompletion => ['key' => $status->value, 'label' => 'در انتظار تکمیل اطلاعات', 'class' => 'text-bg-warning'],
            ReservationStatus::PendingPrepayment => ['key' => $status->value, 'label' => 'در انتظار پیش‌پرداخت', 'class' => 'text-bg-warning'],
            ReservationStatus::PendingPaymentApproval => ['key' => $status->value, 'label' => 'در انتظار تأیید فیش', 'class' => 'text-bg-info'],
            ReservationStatus::Confirmed => ['key' => $status->value, 'label' => 'رزرو نهایی', 'class' => 'text-bg-success'],
            ReservationStatus::PaymentRejected => ['key' => $status->value, 'label' => 'رد شده', 'class' => 'text-bg-danger'],
            ReservationStatus::Expired => ['key' => $status->value, 'label' => 'منقضی شده', 'class' => 'text-bg-secondary'],
            ReservationStatus::Cancelled => ['key' => $status->value, 'label' => 'لغو شده', 'class' => 'text-bg-dark'],
            ReservationStatus::Completed => ['key' => $status->value, 'label' => 'انجام شده', 'class' => 'text-bg-primary'],
            ReservationStatus::NoShow => ['key' => $status->value, 'label' => 'عدم حضور', 'class' => 'text-bg-danger'],
            default => ['key' => $status->value, 'label' => $status->label(), 'class' => 'text-bg-light'],
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{advisor_id?:int|null, status?:string|null, availability?:string|null}  $filters
     */
    private function passesFilters(array $row, array $filters): bool
    {
        if (
            ($filters['status'] ?? null)
            && $row['display_status_key'] !== $filters['status']
            && $row['slot_status']['key'] !== $filters['status']
        ) {
            return false;
        }

        return match ($filters['availability'] ?? null) {
            'available' => $row['is_available'],
            'reserved' => $row['is_reserved'],
            default => true,
        };
    }
}
