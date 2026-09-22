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

        // Someone holding nothing but the Operator role gets a stripped
        // dashboard: a greeting and their own two counters, nothing else.
        // The heavier queries are skipped rather than merely hidden.
        $operatorOnly = $user->roles->count() === 1 && $user->hasRole('Operator');

        $ownFieldSelectionSummary = $user->can('view_field_selection')
            ? $operatorStats->getOwnSummary($user)
            : null;

        if ($operatorOnly) {
            return view('admin.dashboard.index', [
                'operatorOnly' => true,
                'ownFieldSelectionSummary' => $ownFieldSelectionSummary,
            ]);
        }

        return view('admin.dashboard.index', [
            'operatorOnly' => false,
            'stats' => $dashboard->getStats(),
            'creatorPaymentSummary' => $privacy->canViewPaymentInfo($user) ? $dashboard->getCreatorPaymentSummary($user) : [],
            'completedConsultationStats' => $dashboard->getCompletedConsultationStatsForCreator($user),
            'operatorFieldSelectionStats' => $user->can('view_operator_field_selection_stats') ? $operatorStats->getSummaryForUser($user) : [],
            'ownFieldSelectionSummary' => $ownFieldSelectionSummary,
            'pendingReservationRequests' => $user->can('view_reservation_requests') ? $dashboard->countPendingReservationRequests() : 0,
            'todayReservations' => $dashboard->getTodayReservations(),
            'latestReservations' => $dashboard->getLatestReservations(),
            'canViewPersonalData' => $privacy->canViewPersonalData($user),
            'canViewPaymentInfo' => $privacy->canViewPaymentInfo($user),
        ]);
    }
}
