<?php

namespace App\Console\Commands;

use App\Services\StudyPrograms\PersianTextNormalizer;
use App\Services\StudyPrograms\StudyProgramValueMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Removes one exam year from the catalogue: its programs (imported or manual), review
 * records and import logs, the year itself, and every province, city, institution, campus
 * and field that no remaining program uses. Students' field-selection lists keep their own
 * copy of each chosen program and are not touched.
 *
 * Nothing is deleted unless another year still has active programs, and a verified backup
 * of all catalogue tables is written to storage first; nothing is deleted if the backup
 * fails. Running it again once the year is gone does nothing.
 */
class PurgeExamYear extends Command
{
    protected $signature = 'education:purge-exam-year {year : سال آزمون، مثلاً 1404} {--force : تأیید حذف}';

    protected $description = 'Back up the catalogue, then delete one exam year and the reference rows only it used.';

    public function handle(PersianTextNormalizer $normalizer): int
    {
        $year = DB::table('exam_years')->where('year', (int) $this->argument('year'))->first();

        if (! $year) {
            $this->line("سال {$this->argument('year')} در دیتابیس نیست؛ کاری انجام نشد.");

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->error('برای حذف باید --force را وارد کنید.');

            return self::FAILURE;
        }

        $replacement = DB::table('study_programs')
            ->where('exam_year_id', '!=', $year->id)
            ->where('is_active', true)
            ->where('validation_status', 'validated')
            ->exists();
        if (! $replacement) {
            $this->error("هیچ سال دیگری رشته‌محل فعال ندارد؛ برای اینکه فهرست رشته‌محل‌ها خالی نشود، سال {$year->year} حذف نشد.");

            return self::FAILURE;
        }

        $backup = new BufferedOutput;
        if (Artisan::call('education:backup-study-programs', [], $backup) !== self::SUCCESS) {
            $this->error('بکاپ ناموفق بود؛ چیزی حذف نشد.');
            $this->line($backup->fetch());

            return self::FAILURE;
        }
        $this->line('بکاپ پیش از حذف: '.trim($backup->fetch()));

        $deleted = DB::transaction(function () use ($year, $normalizer): array {
            $deleted = [];
            $importIds = DB::table('study_program_imports')->where('exam_year_id', $year->id)->pluck('id');
            $deleted['study_program_import_failures'] = DB::table('study_program_import_failures')->whereIn('import_id', $importIds)->delete();
            $deleted['study_program_imports'] = DB::table('study_program_imports')->whereIn('id', $importIds)->delete();
            $deleted['study_program_review_records'] = DB::table('study_program_review_records')->where('exam_year_id', $year->id)->delete();
            $deleted['study_programs'] = DB::table('study_programs')->where('exam_year_id', $year->id)->delete();
            $deleted['exam_years'] = DB::table('exam_years')->where('id', $year->id)->delete();

            $deleted['institution_campuses'] = DB::table('institution_campuses')
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.institution_campus_id', 'institution_campuses.id'))
                ->delete();
            $deleted['institutions'] = DB::table('institutions')
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.institution_id', 'institutions.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institution_campuses')->whereColumn('institution_campuses.institution_id', 'institutions.id'))
                ->delete();
            $deleted['academic_fields'] = DB::table('academic_fields')
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.academic_field_id', 'academic_fields.id'))
                ->delete();
            $deleted['cities'] = DB::table('cities')
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.city_id', 'cities.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institutions')->whereColumn('institutions.city_id', 'cities.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institution_campuses')->whereColumn('institution_campuses.city_id', 'cities.id'))
                ->delete();

            // The 31 provinces stay even when no program uses them; misspelled ones go.
            $official = array_map(fn (string $name) => $normalizer->lookup($name), StudyProgramValueMapper::OFFICIAL_PROVINCES);
            $deleted['provinces'] = DB::table('provinces')
                ->whereNotIn('normalized_name', $official)
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.province_id', 'provinces.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('cities')->whereColumn('cities.province_id', 'provinces.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institutions')->whereColumn('institutions.province_id', 'provinces.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institution_campuses')->whereColumn('institution_campuses.province_id', 'provinces.id'))
                ->delete();

            return $deleted;
        });

        $this->info("سال {$year->year} حذف شد.");
        $this->table(['table', 'deleted rows'], collect($deleted)->map(fn ($count, $table) => [$table, $count])->values()->all());

        return self::SUCCESS;
    }
}
