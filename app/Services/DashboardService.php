<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\SlotStatus;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\ReservationSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly SlotAvailabilityService $availability,
        private readonly SettingsService $settings,
    ) {
    }

    public function getStats(): array
    {
        $today = $this->today();
        $yesterday = $today->copy()->subDay();
        $thisWeekStart = $today->copy()->startOfWeek();
        $thisWeekEnd = $today->copy()->endOfWeek();
        $previousWeekStart = $thisWeekStart->copy()->subWeek();
        $previousWeekEnd = $thisWeekEnd->copy()->subWeek();

        $todayReservations = $this->countReservationsForDate($today);
        $yesterdayReservations = $this->countReservationsForDate($yesterday);
        $thisWeekAvailability = $this->countAvailableIntervalsBetween($thisWeekStart, $thisWeekEnd);
        $previousWeekAvailability = $this->countAvailableIntervalsBetween($previousWeekStart, $previousWeekEnd);
        $pendingPayments = $this->countPendingPaymentApprovals();
        $activeReservations = $this->countActiveReservations();

        return [
            'today_reservations' => [
                'value' => $todayReservations,
                'label' => 'رزرو امروز',
                'trend' => $this->comparisonText($todayReservations, $yesterdayReservations, 'دیروز'),
            ],
            'pending_payments' => [
                'value' => $pendingPayments,
                'label' => 'فیش در انتظار تایید',
                'trend' => [
                    'text' => $pendingPayments ? 'نیازمند بررسی آموزشگاه' : 'فیشی در انتظار تأیید نیست.',
                    'tone' => $pendingPayments ? 'down' : 'neutral',
                    'icon' => $pendingPayments ? 'ri-error-warning-line' : 'ri-checkbox-circle-line',
                ],
            ],
            'available_intervals' => [
                'value' => $thisWeekAvailability,
                'label' => 'تایم خالی این هفته',
                'trend' => $this->comparisonText($thisWeekAvailability, $previousWeekAvailability, 'هفته قبل'),
            ],
            'active_reservations' => [
                'value' => $activeReservations,
                'label' => 'رزروهای فعال',
                'trend' => [
                    'text' => 'بر اساس رزروهای در جریان',
                    'tone' => 'neutral',
                    'icon' => 'ri-information-line',
                ],
            ],
        ];
    }

    public function getTodayReservations(): Collection
    {
        return Reservation::query()
            ->with(['student.phones', 'slot.advisor', 'advisor'])
            ->whereHas('slot', fn ($query) => $query->whereDate('date', $this->today()))
            ->whereIn('status', [
                ...ReservationStatus::activeValues(),
                ReservationStatus::Completed->value,
            ])
            ->orderBy('reserved_start_time')
            ->get();
    }

    public function getLatestReservations(int $limit = 10): Collection
    {
        return Reservation::query()
            ->with(['student.phones', 'slot.advisor', 'advisor'])
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function countTodayReservations(): int
    {
        return $this->countReservationsForDate($this->today());
    }

    public function countPendingPaymentApprovals(): int
    {
        return ReservationPayment::query()
            ->where('status', PaymentStatus::PendingApproval)
            ->count();
    }

    public function countAvailableIntervalsThisWeek(): int
    {
        $today = $this->today();

        return $this->countAvailableIntervalsBetween($today->copy()->startOfWeek(), $today->copy()->endOfWeek());
    }

    public function countActiveReservations(): int
    {
        return Reservation::query()
            ->whereIn('status', ReservationStatus::activeValues())
            ->count();
    }

    private function countReservationsForDate(Carbon $date): int
    {
        return Reservation::query()
            ->whereHas('slot', fn ($query) => $query->whereDate('date', $date->toDateString()))
            ->count();
    }

    private function countAvailableIntervalsBetween(Carbon $from, Carbon $to): int
    {
        $duration = $this->settings->reservationDurationMinutes();

        return ReservationSlot::query()
            ->with('advisor')
            ->where('status', SlotStatus::Active)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->sum(fn (ReservationSlot $slot) => $this->availability
                ->generateIntervalsForSlot($slot, $duration)
                ->where('available', true)
                ->count());
    }

    private function comparisonText(int $current, int $previous, string $period): array
    {
        $diff = $current - $previous;

        if ($diff === 0) {
            return [
                'text' => 'بدون تغییر نسبت به '.$period,
                'tone' => 'neutral',
                'icon' => 'ri-subtract-line',
            ];
        }

        return [
            'text' => abs($diff).' مورد '.($diff > 0 ? 'بیشتر' : 'کمتر').' از '.$period,
            'tone' => $diff > 0 ? 'up' : 'down',
            'icon' => $diff > 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line',
        ];
    }

    private function today(): Carbon
    {
        return Carbon::today(config('app.timezone'));
    }
}
