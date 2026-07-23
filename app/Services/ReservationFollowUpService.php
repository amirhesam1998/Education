<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationFollowUp;
use App\Models\ReservationSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReservationFollowUpService
{
    public function __construct(private readonly SlotAvailabilityService $availability)
    {
    }

    public function createOrUpdateFollowUp(Reservation $reservation, array $data, User $user): ReservationFollowUp
    {
        return DB::transaction(function () use ($reservation, $data, $user): ReservationFollowUp {
            $slot = ReservationSlot::query()->whereKey($data['slot_id'])->lockForUpdate()->firstOrFail();
            $followUp = $reservation->activeFollowUp()->lockForUpdate()->first();

            $this->availability->assertIntervalAvailableForFollowUp(
                $slot,
                $data['reserved_start_time'],
                $data['reserved_end_time'],
                $followUp,
            );

            $values = [
                'reservation_id' => $reservation->id,
                'slot_id' => $slot->id,
                'advisor_id' => $slot->advisor_id,
                'follow_up_date' => $slot->date->toDateString(),
                'reserved_start_time' => $data['reserved_start_time'],
                'reserved_end_time' => $data['reserved_end_time'],
                'status' => ReservationFollowUp::STATUS_SCHEDULED,
                'created_by' => $followUp?->created_by ?? $user->id,
                'updated_by' => $user->id,
                'note' => $data['note'] ?? null,
            ];

            if ($followUp) {
                $followUp->fill($values)->save();

                return $followUp->load(['slot.advisor', 'advisor']);
            }

            return ReservationFollowUp::query()->create($values)->load(['slot.advisor', 'advisor']);
        });
    }

    public function deleteFollowUp(ReservationFollowUp $followUp, User $user): void
    {
        $followUp->forceFill([
            'status' => ReservationFollowUp::STATUS_CANCELLED,
            'updated_by' => $user->id,
        ])->save();
    }
}
