<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Reservation;
use App\Models\User;

class ActivityLogService
{
    public function log(
        string $action,
        ?Reservation $reservation = null,
        ?User $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $user?->id,
            'reservation_id' => $reservation?->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }
}
