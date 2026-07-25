<?php

namespace App\Services\StudyPrograms;

class PersianTextNormalizer
{
    public function normalize(?string $value): string
    {
        $value = trim((string) $value);
        $value = strtr($value, [
            'ي' => 'ی',
            'ى' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'ۀ' => 'ه',
            'ـ' => '',
            "\u{00A0}" => ' ',
            "\u{200B}" => ' ',
            "\u{200C}" => ' ',
            "\u{200D}" => ' ',
            "\u{200E}" => '',
            "\u{200F}" => '',
            "\u{202A}" => '',
            "\u{202B}" => '',
            "\u{202C}" => '',
            "\u{202D}" => '',
            "\u{202E}" => '',
        ]);
        $value = preg_replace('/\s*([،؛:])\s*/u', '$1 ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public function lookup(?string $value): string
    {
        return mb_strtolower($this->normalize($value));
    }

    public function nullable(?string $value): ?string
    {
        $value = $this->normalize($value);

        return $value === '' || $value === '-' ? null : $value;
    }
}
