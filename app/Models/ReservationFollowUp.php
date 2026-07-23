<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reservation_id',
    'slot_id',
    'advisor_id',
    'follow_up_date',
    'reserved_start_time',
    'reserved_end_time',
    'status',
    'created_by',
    'updated_by',
    'note',
])]
class ReservationFollowUp extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ReservationSlot::class);
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
        ];
    }
}
