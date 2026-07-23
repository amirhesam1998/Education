<?php

namespace App\Services\StudyPrograms;

class StudyProgramValueMapper
{
    public const EXAM_GROUPS = [
        'tajrobi' => 'تجربی',
        'riazi' => 'ریاضی',
        'ensani' => 'انسانی',
        'honar' => 'هنر',
        'zaban' => 'زبان',
    ];

    private const EXAM_GROUP_ALIASES = [
        'tajrobi' => ['تجربی', 'علوم تجربی'],
        'riazi' => ['ریاضی', 'ریاضی و فنی'],
        'ensani' => ['انسانی', 'علوم انسانی'],
        'honar' => ['هنر'],
        'zaban' => ['زبان', 'زبان‌های خارجی', 'زبان های خارجی'],
    ];

    public const COURSE_TYPES = [
        'day' => 'روزانه',
        'evening' => 'نوبت دوم',
        'commitment' => 'تعهد خدمت',
        'tuition' => 'شهریه‌پرداز',
        'nonprofit' => 'غیرانتفاعی',
        'payame_noor' => 'پیام نور',
        'virtual' => 'مجازی',
        'joint' => 'مشترک',
        'other' => 'سایر',
    ];

    public const ADMISSION_TYPES = [
        'with_exam' => 'با آزمون',
        'academic_records' => 'سوابق تحصیلی',
        'special_conditions' => 'شرایط خاص',
        'unknown' => 'نامشخص',
    ];

    public const OFFICIAL_PROVINCES = [
        'آذربایجان شرقی', 'آذربایجان غربی', 'اردبیل', 'اصفهان', 'البرز', 'ایلام', 'بوشهر', 'تهران',
        'چهارمحال و بختیاری', 'خراسان جنوبی', 'خراسان رضوی', 'خراسان شمالی', 'خوزستان', 'زنجان',
        'سمنان', 'سیستان و بلوچستان', 'فارس', 'قزوین', 'قم', 'کردستان', 'کرمان', 'کرمانشاه',
        'کهگیلویه و بویراحمد', 'گلستان', 'گیلان', 'لرستان', 'مازندران', 'مرکزی', 'هرمزگان', 'همدان', 'یزد',
    ];

    public function __construct(private readonly PersianTextNormalizer $normalizer)
    {
    }

    public function examGroupFromFilename(string $filename): ?string
    {
        $filename = strtolower($filename);

        foreach (array_keys(self::EXAM_GROUPS) as $slug) {
            if (str_contains($filename, $slug)) {
                return $slug;
            }
        }

        return null;
    }

    public function examGroupSlug(?string $value): ?string
    {
        $lookup = $this->normalizer->lookup($value);

        foreach (self::EXAM_GROUP_ALIASES as $slug => $aliases) {
            foreach ([$slug, ...$aliases] as $alias) {
                if ($lookup === $this->normalizer->lookup($alias)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    public function validProvince(?string $value): ?string
    {
        $lookup = $this->normalizer->lookup($value);

        foreach (self::OFFICIAL_PROVINCES as $province) {
            if ($lookup === $this->normalizer->lookup($province)) {
                return $province;
            }
        }

        return null;
    }

    public function courseTypeName(string $slug): string
    {
        return self::COURSE_TYPES[$slug] ?? self::COURSE_TYPES['other'];
    }

    public function admissionTypeName(string $slug): string
    {
        return self::ADMISSION_TYPES[$slug] ?? self::ADMISSION_TYPES['unknown'];
    }
}
