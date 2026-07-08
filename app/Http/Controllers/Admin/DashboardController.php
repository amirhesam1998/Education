<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\ReservationSlot;
use App\Services\SlotAvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SlotAvailabilityService $availability): View
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $activeSlots = ReservationSlot::query()->where('status', 'active')->with('reservations')->get();

        return view('admin.dashboard.index', [
            'cards' => [
                'رزروهای امروز' => Reservation::query()->whereHas('slot', fn ($query) => $query->whereDate('date', $today))->count(),
                'رزروهای فردا' => Reservation::query()->whereHas('slot', fn ($query) => $query->whereDate('date', $tomorrow))->count(),
                'در انتظار تکمیل اطلاعات' => Reservation::query()->where('status', ReservationStatus::PendingCompletion)->count(),
                'در انتظار پرداخت' => Reservation::query()->where('status', ReservationStatus::PendingPrepayment)->count(),
                'در انتظار تأیید فیش' => Reservation::query()->where('status', ReservationStatus::PendingPaymentApproval)->count(),
                'تأیید شده' => Reservation::query()->where('status', ReservationStatus::Confirmed)->count(),
                'منقضی شده' => Reservation::query()->where('status', ReservationStatus::Expired)->count(),
                'تایمهای آزاد' => $activeSlots->filter(fn (ReservationSlot $slot) => $availability->isAvailable($slot))->count(),
                'فیشهای در انتظار تأیید' => ReservationPayment::query()->where('status', PaymentStatus::PendingApproval)->count(),
            ],
        ]);
    }
}
