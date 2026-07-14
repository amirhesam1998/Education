<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reservation_id',
    'type',
    'uploaded_by_type',
    'uploaded_by_id',
    'source',
    'file_path',
    'original_name',
    'mime_type',
    'size',
])]
class ReservationDocument extends Model
{
    public const TYPE_REPORT_CARD = 'report_card';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_STUDENT = 'student';

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function sourceLabel(): string
    {
        return $this->source === self::SOURCE_ADMIN
            ? 'ثبت شده توسط آموزشگاه'
            : 'ثبت شده توسط دانش‌آموز';
    }
}
