<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'exam_year_id',
    'exam_group_id',
    'code',
    'source_file',
    'source_row',
    'source_hash',
    'identity_hash',
    'review_reason',
    'raw_data',
    'status',
    'approved_by',
    'approved_at',
    'rejected_by',
    'rejected_at',
    'review_note',
])]
class StudyProgramReviewRecord extends Model
{
    public function examYear(): BelongsTo { return $this->belongsTo(ExamYear::class); }
    public function examGroup(): BelongsTo { return $this->belongsTo(ExamGroup::class); }

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}

