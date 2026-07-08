<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class PersianDate
{
    public static function date(DateTimeInterface|string|null $date): string
    {
        if ($date === null) {
            return '-';
        }

        $carbon = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date);

        [$year, $month, $day] = self::gregorianToJalali(
            (int) $carbon->format('Y'),
            (int) $carbon->format('m'),
            (int) $carbon->format('d'),
        );

        return self::number(sprintf('%04d/%02d/%02d', $year, $month, $day));
    }

    public static function dateTime(DateTimeInterface|string|null $date): string
    {
        if ($date === null) {
            return '-';
        }

        $carbon = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date);

        return self::date($carbon).' - '.self::number($carbon->format('H:i'));
    }

    public static function inputDate(DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        if ($date instanceof DateTimeInterface) {
            return self::date($date);
        }

        $normalized = self::normalizeDigits($date);

        if (self::isJalaliDate($normalized)) {
            return self::number(str_replace('-', '/', substr($normalized, 0, 10)));
        }

        try {
            return self::date($normalized);
        } catch (\Throwable) {
            return $date;
        }
    }

    public static function inputDateTime(DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        if ($date instanceof DateTimeInterface) {
            return self::date($date).' '.self::number($date->format('H:i'));
        }

        $normalized = self::normalizeDigits(str_replace('T', ' ', $date));

        if (preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})(?:\s+(\d{1,2}):(\d{2}))?$/', $normalized, $matches)) {
            if ((int) $matches[1] < 1700) {
                $time = isset($matches[4]) ? sprintf(' %02d:%02d', (int) $matches[4], (int) $matches[5]) : '';

                return self::number(sprintf('%04d/%02d/%02d%s', (int) $matches[1], (int) $matches[2], (int) $matches[3], $time));
            }
        }

        try {
            $carbon = Carbon::parse($normalized);

            return self::date($carbon).' '.self::number($carbon->format('H:i'));
        } catch (\Throwable) {
            return $date;
        }
    }

    public static function toGregorianDate(string|null $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        $normalized = self::normalizeDigits($date);

        if (! preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/', $normalized, $matches)) {
            return $date;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if ($year >= 1700) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        $gregorian = self::jalaliToGregorian($year, $month, $day);

        if ($gregorian === null) {
            return $date;
        }

        return sprintf('%04d-%02d-%02d', ...$gregorian);
    }

    public static function toGregorianDateTime(string|null $dateTime): ?string
    {
        if ($dateTime === null || trim($dateTime) === '') {
            return null;
        }

        $normalized = self::normalizeDigits(str_replace('T', ' ', $dateTime));

        if (! preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})(?:\s+(\d{1,2}):(\d{2}))?$/', $normalized, $matches)) {
            return $dateTime;
        }

        $hour = isset($matches[4]) ? (int) $matches[4] : 0;
        $minute = isset($matches[5]) ? (int) $matches[5] : 0;

        if ($hour > 23 || $minute > 59) {
            return $dateTime;
        }

        $gregorianDate = self::toGregorianDate(sprintf('%04d/%02d/%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3]));

        if ($gregorianDate === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $gregorianDate)) {
            return $dateTime;
        }

        return sprintf('%s %02d:%02d', $gregorianDate, $hour, $minute);
    }

    public static function time(string|null $time): string
    {
        if (! $time) {
            return '-';
        }

        return self::number(substr($time, 0, 5));
    }

    public static function number(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return strtr((string) $value, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
        ]);
    }

    public static function money(int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return self::number(number_format((int) $amount)).' تومان';
    }

    public static function normalizeDigits(string|int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(strtr((string) $value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]));
    }

    private static function isJalaliDate(string $date): bool
    {
        return (bool) preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}/', $date)
            && (int) substr($date, 0, 4) < 1700;
    }

    /**
     * @return array{0:int, 1:int, 2:int}
     */
    private static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $jDaysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        $gy -= 1600;
        $gm -= 1;
        $gd -= 1;

        $gDayNo = 365 * $gy + intdiv($gy + 3, 4) - intdiv($gy + 99, 100) + intdiv($gy + 399, 400);

        for ($i = 0; $i < $gm; $i++) {
            $gDayNo += $gDaysInMonth[$i];
        }

        if ($gm > 1 && (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0))) {
            $gDayNo++;
        }

        $gDayNo += $gd;
        $jDayNo = $gDayNo - 79;
        $jNp = intdiv($jDayNo, 12053);
        $jDayNo %= 12053;

        $jy = 979 + 33 * $jNp + 4 * intdiv($jDayNo, 1461);
        $jDayNo %= 1461;

        if ($jDayNo >= 366) {
            $jy += intdiv($jDayNo - 1, 365);
            $jDayNo = ($jDayNo - 1) % 365;
        }

        for ($i = 0; $i < 11 && $jDayNo >= $jDaysInMonth[$i]; $i++) {
            $jDayNo -= $jDaysInMonth[$i];
        }

        return [$jy, $i + 1, $jDayNo + 1];
    }

    /**
     * @return array{0:int, 1:int, 2:int}|null
     */
    private static function jalaliToGregorian(int $jy, int $jm, int $jd): ?array
    {
        if ($jm < 1 || $jm > 12 || $jd < 1 || $jd > self::jalaliMonthLength($jy, $jm)) {
            return null;
        }

        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv((($jy % 33) + 3), 4) + $jd;

        if ($jm < 7) {
            $days += ($jm - 1) * 31;
        } else {
            $days += (($jm - 7) * 30) + 186;
        }

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;

            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $gDaysInMonth = [0, 31, self::isGregorianLeapYear($gy) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        for ($gm = 1; $gm <= 12 && $gd > $gDaysInMonth[$gm]; $gm++) {
            $gd -= $gDaysInMonth[$gm];
        }

        return [$gy, $gm, $gd];
    }

    private static function jalaliMonthLength(int $year, int $month): int
    {
        if ($month <= 6) {
            return 31;
        }

        if ($month <= 11) {
            return 30;
        }

        return self::isJalaliLeapYear($year) ? 30 : 29;
    }

    private static function isJalaliLeapYear(int $year): bool
    {
        $mod = (($year - (($year > 0) ? 474 : 473)) % 2820) + 474;

        return ((($mod + 38) * 682) % 2816) < 682;
    }

    private static function isGregorianLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}
