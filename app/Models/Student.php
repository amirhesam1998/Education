<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['full_name', 'major', 'region', 'score', 'exam_type'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    public const REGION_ONE = 'منطقه یک';
    public const REGION_TWO = 'منطقه دو';
    public const REGION_THREE = 'منطقه سه';
    public const REGION_QUOTA_5 = 'سهمیه 5 درصد';
    public const REGION_QUOTA_25 = 'سهمیه 25 درصد';

    /**
     * @return array<int, string>
     */
    public static function regionOptions(): array
    {
        return [
            self::REGION_ONE,
            self::REGION_TWO,
            self::REGION_THREE,
            self::REGION_QUOTA_5,
            self::REGION_QUOTA_25,
        ];
    }

    public function phones(): HasMany
    {
        return $this->hasMany(StudentPhone::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function primaryPhone(): ?StudentPhone
    {
        return $this->phones->firstWhere('is_primary', true) ?? $this->phones->first();
    }

    public function examTypeLabel(): string
    {
        $examTypes = $this->exam_type;

        if (is_string($examTypes)) {
            return $examTypes;
        }

        return collect($examTypes)->filter()->implode('، ') ?: '-';
    }

    protected function casts(): array
    {
        return [
            'exam_type' => 'array',
        ];
    }
}
