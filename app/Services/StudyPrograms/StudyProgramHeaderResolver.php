<?php

namespace App\Services\StudyPrograms;

use RuntimeException;

class StudyProgramHeaderResolver
{
    public const V2 = [
        'row_index' => ['ردیف'],
        'code' => ['کدرشته محل'],
        'exam_group' => ['گروه آزمایشی'],
        'province' => ['استان'],
        'city' => ['شهر'],
        'course_type_name' => ['نوع دوره'],
        'course_type_slug' => ['شناسه نوع دوره'],
        'original_course_type' => ['دوره درج‌شده در دفترچه', 'دوره درج شده در دفترچه'],
        'academic_field' => ['رشته'],
        'institution' => ['دانشگاه / مؤسسه', 'دانشگاه/مؤسسه'],
        'campus' => ['دانشکده / محل تحصیل', 'دانشکده/محل تحصیل'],
        'admission_type_name' => ['نحوه پذیرش'],
        'admission_type_slug' => ['شناسه نحوه پذیرش'],
        'accepts_male' => ['جنس پذیرش - مرد'],
        'accepts_female' => ['جنس پذیرش - زن'],
        'first_capacity' => ['ظرفیت نیمسال اول'],
        'second_capacity' => ['ظرفیت نیمسال دوم'],
        'description' => ['توضیحات'],
        'booklet_page' => ['صفحه دفترچه'],
        'booklet_section' => ['بخش دفترچه'],
        'city_detection_method' => ['روش تشخیص شهر'],
        'source_file' => ['فایل منبع'],
        'validation_status' => ['وضعیت کنترل'],
        'review_reason' => ['موارد نیازمند بررسی'],
        'source_hash' => ['هش منبع'],
    ];

    public const REFERENCE = [
        'province' => ['استان'],
        'city' => ['شهر'],
        'institution' => ['دانشگاه / مؤسسه', 'دانشگاه/مؤسسه'],
        'campus' => ['دانشکده / محل تحصیل', 'دانشکده/محل تحصیل'],
    ];

    public function __construct(private readonly PersianTextNormalizer $normalizer)
    {
    }

    public function resolve(array $headers, array $aliases, array $required): array
    {
        $normalized = array_map(fn ($header) => $this->normalizer->lookup((string) $header), $headers);
        $map = [];

        foreach ($aliases as $key => $items) {
            foreach ($items as $alias) {
                $index = array_search($this->normalizer->lookup($alias), $normalized, true);
                if ($index !== false) {
                    $map[$key] = $index;
                    break;
                }
            }
        }

        $missing = array_values(array_diff($required, array_keys($map)));
        if ($missing) {
            throw new RuntimeException('ستون‌های لازم پیدا نشد: '.implode(', ', $missing).' | ستون‌های فایل: '.implode('، ', $headers));
        }

        return $map;
    }
}

