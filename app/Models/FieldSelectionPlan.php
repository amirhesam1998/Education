<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reservation_id', 'student_id', 'exam_type_key', 'version', 'status', 'is_public_visible', 'student_visible_at', 'student_hidden_at', 'visibility_changed_by', 'visibility_note', 'created_by', 'updated_by', 'published_at'])]
class FieldSelectionPlan extends Model
{
    use SoftDeletes;
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

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
        return $this->hasMany(FieldSelectionItem::class)->orderBy('priority_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function visibilityChanger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visibility_changed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function canBePubliclyVisible(): bool
    {
        return $this->isPublished();
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
