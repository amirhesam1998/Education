<?php

namespace App\Models;

use App\Support\PersianDate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['holder_name', 'card_number', 'bank_name', 'description', 'is_active'])]
class PaymentCard extends Model
{
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function formattedNumber(): string
    {
        $digits = preg_replace('/\D+/', '', $this->card_number) ?: $this->card_number;

        return PersianDate::number(implode(' - ', str_split($digits, 4)));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
