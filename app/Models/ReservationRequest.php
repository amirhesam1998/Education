<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'full_name', 'phone_1', 'phone_2', 'major', 'exam_type', 'region', 'special_quota', 'special_quota_other', 'score', 'region_quota',
    'description', 'preferred_date', 'preferred_time', 'status', 'reviewed_by', 'reviewed_at',
    'rejection_reason', 'converted_reservation_id',
])]
class ReservationRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';

    /** @return array<string, string> */
    public static function regionQuotaOptions(): array
    {
        return [
            'region_1' => 'منطقه یک',
            'region_2' => 'منطقه دو',
            'region_3' => 'منطقه سه',
            'quota_5' => 'سهمیه ۵ درصد',
            'quota_25' => 'سهمیه ۲۵ درصد',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function convertedReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'converted_reservation_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    protected function casts(): array
    {
        return [
            'exam_type' => 'array',
            'preferred_date' => 'date',
            'preferred_time' => 'datetime:H:i',
            'reviewed_at' => 'datetime',
        ];
    }
}
