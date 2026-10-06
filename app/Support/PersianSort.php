<?php

namespace App\Support;

use Illuminate\Support\Collection;

/** Persian alphabetical order (پ, چ, ژ, گ in their places), which a byte or Arabic collation gets wrong. */
class PersianSort
{
    private const ALPHABET = 'آاأإءبپتثجچحخدذرزژسشصضطظعغفقکگلمنوؤهیئ';

    public static function key(string $text): string
    {
        static $rank = null;
        if ($rank === null) {
            $rank = [];
            foreach (mb_str_split(self::ALPHABET) as $i => $char) {
                $rank[$char] = $i;
            }
        }

        $key = '';
        foreach (mb_str_split(strtr($text, ['ي' => 'ی', 'ك' => 'ک', "\u{200C}" => ' '])) as $char) {
            // Space first, then the alphabet, then anything else (digits, Latin) by code point.
            $key .= $char === ' ' ? '00' : (isset($rank[$char]) ? sprintf('%02d', $rank[$char] + 1) : '9'.$char);
        }

        return $key;
    }

    /** @param Collection<int, string> $values */
    public static function sort(Collection $values): Collection
    {
        return $values->sortBy(fn (string $value) => self::key($value), SORT_STRING)->values();
    }
}
