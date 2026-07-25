<?php

namespace App\Console\Commands;

use App\Models\ExamGroup;
use App\Models\ExamYear;
use App\Services\StudyPrograms\PersianTextNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetStudyPrograms extends Command
{
    protected $signature = 'education:reset-study-programs {--year=1404} {--confirm-reset}';
    protected $description = 'Delete old study-program data after a verified backup.';

    public const OFFICIAL_PROVINCES = [
        'آذربایجان شرقی', 'آذربایجان غربی', 'اردبیل', 'اصفهان', 'البرز', 'ایلام', 'بوشهر', 'تهران',
        'چهارمحال و بختیاری', 'خراسان جنوبی', 'خراسان رضوی', 'خراسان شمالی', 'خوزستان', 'زنجان',
        'سمنان', 'سیستان و بلوچستان', 'فارس', 'قزوین', 'قم', 'کردستان', 'کرمان', 'کرمانشاه',
        'کهگیلویه و بویراحمد', 'گلستان', 'گیلان', 'لرستان', 'مازندران', 'مرکزی', 'هرمزگان', 'همدان', 'یزد',
    ];

    public function handle(PersianTextNormalizer $normalizer): int
    {
        if (! $this->option('confirm-reset')) {
            $this->error('برای اجرای حذف باید --confirm-reset را وارد کنید.');
            return self::FAILURE;
        }

        if (! $this->latestBackupManifest()) {
            $this->error('بکاپ موفق پیدا نشد. ابتدا education:backup-study-programs را اجرا کنید.');
            return self::FAILURE;
        }

        $year = ExamYear::query()->where('year', (int) $this->option('year'))->first();
        $groups = ExamGroup::query()->whereIn('slug', ['tajrobi', 'riazi', 'ensani', 'honar', 'zaban'])->pluck('id');
        $deleted = [];

        DB::transaction(function () use ($year, $groups, &$deleted, $normalizer): void {
            if ($year && $groups->isNotEmpty()) {
                $importIds = DB::table('study_program_imports')
                    ->where('exam_year_id', $year->id)
                    ->where(fn ($q) => $q->whereNull('exam_group_id')->orWhereIn('exam_group_id', $groups))
                    ->pluck('id');

                if (Schema::hasTable('study_program_import_failures')) {
                    $deleted['study_program_import_failures'] = DB::table('study_program_import_failures')->whereIn('import_id', $importIds)->delete();
                }

                if (Schema::hasTable('study_program_review_records')) {
                    $deleted['study_program_review_records'] = DB::table('study_program_review_records')->where('exam_year_id', $year->id)->whereIn('exam_group_id', $groups)->delete();
                }

                $deleted['study_programs'] = DB::table('study_programs')->where('exam_year_id', $year->id)->whereIn('exam_group_id', $groups)->delete();
                $deleted['study_program_imports'] = DB::table('study_program_imports')->whereIn('id', $importIds)->delete();
            }

            $valid = array_map(fn ($name) => $normalizer->lookup($name), self::OFFICIAL_PROVINCES);
            $badProvinceIds = DB::table('provinces')->whereNotIn('normalized_name', $valid)->pluck('id');
            if ($badProvinceIds->isNotEmpty()) {
                if (Schema::hasTable('institution_campuses')) {
                    $deleted['malformed_orphan_campuses'] = DB::table('institution_campuses')
                        ->whereIn('province_id', $badProvinceIds)
                        ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.institution_campus_id', 'institution_campuses.id'))
                        ->delete();
                }

                $deleted['malformed_orphan_institutions'] = DB::table('institutions')
                    ->whereIn('province_id', $badProvinceIds)
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.institution_id', 'institutions.id'))
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institution_campuses')->whereColumn('institution_campuses.institution_id', 'institutions.id'))
                    ->delete();

                $deleted['malformed_orphan_cities'] = DB::table('cities')
                    ->whereIn('province_id', $badProvinceIds)
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.city_id', 'cities.id'))
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institutions')->whereColumn('institutions.city_id', 'cities.id'))
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institution_campuses')->whereColumn('institution_campuses.city_id', 'cities.id'))
                    ->delete();
            }

            $malformedCityIds = DB::table('cities')
                ->where(fn ($q) => $q
                    ->where('name', 'like', '%دانشگاه%')
                    ->orWhere('name', 'like', '%استان%')
                    ->orWhere('name', 'regexp', '(^| )[0-9]{5}($| )')
                    ->orWhereRaw("name like '% %' and substring_index(name, ' ', 1) = substring_index(substring_index(name, ' ', 2), ' ', -1)"))
                ->pluck('id');

            if ($malformedCityIds->isNotEmpty() && Schema::hasTable('institution_campuses')) {
                $deleted['malformed_city_orphan_campuses'] = DB::table('institution_campuses')
                    ->whereIn('city_id', $malformedCityIds)
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.institution_campus_id', 'institution_campuses.id'))
                    ->delete();
            }

            if ($malformedCityIds->isNotEmpty()) {
                $deleted['malformed_city_orphan_institutions'] = DB::table('institutions')
                    ->whereIn('city_id', $malformedCityIds)
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.institution_id', 'institutions.id'))
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institution_campuses')->whereColumn('institution_campuses.institution_id', 'institutions.id'))
                    ->delete();
            }

            $deleted['malformed_orphan_cities'] = DB::table('cities')
                ->whereIn('id', $malformedCityIds)
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.city_id', 'cities.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institutions')->whereColumn('institutions.city_id', 'cities.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institution_campuses')->whereColumn('institution_campuses.city_id', 'cities.id'))
                ->delete();

            $deleted['malformed_orphan_provinces'] = DB::table('provinces')
                ->whereNotIn('normalized_name', $valid)
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('cities')->whereColumn('cities.province_id', 'provinces.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('institutions')->whereColumn('institutions.province_id', 'provinces.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('study_programs')->whereColumn('study_programs.province_id', 'provinces.id'))
                ->delete();
        });

        $this->table(['deleted', 'rows'], collect($deleted)->map(fn ($count, $table) => [$table, $count])->all());

        return self::SUCCESS;
    }

    private function latestBackupManifest(): ?string
    {
        $manifests = array_merge(
            glob(storage_path('app/backups/study-programs/*/backup-manifest.json')) ?: [],
            glob(storage_path('app/backups/study-programs/*/manifest.json')) ?: [],
        );
        rsort($manifests);

        foreach ($manifests as $manifest) {
            $data = json_decode((string) file_get_contents($manifest), true);
            $dir = dirname($manifest);
            $verified = collect($data['files'] ?? [])->every(fn ($checksum, $file) => is_file($dir.'/'.$file) && hash_file('sha256', $dir.'/'.$file) === $checksum);
            if (($data['status'] ?? null) === 'success' && $verified) {
                return $manifest;
            }
        }

        return null;
    }
}
