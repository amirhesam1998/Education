<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['year', 'is_active'])]
class ExamYear extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
