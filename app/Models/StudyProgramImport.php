<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'exam_year_id',
    'exam_group_id',
    'source_path',
    'source_filename',
    'file_hash',
    'status',
    'total_rows',
    'inserted_rows',
    'updated_rows',
    'unchanged_rows',
    'review_rows',
    'skipped_rows',
    'failed_rows',
    'started_at',
    'finished_at',
    'error_message',
    'metadata',
])]
class StudyProgramImport extends Model
{
    public function examYear(): BelongsTo { return $this->belongsTo(ExamYear::class); }
    public function examGroup(): BelongsTo { return $this->belongsTo(ExamGroup::class); }
    public function failures(): HasMany { return $this->hasMany(StudyProgramImportFailure::class, 'import_id'); }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
