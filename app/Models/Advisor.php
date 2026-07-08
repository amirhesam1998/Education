<?php

namespace App\Models;

use Database\Factories\AdvisorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'description', 'status'])]
class Advisor extends Model
{
    /** @use HasFactory<AdvisorFactory> */
    use HasFactory;

    public function slots(): HasMany
    {
        return $this->hasMany(ReservationSlot::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
