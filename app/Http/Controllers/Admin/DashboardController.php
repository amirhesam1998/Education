<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View
    {
        return view('admin.dashboard.index', [
            'stats' => $dashboard->getStats(),
            'creatorPaymentSummary' => $dashboard->getCreatorPaymentSummary(request()->user()),
            'todayReservations' => $dashboard->getTodayReservations(),
            'latestReservations' => $dashboard->getLatestReservations(),
        ]);
    }
}
