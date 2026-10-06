<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Islamic Azad University programme (رشته‌محل آزاد): a field at a unit (واحد / مرکز),
 * identified in the booklets by کد محل دانشگاهی + کد رشته تحصیلی.
 */
#[Fillable([
    'year',
    'booklet',
    'admission',
    'level',
    'province',
    'city',
    'unit_code',
    'unit_name',
    'self_funded',
    'field_code',
    'field_name',
    'part_time',
    'gender',
    'exam_group',
    'education_group',
    'capacity_first',
    'capacity_second',
    'booklet_page',
    'search_text',
    'source_type',
    'source_hash',
    'is_active',
    'created_by',
    'updated_by',
])]
class AzadProgram extends Model
{
    public const SOURCE_BOOKLET = 'booklet';
    public const SOURCE_MANUAL = 'manual';

    /** Booklet key => [label, admission, level]; the order is the booklets' own. */
    public const BOOKLETS = [
        'sarasari' => ['با آزمون سراسری', 'exam', null],
        'kardani_napeyvaste' => ['کاردانی ناپیوسته - سوابق تحصیلی', 'records', 'کاردانی ناپیوسته'],
        'kardani_peyvaste' => ['کاردانی پیوسته - سوابق تحصیلی', 'records', 'کاردانی پیوسته'],
        'karshenasi_napeyvaste' => ['کارشناسی ناپیوسته - سوابق تحصیلی', 'records', 'کارشناسی ناپیوسته'],
        'karshenasi_peyvaste' => ['کارشناسی پیوسته - سوابق تحصیلی', 'records', 'کارشناسی پیوسته'],
    ];

    public const ADMISSIONS = [
        'exam' => 'با آزمون سراسری',
        'records' => 'بر اساس سوابق تحصیلی',
    ];

    /** گروه آزمایشی / گروه آموزشی, as the Azad booklets name them. */
    public const GROUPS = [
        'tajrobi' => 'علوم تجربی',
        'riazi' => 'علوم ریاضی و فنی',
        'ensani' => 'علوم انسانی',
        'honar' => 'هنر',
        'zaban' => 'زبان خارجی',
    ];

    public const GENDERS = ['زن و مرد', 'زن', 'مرد'];

    public const ABROAD = 'برون مرزی';

    public static function bookletLabel(?string $booklet): string
    {
        return self::BOOKLETS[$booklet][0] ?? (string) $booklet;
    }

    public static function groupLabel(?string $group): ?string
    {
        return $group === null ? null : (self::GROUPS[$group] ?? $group);
    }

    /** Text the catalogue search matches against: names and places in their lookup form. */
    public static function searchText(array $attributes): string
    {
        return self::lookup(implode(' ', [
            $attributes['unit_name'] ?? '',
            $attributes['field_name'] ?? '',
            $attributes['city'] ?? '',
            $attributes['province'] ?? '',
        ]));
    }

    /** One spelling for searching: Persian letters, no ZWNJ (a space instead), single spaces. */
    public static function lookup(?string $text): string
    {
        $text = strtr((string) $text, [
            'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه', 'ـ' => '',
            "\u{200C}" => ' ', "\u{200B}" => ' ', "\u{200D}" => ' ', "\u{00A0}" => ' ',
            "\u{200E}" => '', "\u{200F}" => '',
        ]);

        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }

    public static function asciiDigits(?string $text): string
    {
        return strtr((string) $text, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getBookletLabelAttribute(): string
    {
        return self::bookletLabel($this->booklet);
    }

    /** Capacity as printed: «-» for a half-year without admission; records-based rows print none. */
    public function capacityText(?int $value): string
    {
        if ($this->admission !== 'exam') {
            return '';
        }

        return $value === null ? '-' : (string) $value;
    }

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'self_funded' => 'boolean',
            'part_time' => 'boolean',
            'capacity_first' => 'integer',
            'capacity_second' => 'integer',
            'booklet_page' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
