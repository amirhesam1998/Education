<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'normalized_name'])]
class Province extends Model
{
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
