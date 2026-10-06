<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'year',
    'programs_sha256',
    'status',
    'total_rows',
    'inserted_rows',
    'updated_rows',
    'unchanged_rows',
    'kept_manual_rows',
    'removed_rows',
    'error_message',
    'started_at',
    'finished_at',
])]
class AzadProgramImport extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
