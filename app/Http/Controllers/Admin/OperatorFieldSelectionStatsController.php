<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OperatorFieldSelectionStatsRequest;
use App\Models\User;
use App\Services\OperatorFieldSelectionStatsService;
use Illuminate\View\View;

class OperatorFieldSelectionStatsController extends Controller
{
    public function index(OperatorFieldSelectionStatsRequest $request, OperatorFieldSelectionStatsService $stats): View
    {
        $filters = $request->validated();
        $summary = $stats->getSummaryForUser($request->user(), $filters);

        return view('admin.reports.operator-field-selection.index', compact('filters', 'summary'));
    }

    public function show(OperatorFieldSelectionStatsRequest $request, User $operator, OperatorFieldSelectionStatsService $stats): View
    {
        return view('admin.reports.operator-field-selection.show', [
            'operator' => $operator,
            'filters' => $request->validated(),
            'activities' => $stats->getOperatorDetails($request->user(), $operator, $request->validated()),
        ]);
    }
}
