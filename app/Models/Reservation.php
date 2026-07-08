<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'student_id',
    'slot_id',
    'advisor_id',
    'reserved_start_time',
    'reserved_end_time',
    'status',
    'prepayment_required',
    'prepayment_amount',
    'payment_deadline_at',
    'public_token',
    'public_token_expires_at',
    'public_token_used_at',
    'confirmed_at',
    'cancelled_at',
    'expired_at',
    'completed_at',
    'created_by',
    'updated_by',
    'admin_note',
    'student_note',
])]
class Reservation extends Model
{
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ReservationSlot::class, 'slot_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(ReservationPayment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            ReservationStatus::Expired,
            ReservationStatus::Cancelled,
            ReservationStatus::Completed,
            ReservationStatus::NoShow,
        ], true);
    }

    public function hasCompleteStudentData(): bool
    {
        $student = $this->student;

        return filled($student?->full_name)
            && filled($student?->major)
            && filled($student?->score)
            && filled($student?->exam_type)
            && $student?->phones()->exists();
    }

    /**
     * @return array<int, string>
     */
    public function missingStudentFields(): array
    {
        $student = $this->student;
        $missing = [];

        foreach (['full_name', 'major', 'score', 'exam_type'] as $field) {
            if (blank($student?->{$field})) {
                $missing[] = $field;
            }
        }

        if (! $student?->phones()->exists()) {
            $missing[] = 'phone_one';
        }

        return $missing;
    }

    public function assignedStartTime(): ?string
    {
        return $this->reserved_start_time ?: $this->slot?->start_time;
    }

    public function assignedEndTime(): ?string
    {
        return $this->reserved_end_time ?: $this->slot?->end_time;
    }

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'prepayment_required' => 'boolean',
            'prepayment_amount' => 'integer',
            'payment_deadline_at' => 'datetime',
            'public_token_expires_at' => 'datetime',
            'public_token_used_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
