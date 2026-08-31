<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['field_selection_plan_id', 'priority_order', 'field_code', 'field_name', 'field_description', 'city', 'university_name', 'university_type', 'university_description'])]
class FieldSelectionItem extends Model
{
    public function plan(): BelongsTo
    {
        return $this->belongsTo(FieldSelectionPlan::class, 'field_selection_plan_id');
    }
}
