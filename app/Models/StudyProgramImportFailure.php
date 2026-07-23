<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['import_id', 'sheet_name', 'source_row', 'code', 'reason', 'raw_data'])]
class StudyProgramImportFailure extends Model
{
    public function import(): BelongsTo
    {
        return $this->belongsTo(StudyProgramImport::class, 'import_id');
    }

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'source_row' => 'integer',
        ];
    }
}
