<?php

namespace App\Console\Commands;

use App\Models\StudyProgram;
use App\Services\StudyPrograms\StudyProgramValueMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditStudyPrograms extends Command
{
    protected $signature = 'education:audit-study-programs {--year=1404} {--format=json} {--output=}';
    protected $description = 'Audit V2 study program data quality.';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $base = StudyProgram::query()->whereHas('examYear', fn ($query) => $query->where('year', $year));
        $productionCities = DB::table('study_programs')
            ->join('exam_years', 'exam_years.id', '=', 'study_programs.exam_year_id')
            ->join('cities', 'cities.id', '=', 'study_programs.city_id')
            ->where('exam_years.year', $year)
            ->where('study_programs.validation_status', 'validated');
        $official = array_map(fn ($name) => app(\App\Services\StudyPrograms\PersianTextNormalizer::class)->lookup($name), StudyProgramValueMapper::OFFICIAL_PROVINCES);
        $report = [
            'total_production_records' => (clone $base)->count(),
            'total_review_records' => DB::table('study_program_review_records')->join('exam_years', 'exam_years.id', '=', 'study_program_review_records.exam_year_id')->where('exam_years.year', $year)->count(),
            'rows_by_exam_group' => (clone $base)->join('exam_groups', 'exam_groups.id', '=', 'study_programs.exam_group_id')->groupBy('exam_groups.slug')->pluck(DB::raw('count(*)'), 'exam_groups.slug')->all(),
            'review_by_exam_group' => DB::table('study_program_review_records')->join('exam_groups', 'exam_groups.id', '=', 'study_program_review_records.exam_group_id')->join('exam_years', 'exam_years.id', '=', 'study_program_review_records.exam_year_id')->where('exam_years.year', $year)->groupBy('exam_groups.slug')->pluck(DB::raw('count(*)'), 'exam_groups.slug')->all(),
            'rows_by_province' => (clone $base)->join('provinces', 'provinces.id', '=', 'study_programs.province_id')->groupBy('provinces.name')->pluck(DB::raw('count(*)'), 'provinces.name')->all(),
            'rows_by_city' => (clone $base)->join('cities', 'cities.id', '=', 'study_programs.city_id')->groupBy('cities.name')->pluck(DB::raw('count(*)'), 'cities.name')->all(),
            'rows_by_institution' => (clone $base)->join('institutions', 'institutions.id', '=', 'study_programs.institution_id')->groupBy('institutions.name')->pluck(DB::raw('count(*)'), 'institutions.name')->all(),
            'rows_by_academic_field' => (clone $base)->join('academic_fields', 'academic_fields.id', '=', 'study_programs.academic_field_id')->groupBy('academic_fields.name')->pluck(DB::raw('count(*)'), 'academic_fields.name')->all(),
            'rows_by_course_type' => (clone $base)->leftJoin('course_types', 'course_types.id', '=', 'study_programs.course_type_id')->groupBy('course_types.slug')->pluck(DB::raw('count(*)'), 'course_types.slug')->all(),
            'rows_by_admission_type' => (clone $base)->leftJoin('admission_types', 'admission_types.id', '=', 'study_programs.admission_type_id')->groupBy('admission_types.slug')->pluck(DB::raw('count(*)'), 'admission_types.slug')->all(),
            'malformed_province_values' => DB::table('provinces')->whereNotIn('normalized_name', $official)->pluck('name')->all(),
            'cities_without_provinces' => DB::table('cities')->whereNull('province_id')->count(),
            'malformed_city_values' => (clone $productionCities)
                ->where(fn ($q) => $q
                    ->where('cities.name', 'like', '%دانشگاه%')
                    ->orWhere('cities.name', 'like', '%استان%')
                    ->orWhere('cities.name', 'regexp', '(^| )[0-9]{5}($| )')
                    ->orWhereRaw("cities.name like '% %' and substring_index(cities.name, ' ', 1) = substring_index(substring_index(cities.name, ' ', 2), ' ', -1)"))
                ->count(),
            'duplicate_normalized_cities' => DB::table('cities')->select('province_id', 'normalized_name')->groupBy('province_id', 'normalized_name')->havingRaw('count(*) > 1')->count(),
            'city_names_containing_university' => DB::table('cities')->where('name', 'like', '%دانشگاه%')->count(),
            'city_names_containing_province_label' => DB::table('cities')->where('name', 'like', '%استان%')->count(),
            'city_names_containing_five_digit_codes' => DB::table('cities')->where('name', 'regexp', '(^| )[0-9]{5}($| )')->count(),
            'repeated_city_words' => DB::table('cities')->whereRaw("name like '% %' and substring_index(name, ' ', 1) = substring_index(substring_index(name, ' ', 2), ' ', -1)")->count(),
            'city_province_mismatches' => DB::table('study_programs')->join('cities', 'cities.id', '=', 'study_programs.city_id')->whereColumn('cities.province_id', '!=', 'study_programs.province_id')->count(),
            'missing_cities' => (clone $base)->whereNull('city_id')->count(),
            'missing_institutions' => (clone $base)->whereNull('institution_id')->count(),
            'missing_academic_fields' => (clone $base)->whereNull('academic_field_id')->count(),
            'unknown_course_types' => (clone $base)->whereHas('courseType', fn ($q) => $q->where('slug', 'other'))->count(),
            'unknown_admission_types' => (clone $base)->whereHas('admissionType', fn ($q) => $q->where('slug', 'unknown'))->count(),
            'duplicate_identity_hashes' => DB::table('study_programs')->select('identity_hash')->groupBy('identity_hash')->havingRaw('count(*) > 1')->count(),
            'duplicate_source_hashes' => DB::table('study_programs')->select('source_hash')->groupBy('source_hash')->havingRaw('count(*) > 1')->count(),
            'duplicate_codes_within_group' => DB::table('study_programs')->join('exam_years', 'exam_years.id', '=', 'study_programs.exam_year_id')->where('exam_years.year', $year)->select('exam_group_id', 'code')->groupBy('exam_group_id', 'code')->havingRaw('count(*) > 1')->count(),
            'same_code_across_groups' => DB::table('study_programs')->join('exam_years', 'exam_years.id', '=', 'study_programs.exam_year_id')->where('exam_years.year', $year)->select('code')->groupBy('code')->havingRaw('count(distinct exam_group_id) > 1')->count(),
            'source_file_counts' => (clone $base)->groupBy('source_file')->pluck(DB::raw('count(*)'), 'source_file')->all(),
            'orphaned_lookup_records' => [
                'provinces' => DB::table('provinces')->leftJoin('study_programs', 'study_programs.province_id', '=', 'provinces.id')->whereNull('study_programs.id')->count(),
                'cities' => DB::table('cities')->leftJoin('study_programs', 'study_programs.city_id', '=', 'cities.id')->whereNull('study_programs.id')->count(),
                'institutions' => DB::table('institutions')->leftJoin('study_programs', 'study_programs.institution_id', '=', 'institutions.id')->whereNull('study_programs.id')->count(),
            ],
            'public_records_marked_for_review' => (clone $base)->where('validation_status', '!=', 'validated')->count(),
        ];

        $format = $this->option('format');
        $content = $format === 'csv' ? $this->csv($report) : json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($this->option('output')) {
            file_put_contents($this->option('output'), $content);
        }

        $this->line($content);

        $blocking = [
            'malformed_province_values' => count($report['malformed_province_values']),
            'malformed_city_values' => $report['malformed_city_values'],
            'city_province_mismatches' => $report['city_province_mismatches'],
            'missing_institutions' => $report['missing_institutions'],
            'missing_academic_fields' => $report['missing_academic_fields'],
            'unknown_course_types' => $report['unknown_course_types'],
            'unknown_admission_types' => $report['unknown_admission_types'],
            'duplicate_identity_hashes' => $report['duplicate_identity_hashes'],
            'public_records_marked_for_review' => $report['public_records_marked_for_review'],
        ];

        return array_sum($blocking) === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function csv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($report as $key => $value) {
            fputcsv($handle, [$key, is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value]);
        }
        rewind($handle);

        return stream_get_contents($handle);
    }
}
