<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A student's Azad university choices (انتخاب رشته آزاد), kept apart from the state plans. */
#[Fillable(['reservation_id', 'student_id', 'version', 'status', 'is_public_visible', 'student_visible_at', 'student_hidden_at', 'visibility_changed_by', 'created_by', 'updated_by', 'published_at'])]
class AzadSelectionPlan extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const MAX_ITEMS = 150;

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AzadSelectionItem::class)->orderBy('priority_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'منتشر شده',
            self::STATUS_ARCHIVED => 'آرشیو شده',
            default => 'پیش‌نویس',
        };
    }

    protected function casts(): array
    {
        return [
            'is_public_visible' => 'boolean',
            'published_at' => 'datetime',
            'student_visible_at' => 'datetime',
            'student_hidden_at' => 'datetime',
        ];
    }
}
