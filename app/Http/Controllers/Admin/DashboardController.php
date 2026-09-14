<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\OperatorFieldSelectionStatsService;
use App\Services\StudentPrivacyService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard, StudentPrivacyService $privacy, OperatorFieldSelectionStatsService $operatorStats): View
    {
        $user = request()->user();

        return view('admin.dashboard.index', [
            'stats' => $dashboard->getStats(),
            'creatorPaymentSummary' => $privacy->canViewPaymentInfo($user) ? $dashboard->getCreatorPaymentSummary($user) : [],
            'completedConsultationStats' => $dashboard->getCompletedConsultationStatsForCreator($user),
            'operatorFieldSelectionStats' => $user->can('view_operator_field_selection_stats') ? $operatorStats->getSummaryForUser($user) : [],
            'pendingReservationRequests' => $user->can('view_reservation_requests') ? $dashboard->countPendingReservationRequests() : 0,
            'todayReservations' => $dashboard->getTodayReservations(),
            'latestReservations' => $dashboard->getLatestReservations(),
            'canViewPersonalData' => $privacy->canViewPersonalData($user),
            'canViewPaymentInfo' => $privacy->canViewPaymentInfo($user),
        ]);
    }
}
