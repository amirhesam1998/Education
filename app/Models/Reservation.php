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
    'payment_card_id',
    'payment_deadline_at',
    'public_token',
    'public_token_expires_at',
    'public_token_used_at',
    'public_link_disabled_at',
    'public_link_disabled_by',
    'public_link_disabled_reason',
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

    public function paymentCard(): BelongsTo
    {
        return $this->belongsTo(PaymentCard::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ReservationDocument::class);
    }

    public function reportCards(): HasMany
    {
        return $this->documents()->where('type', ReservationDocument::TYPE_REPORT_CARD);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(ReservationFollowUp::class);
    }

    public function fieldSelectionPlans(): HasMany
    {
        return $this->hasMany(FieldSelectionPlan::class)->orderByDesc('version');
    }

    public function latestFieldSelectionPlan(): HasOne
    {
        return $this->hasOne(FieldSelectionPlan::class)->latestOfMany('version');
    }

    public function publicLinkDisabler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'public_link_disabled_by');
    }

    public function activeFollowUp(): HasOne
    {
        return $this->hasOne(ReservationFollowUp::class)->where('status', ReservationFollowUp::STATUS_SCHEDULED);
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
            && filled($student?->region)
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

        foreach (['full_name', 'major', 'region', 'score', 'exam_type'] as $field) {
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
            'payment_card_id' => 'integer',
            'payment_deadline_at' => 'datetime',
            'public_token_expires_at' => 'datetime',
            'public_token_used_at' => 'datetime',
            'public_link_disabled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
