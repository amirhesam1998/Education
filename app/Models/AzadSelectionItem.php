<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'azad_selection_plan_id',
    'azad_program_id',
    'priority_order',
    'booklet',
    'admission',
    'level',
    'unit_code',
    'unit_name',
    'field_code',
    'field_name',
    'part_time',
    'province',
    'city',
    'gender',
    'exam_group',
    'capacity_first',
    'capacity_second',
    'note',
])]
class AzadSelectionItem extends Model
{
    /** Programme columns copied into an item when it is added. */
    public const PROGRAM_FIELDS = [
        'booklet', 'admission', 'level', 'unit_code', 'unit_name', 'field_code', 'field_name', 'part_time',
        'province', 'city', 'gender', 'exam_group', 'capacity_first', 'capacity_second',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AzadSelectionPlan::class, 'azad_selection_plan_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AzadProgram::class, 'azad_program_id');
    }

    public function bookletLabel(): string
    {
        return AzadProgram::bookletLabel($this->booklet);
    }

    public function capacityText(): string
    {
        if ($this->admission !== 'exam') {
            return '';
        }

        $first = $this->capacity_first === null ? '-' : (string) $this->capacity_first;
        $second = $this->capacity_second === null ? '-' : (string) $this->capacity_second;

        return "نیمسال اول {$first} / نیمسال دوم {$second}";
    }

    protected function casts(): array
    {
        return [
            'part_time' => 'boolean',
            'capacity_first' => 'integer',
            'capacity_second' => 'integer',
            'priority_order' => 'integer',
        ];
    }
}
