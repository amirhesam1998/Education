<?php

namespace App\Services\StudyPrograms;

use App\Models\AcademicField;
use App\Models\AdmissionType;
use App\Models\City;
use App\Models\CourseType;
use App\Models\ExamGroup;
use App\Models\ExamYear;
use App\Models\Institution;
use App\Models\InstitutionCampus;
use App\Models\Province;
use App\Models\StudyProgram;
use App\Models\StudyProgramImport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StudyProgramImporter
{
    private array $cache = [];
    private array $referenceCities = [];
    private bool $dryRun = false;

    public function __construct(
        private readonly XlsxRowReader $reader,
        private readonly StudyProgramHeaderResolver $headers,
        private readonly PersianTextNormalizer $normalizer,
        private readonly StudyProgramValueMapper $mapper,
    ) {
    }

    public function importReference(string $file, bool $dryRun = false): array
    {
        $stats = ['reference_rows' => 0, 'reference_skipped' => 0];
        $headers = [];
        $maps = [];

        foreach ($this->reader->rows($file) as $row) {
            $sheet = $this->normalizer->lookup($row['sheet']);
            if (! in_array($sheet, [
                $this->normalizer->lookup('دانشگاه‌ها و شهرها'),
                $this->normalizer->lookup('مرجع دانشگاه‌ها'),
                $this->normalizer->lookup('شهرهای مرجع'),
            ], true)) {
                continue;
            }

            if (! isset($headers[$row['sheet']])) {
                $headers[$row['sheet']] = array_values($row['values']);
                $required = $sheet === $this->normalizer->lookup('شهرهای مرجع')
                    ? ['province', 'city']
                    : ['province', 'city', 'institution'];
                $maps[$row['sheet']] = $this->headers->resolve($headers[$row['sheet']], StudyProgramHeaderResolver::REFERENCE, $required);
                continue;
            }

            $map = $maps[$row['sheet']];
            $province = $this->mapper->validProvince($this->cell($row, $map, 'province'));
            $city = $this->cell($row, $map, 'city');
            $institution = $this->cell($row, $map, 'institution');
            $campus = $this->cell($row, $map, 'campus');

            if (! $province || ! $city || $this->cityReviewReason($city, $province, false)) {
                $stats['reference_skipped']++;
                continue;
            }

            $stats['reference_rows']++;
            $this->referenceCities[$this->normalizer->lookup($province)][$this->normalizer->lookup($city)] = true;

            if (! $dryRun) {
                $provinceId = $this->province($province);
                $cityId = $this->city($city, $provinceId);
                if ($institution) {
                    $institutionId = $this->institution($institution, $provinceId, $cityId);
                    $campus && $this->campus($campus, $institutionId, $provinceId, $cityId);
                }
            }
        }

        return $stats;
    }

    public function importFile(string $file, int $year, string $groupSlug, array $options = []): array
    {
        $chunkSize = min(max((int) ($options['chunk'] ?? 1000), 500), 1000);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $this->dryRun = $dryRun;
        $onlyValidated = (bool) ($options['only_validated'] ?? false);
        $fileHash = hash_file('sha256', $file);
        $lock = Cache::lock("study-program-v3:$year:$groupSlug:$fileHash", 600);

        if (! $lock->get()) {
            throw new RuntimeException('این فایل هم‌زمان در حال import است.');
        }

        try {
            $examYear = $dryRun ? $this->fakeYear($year) : ExamYear::query()->firstOrCreate(['year' => $year], ['is_active' => true]);
            $examGroup = $dryRun ? $this->fakeGroup($groupSlug) : ExamGroup::query()->firstOrCreate(['slug' => $groupSlug], ['name' => StudyProgramValueMapper::EXAM_GROUPS[$groupSlug] ?? $groupSlug]);

            $import = $dryRun ? null : StudyProgramImport::query()->create([
                'exam_year_id' => $examYear->id,
                'exam_group_id' => $examGroup->id,
                'source_path' => $file,
                'source_filename' => basename($file),
                'source_file' => basename($file),
                'file_hash' => $fileHash,
                'status' => 'running',
                'started_at' => now(),
                'metadata' => ['v3' => true],
            ]);

            $stats = $this->summary();
            $programs = [];
            $reviews = [];
            $this->preload();

            foreach ($this->reader->rows($file) as $row) {
                $sheet = $this->normalizer->lookup($row['sheet']);
                if (! in_array($sheet, [
                    $this->normalizer->lookup('کدرشته‌ها'),
                    $this->normalizer->lookup('نیازمند بررسی'),
                ], true)) {
                    continue;
                }

                if (! isset($headers[$row['sheet']])) {
                    $headers[$row['sheet']] = array_values($row['values']);
                    $maps[$row['sheet']] = $this->headers->resolve($headers[$row['sheet']], StudyProgramHeaderResolver::V3, ['code', 'province', 'city', 'course_type_slug', 'academic_field', 'institution', 'admission_type_slug', 'validation_status']);
                    continue;
                }

                $status = $this->cell($row, $maps[$row['sheet']], 'validation_status');
                if (! $status) {
                    continue;
                }

                $data = $this->rowData($row, $maps[$row['sheet']], $year, $examYear->id, $examGroup->id, basename($file), $groupSlug, $stats);

                if ($sheet === $this->normalizer->lookup('کدرشته‌ها') && $this->normalizer->lookup($status) !== $this->normalizer->lookup('تأیید ساختاری')) {
                    continue;
                }

                if ($sheet === $this->normalizer->lookup('نیازمند بررسی')) {
                    if (! $onlyValidated) {
                        $reviews[] = $data['review'];
                        $stats['review_rows']++;
                    }
                } elseif ($data['has_errors']) {
                    if (! $onlyValidated) {
                        $reviews[] = $data['review'];
                        $stats['review_rows']++;
                    } else {
                        $stats['skipped']++;
                    }
                } else {
                    $programs[] = $data['program'];
                }

                $stats['total_rows']++;

                if (count($programs) >= $chunkSize || count($reviews) >= $chunkSize) {
                    $this->flush($programs, $reviews, $stats, $dryRun);
                }
            }

            $this->flush($programs, $reviews, $stats, $dryRun);

            if ($import) {
                $import->update([
                    'status' => 'completed',
                    'total_rows' => $stats['total_rows'],
                    'inserted_rows' => $stats['inserted'],
                    'updated_rows' => $stats['updated'],
                    'unchanged_rows' => $stats['unchanged'],
                    'review_rows' => $stats['review_rows'],
                    'skipped_rows' => $stats['skipped'],
                    'failed_rows' => $stats['failed'],
                    'finished_at' => now(),
                ]);
            }

            return $stats;
        } catch (\Throwable $e) {
            isset($import) && $import?->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function rowData(array $row, array $map, int $year, int $yearId, int $groupId, string $fallbackFile, string $expectedGroup, array &$stats): array
    {
        $raw = $row['values'];
        $code = $this->cell($row, $map, 'code');
        $provinceName = $this->mapper->validProvince($this->cell($row, $map, 'province'));
        $cityName = $this->cell($row, $map, 'city');
        $institutionName = $this->cell($row, $map, 'institution');
        $campusName = $this->cell($row, $map, 'campus');
        $fieldName = $this->cell($row, $map, 'academic_field');
        $courseSlug = $this->cell($row, $map, 'course_type_slug');
        $admissionSlug = $this->cell($row, $map, 'admission_type_slug');
        $sourceFile = $this->cell($row, $map, 'source_file') ?: $fallbackFile;
        $validationStatus = $this->cell($row, $map, 'validation_status');
        $description = $this->cell($row, $map, 'description');
        $cityProblem = $this->cityReviewReason($cityName, $provinceName);
        $review = [];
        $blockingReview = [];

        if (! $code || ! preg_match('/^[0-9A-Za-z-]+$/', $code)) {
            $review[] = 'کدرشته نامعتبر';
            $blockingReview[] = 'کدرشته نامعتبر';
        }
        if (! $provinceName) {
            $review[] = 'استان نامعتبر';
            $blockingReview[] = 'استان نامعتبر';
        }
        if (! $cityName || $cityProblem) {
            $review[] = $cityProblem ?: 'شهر نامعتبر';
            $blockingReview[] = $cityProblem ?: 'شهر نامعتبر';
        }
        if (! isset(StudyProgramValueMapper::COURSE_TYPES[$courseSlug])) {
            $review[] = 'نوع دوره نامعتبر';
            $blockingReview[] = 'نوع دوره نامعتبر';
        }
        if (! $fieldName) {
            $review[] = 'رشته نامعتبر';
            $blockingReview[] = 'رشته نامعتبر';
        }
        if (! isset(StudyProgramValueMapper::ADMISSION_TYPES[$admissionSlug]) || $admissionSlug === 'unknown') {
            $review[] = 'نحوه پذیرش نامعتبر';
            $blockingReview[] = 'نحوه پذیرش نامعتبر';
        }
        if (($this->mapper->examGroupSlug($this->cell($row, $map, 'exam_group')) ?: $expectedGroup) !== $expectedGroup) {
            $review[] = 'گروه آزمایشی نامعتبر';
            $blockingReview[] = 'گروه آزمایشی نامعتبر';
        }

        $provinceId = $provinceName ? $this->province($provinceName) : null;
        $cityId = $cityName && $provinceId && ! $cityProblem ? $this->city($cityName, $provinceId) : null;

        $institutionId = $institutionName && $provinceId && $cityId ? $this->institution($institutionName, $provinceId, $cityId) : null;
        $campusId = $campusName && $institutionId ? $this->campus($campusName, $institutionId, $provinceId, $cityId) : null;
        $fieldId = $fieldName ? $this->lookup(AcademicField::class, $fieldName) : null;
        $courseTypeId = isset(StudyProgramValueMapper::COURSE_TYPES[$courseSlug]) ? $this->slugLookup(CourseType::class, $courseSlug, StudyProgramValueMapper::COURSE_TYPES[$courseSlug]) : null;
        $admissionTypeId = isset(StudyProgramValueMapper::ADMISSION_TYPES[$admissionSlug]) ? $this->slugLookup(AdmissionType::class, $admissionSlug, StudyProgramValueMapper::ADMISSION_TYPES[$admissionSlug]) : null;
        $capacity1 = $this->integer($this->cell($row, $map, 'first_capacity'), $review, 'ظرفیت نیمسال اول نامعتبر');
        $capacity2 = $this->integer($this->cell($row, $map, 'second_capacity'), $review, 'ظرفیت نیمسال دوم نامعتبر');
        $identityInput = implode('|', [$year, $expectedGroup, $code, $this->normalizer->lookup($institutionName), $this->normalizer->lookup($campusName), $this->normalizer->lookup($fieldName), $courseSlug]);
        $identityHash = hash('sha256', $identityInput);
        $sourceHash = $this->sourceHash([
            'code' => $code,
            'province' => $provinceName,
            'city' => $cityName,
            'institution' => $institutionName,
            'campus' => $campusName,
            'academic_field' => $fieldName,
            'course_type' => $courseSlug,
            'admission_type' => $admissionSlug,
            'accepts_male' => $this->cell($row, $map, 'accepts_male'),
            'accepts_female' => $this->cell($row, $map, 'accepts_female'),
            'first_capacity' => $this->cell($row, $map, 'first_capacity'),
            'second_capacity' => $this->cell($row, $map, 'second_capacity'),
            'description' => $description,
            'booklet_page' => $this->cell($row, $map, 'booklet_page'),
            'booklet_section' => $this->cell($row, $map, 'booklet_section'),
        ]);
        $sourceReviewReason = $this->normalizer->lookup($validationStatus) === $this->normalizer->lookup('تأیید ساختاری') ? null : $this->cell($row, $map, 'review_reason');
        $reviewReason = trim(implode('، ', array_filter([$sourceReviewReason, ...$review])));

        $base = [
            'exam_year_id' => $yearId,
            'exam_group_id' => $groupId,
            'code' => $code ?: null,
            'source_file' => $sourceFile,
            'source_row' => (int) ($row['row'] ?? 0),
            'source_hash' => $sourceHash,
            'identity_hash' => $identityHash,
            'raw_data' => json_encode($raw, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return [
            'review_reason' => $reviewReason,
            'has_errors' => $blockingReview !== [],
            'program' => $base + [
                'province_id' => $provinceId,
                'city_id' => $cityId,
                'institution_id' => $institutionId,
                'institution_campus_id' => $campusId,
                'academic_field_id' => $fieldId,
                'course_type_id' => $courseTypeId,
                'admission_type_id' => $admissionTypeId,
                'original_course_type' => $this->cell($row, $map, 'original_course_type'),
                'original_admission_type' => $this->cell($row, $map, 'admission_type_name'),
                'accepts_male' => $this->gender($this->cell($row, $map, 'accepts_male'), 'مرد'),
                'accepts_female' => $this->gender($this->cell($row, $map, 'accepts_female'), 'زن'),
                'first_semester_capacity' => $capacity1,
                'second_semester_capacity' => $capacity2,
                'description' => $description,
                'booklet_page' => $this->integer($this->cell($row, $map, 'booklet_page'), $review, 'صفحه دفترچه نامعتبر'),
                'booklet_section' => $this->cell($row, $map, 'booklet_section'),
                'city_detection_method' => $this->cell($row, $map, 'city_detection_method'),
                'validation_status' => 'validated',
            ],
            'review' => [
                'source_hash' => hash('sha256', $sourceHash.'|review|'.$fallbackFile.'|'.$row['sheet'].'|'.($row['row'] ?? '')),
            ] + $base + [
                'review_reason' => $reviewReason ?: 'نیازمند بررسی',
                'status' => 'pending',
            ],
        ];
    }

    private function flush(array &$programs, array &$reviews, array &$stats, bool $dryRun): void
    {
        if ($programs) {
            $existing = StudyProgram::query()->whereIn('identity_hash', array_column($programs, 'identity_hash'))->pluck('source_hash', 'identity_hash');
            foreach ($programs as $program) {
                if (! isset($existing[$program['identity_hash']])) {
                    $stats['inserted']++;
                } elseif ($existing[$program['identity_hash']] === $program['source_hash']) {
                    $stats['unchanged']++;
                } else {
                    $stats['updated']++;
                }
            }
        }

        if (! $dryRun) {
            DB::transaction(function () use ($programs, $reviews): void {
                $programs && StudyProgram::query()->upsert($programs, ['identity_hash'], array_diff(array_keys($programs[0]), ['identity_hash', 'created_at']));
                $reviews && DB::table('study_program_review_records')->upsert($reviews, ['source_hash'], array_diff(array_keys($reviews[0]), ['source_hash', 'created_at']));
            });
        }

        $programs = [];
        $reviews = [];
    }

    private function preload(): void
    {
        $this->cache = [
            Province::class => Province::query()->pluck('id', 'normalized_name')->all(),
            AcademicField::class => AcademicField::query()->pluck('id', 'normalized_name')->all(),
            CourseType::class => CourseType::query()->pluck('id', 'slug')->all(),
            AdmissionType::class => AdmissionType::query()->pluck('id', 'slug')->all(),
        ];

        foreach (Province::query()->with('cities')->get() as $province) {
            foreach ($province->cities as $city) {
                $this->referenceCities[$province->normalized_name][$city->normalized_name] = true;
            }
        }
    }

    private function cell(array $row, array $map, string $key): ?string
    {
        return $this->normalizer->nullable($row['values'][$map[$key] ?? -1] ?? null);
    }

    private function province(string $name): int
    {
        $normalized = $this->normalizer->lookup($name);

        if ($this->dryRun) {
            return $this->cache[Province::class][$normalized] ??= 1;
        }

        $province = Province::query()->firstOrCreate(['normalized_name' => $normalized], ['name' => $name]);
        if ($province->name !== $name) {
            $province->update(['name' => $name]);
        }

        return $this->cache[Province::class][$normalized] = $province->id;
    }

    private function city(string $name, int $provinceId): int
    {
        if ($this->dryRun) {
            return 1;
        }

        $key = City::class.':'.$provinceId.':'.$this->normalizer->lookup($name);

        return $this->cache[$key] ??= City::query()->firstOrCreate(
            ['province_id' => $provinceId, 'normalized_name' => $this->normalizer->lookup($name)],
            ['name' => $name],
        )->id;
    }

    private function institution(string $name, int $provinceId, int $cityId): int
    {
        return $this->lookup(Institution::class, $name, ['province_id' => $provinceId, 'city_id' => $cityId]);
    }

    private function campus(string $name, int $institutionId, ?int $provinceId, ?int $cityId): int
    {
        $key = InstitutionCampus::class.':'.$institutionId.':'.$this->normalizer->lookup($name);

        if ($this->dryRun) {
            return $this->cache[$key] ??= 1;
        }

        return $this->cache[$key] ??= InstitutionCampus::query()->firstOrCreate(
            ['institution_id' => $institutionId, 'normalized_name' => $this->normalizer->lookup($name)],
            ['name' => $name, 'province_id' => $provinceId, 'city_id' => $cityId],
        )->id;
    }

    private function lookup(string $model, string $name, array $extra = []): int
    {
        $normalized = $extra['normalized_name'] ?? $this->normalizer->lookup($name);

        if ($this->dryRun) {
            return $this->cache[$model][$normalized] ??= 1;
        }

        return $this->cache[$model][$normalized] ??= $model::query()->firstOrCreate(
            ['normalized_name' => $normalized],
            ['name' => $name] + $extra,
        )->id;
    }

    private function slugLookup(string $model, string $slug, string $name): int
    {
        if ($this->dryRun) {
            return $this->cache[$model][$slug] ??= 1;
        }

        return $this->cache[$model][$slug] ??= $model::query()->firstOrCreate(['slug' => $slug], ['name' => $name])->id;
    }

    private function integer(?string $value, array &$review, string $reason): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            $review[] = $reason;
            return null;
        }

        return (int) $value;
    }

    private function gender(?string $value, string $expected): ?bool
    {
        return $value === null ? null : str_contains($this->normalizer->lookup($value), $this->normalizer->lookup($expected));
    }

    private function cityReviewReason(?string $city, ?string $province, bool $requireKnown = true): ?string
    {
        $cityLookup = $this->normalizer->lookup($city);
        if ($cityLookup === '') {
            return 'شهر نامعتبر';
        }

        if (preg_match('/\b\d{5}\b/u', $cityLookup) || str_contains($cityLookup, 'دانشگاه') || str_contains($cityLookup, 'استان')) {
            return 'شهر خارج از مرجع V3';
        }

        $words = preg_split('/\s+/u', $cityLookup, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) >= 2 && $words[0] === $words[1]) {
            return 'شهر تکراری یا خراب';
        }

        $provinceName = $this->mapper->validProvince($province);
        if ($requireKnown && $provinceName && $this->referenceCities) {
            $provinceLookup = $this->normalizer->lookup($provinceName);
            if (! isset($this->referenceCities[$provinceLookup][$cityLookup])) {
                return 'شهر خارج از مرجع V3';
            }
        }

        return null;
    }

    private function sourceHash(array $values): string
    {
        $normalized = [];
        foreach ($values as $key => $value) {
            $normalized[$key] = $this->normalizer->nullable((string) $value);
        }

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function fakeYear(int $year): ExamYear
    {
        $model = new ExamYear(['year' => $year]);
        $model->id = ExamYear::query()->where('year', $year)->value('id') ?: 0;

        return $model;
    }

    private function fakeGroup(string $slug): ExamGroup
    {
        $model = new ExamGroup(['slug' => $slug]);
        $model->id = ExamGroup::query()->where('slug', $slug)->value('id') ?: 0;

        return $model;
    }

    private function summary(array $overrides = []): array
    {
        return $overrides + [
            'total_rows' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'review_rows' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
    }
}
