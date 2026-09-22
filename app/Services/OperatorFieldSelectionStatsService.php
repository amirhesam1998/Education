<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FieldSelectionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperatorFieldSelectionStatsService
{
    private const CREATED_ACTIONS = ['field_selection_plan_created', 'field_selection_new_version_created'];

    private const EDIT_ACTIONS = [
        'field_selection_item_added',
        'field_selection_item_updated',
        'field_selection_item_deleted',
        'field_selection_reordered',
        'field_selection_plan_updated',
    ];

    /** @return array<int, array{operator_id:int,operator_name:string,created_plans_count:int,edit_actions_count:int,last_activity_at:?string}> */
    public function getSummaryForUser(User $viewer, array $filters = []): array
    {
        return $this->activityQuery($viewer, $filters)
            ->selectRaw('user_id, sum(case when action in (\''.implode("','", self::CREATED_ACTIONS).'\') then 1 else 0 end) as created_plans_count')
            ->selectRaw('sum(case when action in (\''.implode("','", self::EDIT_ACTIONS).'\') then 1 else 0 end) as edit_actions_count')
            ->selectRaw('max(created_at) as last_activity_at')
            ->with('user:id,name')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn (ActivityLog $activity) => [
                'operator_id' => (int) $activity->user_id,
                'operator_name' => $activity->user?->name ?: '-',
                'created_plans_count' => (int) $activity->created_plans_count,
                'edit_actions_count' => (int) $activity->edit_actions_count,
                'last_activity_at' => $activity->last_activity_at,
            ])
            ->all();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function getOperatorDetails(User $viewer, User $operator, array $filters = []): Collection
    {
        $activities = $this->activityQuery($viewer, $filters)
            ->with(['reservation.student'])
            ->where('user_id', $operator->id)
            ->latest()
            ->get();
        $plans = FieldSelectionPlan::withTrashed()
            ->whereIn('id', $activities->map(fn (ActivityLog $activity) => $activity->new_values['plan_id'] ?? $activity->old_values['plan_id'] ?? null)->filter())
            ->get()
            ->keyBy('id');

        return $activities->map(function (ActivityLog $activity) use ($plans): array {
            $planId = $activity->new_values['plan_id'] ?? $activity->old_values['plan_id'] ?? null;
            $plan = $planId ? $plans->get($planId) : null;

            return [
                'action' => $activity->action,
                'action_label' => in_array($activity->action, self::CREATED_ACTIONS, true) ? 'ایجاد انتخاب رشته' : 'ویرایش انتخاب رشته',
                'reservation_id' => $activity->reservation_id,
                'student_name' => $activity->reservation?->student?->full_name ?: '-',
                'exam_type_label' => $this->examTypeLabel($activity->new_values['exam_type_key'] ?? $plan?->exam_type_key),
                'plan_id' => $planId,
                'created_at' => $activity->created_at,
            ];
        });
    }

    /**
     * What this one user did themselves — never other people's work.
     *
     * Deliberately independent of activityQuery(): that one scopes by who
     * *created the reservation*, which is the right rule for the
     * all-operators report but the wrong one for "what did I do".
     *
     * @return array{students_count:int, edit_actions_count:int}
     */
    public function getOwnSummary(User $user): array
    {
        $ownActivity = fn (array $actions) => ActivityLog::query()
            ->where('user_id', $user->id)
            ->whereIn('action', $actions);

        // one student may hold several plans, so count each student once
        $studentsCount = $ownActivity([...self::CREATED_ACTIONS, ...self::EDIT_ACTIONS])
            ->join('reservations', 'activity_logs.reservation_id', '=', 'reservations.id')
            ->distinct()
            ->count('reservations.student_id');

        // every edit counts, so three edits on one plan in a day read as three
        $editActionsCount = $ownActivity(self::EDIT_ACTIONS)->count();

        return [
            'students_count' => (int) $studentsCount,
            'edit_actions_count' => (int) $editActionsCount,
        ];
    }

    public function canViewGlobalStats(User $viewer): bool
    {
        return $viewer->hasAnyRole(['Super Admin', 'Admin / Branch Manager']);
    }

    private function activityQuery(User $viewer, array $filters): Builder
    {
        $query = ActivityLog::query()
            ->whereIn('action', [...self::CREATED_ACTIONS, ...self::EDIT_ACTIONS]);

        // Non-admin viewers only see activity on reservations they created.
        if (! $this->canViewGlobalStats($viewer)) {
            $query->whereHas('reservation', fn (Builder $reservation) => $reservation->where('created_by', $viewer->id));
        }

        return $query
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['operator_id'] ?? null, fn (Builder $query, int $operatorId) => $query->where('user_id', $operatorId))
            ->when($filters['activity_type'] ?? null, fn (Builder $query, string $type) => $query->whereIn('action', $type === 'created' ? self::CREATED_ACTIONS : self::EDIT_ACTIONS))
            ->when($filters['exam_type'] ?? null, fn (Builder $query, string $examType) => $query->where('new_values->exam_type_key', $examType));
    }

    private function examTypeLabel(?string $key): string
    {
        return match ($key) {
            'riazi' => 'ریاضی',
            'tajrobi' => 'تجربی',
            'ensani' => 'انسانی',
            'zaban' => 'زبان',
            'honar' => 'هنر',
            default => $key ?: '-',
        };
    }
}
