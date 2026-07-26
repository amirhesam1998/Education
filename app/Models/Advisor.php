<?php

namespace App\Models;

use Database\Factories\AdvisorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'phone', 'description', 'status'])]
class Advisor extends Model
{
    /** @use HasFactory<AdvisorFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ReservationSlot::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeSelectableConsultants(Builder $query, ?self $include = null): Builder
    {
        return $query
            ->with('user.roles')
            ->where(function (Builder $query) use ($include): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->where('status', 'active')
                        ->whereHas('user', fn (Builder $userQuery) => $userQuery->activeConsultants());
                });

                if ($include?->exists) {
                    $query->orWhere($include->getKeyName(), $include->getKey());
                }
            })
            ->orderBy('name');
    }

    public static function syncForUser(User $user): ?self
    {
        if (! $user->hasRole(User::ROLE_CONSULTANT)) {
            return null;
        }

        return self::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'phone' => $user->phone,
                'status' => $user->status,
            ],
        );
    }
}
