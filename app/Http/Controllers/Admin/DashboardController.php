<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\StudentPrivacyService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard, StudentPrivacyService $privacy): View
    {
        $user = request()->user();

        return view('admin.dashboard.index', [
            'stats' => $dashboard->getStats(),
            'creatorPaymentSummary' => $privacy->canViewPaymentInfo($user) ? $dashboard->getCreatorPaymentSummary($user) : [],
            'completedConsultationStats' => $dashboard->getCompletedConsultationStatsForCreator($user),
            'todayReservations' => $dashboard->getTodayReservations(),
            'latestReservations' => $dashboard->getLatestReservations(),
            'canViewPersonalData' => $privacy->canViewPersonalData($user),
            'canViewPaymentInfo' => $privacy->canViewPaymentInfo($user),
        ]);
    }
}
