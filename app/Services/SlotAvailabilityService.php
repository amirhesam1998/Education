<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\SlotStatus;
use App\Models\Advisor;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Support\PersianDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SlotAvailabilityService
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function isAvailable(ReservationSlot $slot): bool
    {
        if ($slot->status !== SlotStatus::Active) {
            return false;
        }

        return $this->countActiveReservations($slot) < $slot->capacity;
    }

    public function assertAvailable(ReservationSlot $slot): void
    {
        if (! $this->isAvailable($slot)) {
            throw ValidationException::withMessages([
                'slot_id' => 'این تایم در حال حاضر قابل رزرو نیست.',
            ]);
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function generateIntervalsForDate(string $date, ?int $advisorId = null): Collection
    {
        $slots = ReservationSlot::query()
            ->with('advisor')
            ->where('status', SlotStatus::Active)
            ->whereDate('date', $date)
            ->when($advisorId, fn (Builder $query) => $query->where('advisor_id', $advisorId))
            ->orderBy('start_time')
            ->get();

        return $this->groupedIntervalsForSlots($slots);
    }

    /**
     * @param  iterable<int, ReservationSlot>  $slots
     * @return Collection<int, array<string, mixed>>
     */
    public function groupedIntervalsForSlots(iterable $slots, ?int $durationMinutes = null, ?Reservation $ignoreReservation = null): Collection
    {
        $durationMinutes ??= $this->settings->reservationDurationMinutes();

        return collect($slots)
            ->groupBy(fn (ReservationSlot $slot) => $slot->date->toDateString())
            ->sortKeys()
            ->map(function (Collection $dateSlots, string $date) use ($durationMinutes, $ignoreReservation): array {
                $intervals = $dateSlots
                    ->sortBy('start_time')
                    ->flatMap(fn (ReservationSlot $slot) => $this
                        ->generateIntervalsForSlot($slot, $durationMinutes, $ignoreReservation)
                        ->map(fn (array $interval) => [
                            'slot_id' => $slot->id,
                            'start_time' => $interval['start_time'],
                            'end_time' => $interval['end_time'],
                            'value' => $interval['value'],
                            'label' => PersianDate::time($interval['start_time']).' تا '.PersianDate::time($interval['end_time']),
                            'slot_range' => PersianDate::time($slot->start_time).' تا '.PersianDate::time($slot->end_time),
                            'advisor_name' => $slot->advisor?->name,
                            'status' => $interval['status_key'],
                            'status_label' => $interval['status_label'],
                            'available' => $interval['available'],
                            'is_available' => $interval['available'],
                        ]))
                    ->values();

                $dateCarbon = Carbon::parse($date);

                return [
                    'date' => $date,
                    'jalali_date' => PersianDate::date($date),
                    'weekday_label' => $this->weekdayLabel($dateCarbon),
                    'advisor_label' => $dateSlots->pluck('advisor.name')->filter()->unique()->implode('، '),
                    'total_count' => $intervals->count(),
                    'available_count' => $intervals->where('available', true)->count(),
                    'reserved_count' => $intervals->where('available', false)->count(),
                    'locked_count' => $intervals->where('status', 'locked')->count(),
                    'intervals' => $intervals->all(),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function generateIntervalsForSlot(ReservationSlot $slot, int $durationMinutes, ?Reservation $ignoreReservation = null): Collection
    {
        $durationMinutes = max(1, $durationMinutes);
        $date = $slot->date->toDateString();
        $cursor = Carbon::parse($date.' '.$this->normalizeTime($slot->start_time));
        $slotEnd = Carbon::parse($date.' '.$this->normalizeTime($slot->end_time));
        $intervals = collect();

        while ($cursor->copy()->addMinutes($durationMinutes)->lte($slotEnd)) {
            $startTime = $cursor->format('H:i');
            $endTime = $cursor->copy()->addMinutes($durationMinutes)->format('H:i');
            $overlappingReservation = $this->firstOverlappingActiveReservation($slot, $startTime, $endTime, $ignoreReservation);
            $available = $slot->status === SlotStatus::Active && $overlappingReservation === null;

            $intervals->push([
                'start_time' => $startTime,
                'end_time' => $endTime,
                'value' => $startTime.'|'.$endTime,
                'label' => $startTime.' تا '.$endTime,
                'available' => $available,
                'status_key' => $available ? 'available' : ($overlappingReservation?->status->value ?? 'locked'),
                'status_label' => $available ? 'آزاد' : $this->intervalUnavailableLabel($overlappingReservation),
                'reservation' => $overlappingReservation,
            ]);

            $cursor->addMinutes($durationMinutes);
        }

        return $intervals;
    }

    public function isIntervalAvailable(ReservationSlot $slot, string $startTime, string $endTime, ?Reservation $ignoreReservation = null): bool
    {
        return $slot->status === SlotStatus::Active
            && $this->intervalIsInsideSlot($slot, $startTime, $endTime)
            && $this->firstOverlappingActiveReservation($slot, $startTime, $endTime, $ignoreReservation) === null;
    }

    public function assertIntervalAvailable(ReservationSlot $slot, string $startTime, string $endTime, ?Reservation $ignoreReservation = null): void
    {
        if (! $this->intervalIsInsideSlot($slot, $startTime, $endTime)) {
            throw ValidationException::withMessages([
                'reservation_interval' => 'زمان اختصاص داده شده باید داخل بازه کلی تایم باشد.',
            ]);
        }

        if (! $this->isIntervalAvailable($slot, $startTime, $endTime, $ignoreReservation)) {
            throw ValidationException::withMessages([
                'reservation_interval' => 'این بازه قبلاً رزرو شده است.',
            ]);
        }
    }

    /**
     * @return Collection<int, ReservationSlot>
     */
    public function getAvailableSlots(?Advisor $advisor = null, ?string $date = null): Collection
    {
        return ReservationSlot::query()
            ->with('advisor')
            ->where('status', SlotStatus::Active)
            ->when($advisor, fn (Builder $query) => $query->where('advisor_id', $advisor->id))
            ->when($date, fn (Builder $query) => $query->whereDate('date', $date))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (ReservationSlot $slot) => $this->isAvailable($slot))
            ->values();
    }

    public function countActiveReservations(ReservationSlot $slot): int
    {
        return Reservation::query()
            ->where('slot_id', $slot->id)
            ->whereIn('status', $this->activeReservationStatuses())
            ->count();
    }

    public function countLoadedActiveReservations(ReservationSlot $slot): int
    {
        if (! $slot->relationLoaded('reservations')) {
            return $this->countActiveReservations($slot);
        }

        return $slot->reservations
            ->filter(fn (Reservation $reservation) => $this->isActiveReservation($reservation))
            ->count();
    }

    public function remainingCapacity(ReservationSlot $slot): int
    {
        return max(0, $slot->capacity - $this->countLoadedActiveReservations($slot));
    }

    public function isActiveReservation(Reservation $reservation): bool
    {
        return in_array($reservation->status->value, $this->activeReservationStatuses(), true);
    }

    /**
     * @return array<int, string>
     */
    public function activeReservationStatuses(): array
    {
        return ReservationStatus::activeValues(
            includePaymentRejected: ! $this->releaseSlotAfterPaymentRejection()
        );
    }

    public function releaseSlotAfterPaymentRejection(): bool
    {
        return (bool) $this->settings->get('release_slot_after_payment_rejection', true);
    }

    private function firstOverlappingActiveReservation(ReservationSlot $slot, string $startTime, string $endTime, ?Reservation $ignoreReservation = null): ?Reservation
    {
        $startTime = $this->normalizeTime($startTime);
        $endTime = $this->normalizeTime($endTime);

        return Reservation::query()
            ->with(['student.phones', 'payment', 'slot.advisor'])
            ->whereIn('status', $this->activeReservationStatuses())
            ->where('reserved_start_time', '<', $endTime)
            ->where('reserved_end_time', '>', $startTime)
            ->when($ignoreReservation, fn (Builder $query) => $query->whereKeyNot($ignoreReservation->id))
            ->where(function (Builder $query) use ($slot): void {
                $query->where('slot_id', $slot->id)
                    ->orWhere(function (Builder $query) use ($slot): void {
                        $query->where('advisor_id', $slot->advisor_id)
                            ->whereHas('slot', fn (Builder $slotQuery) => $slotQuery->whereDate('date', $slot->date));
                    });
            })
            ->oldest('reserved_start_time')
            ->first();
    }

    private function intervalIsInsideSlot(ReservationSlot $slot, string $startTime, string $endTime): bool
    {
        $startTime = $this->normalizeTime($startTime);
        $endTime = $this->normalizeTime($endTime);
        $slotStart = $this->normalizeTime($slot->start_time);
        $slotEnd = $this->normalizeTime($slot->end_time);

        return $startTime >= $slotStart
            && $endTime <= $slotEnd
            && $startTime < $endTime;
    }

    private function intervalUnavailableLabel(?Reservation $reservation): string
    {
        if (! $reservation) {
            return 'رزرو موقت';
        }

        return match ($reservation->status) {
            ReservationStatus::PendingCompletion => 'در انتظار تکمیل اطلاعات',
            ReservationStatus::PendingPrepayment => 'در انتظار پیش‌پرداخت',
            ReservationStatus::PendingPaymentApproval => 'در انتظار تأیید فیش',
            ReservationStatus::Confirmed => 'رزرو نهایی',
            ReservationStatus::PaymentRejected => 'رد شده',
            default => $reservation->status->label(),
        };
    }

    private function normalizeTime(?string $time): string
    {
        return substr((string) $time, 0, 5);
    }

    private function weekdayLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'امروز';
        }

        if ($date->isTomorrow()) {
            return 'فردا';
        }

        return ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'][$date->dayOfWeek];
    }
}
