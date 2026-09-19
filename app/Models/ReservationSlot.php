<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Database\Factories\ReservationSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['advisor_id', 'date', 'start_time', 'end_time', 'duration_minutes', 'capacity', 'status', 'created_by'])]
class ReservationSlot extends Model
{
    /** @use HasFactory<ReservationSlotFactory> */
    use HasFactory;

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'slot_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(ReservationFollowUp::class, 'slot_id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'duration_minutes' => 'integer',
            'capacity' => 'integer',
            'status' => SlotStatus::class,
        ];
    }
}
