<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['institution_id', 'province_id', 'city_id', 'name', 'normalized_name'])]
class InstitutionCampus extends Model
{
    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function province(): BelongsTo { return $this->belongsTo(Province::class); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
}

