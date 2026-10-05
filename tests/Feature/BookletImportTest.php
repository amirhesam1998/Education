<?php

namespace Tests\Feature;

use App\Models\StudyProgram;
use App\Models\User;
use App\Services\FieldSelectionService;
use App\Services\StudyPrograms\StudyProgramAdminService;
use App\Services\StudyPrograms\StudyProgramQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class BookletImportTest extends TestCase
{
    use RefreshDatabase;

    private string $storage;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        // The import writes a catalogue backup under storage/app; keep it out of the real storage folder.
        $this->storage = sys_get_temp_dir().'/booklet-import-test-'.getmypid();
        File::ensureDirectoryExists($this->storage.'/app');
        $this->app->useStoragePath($this->storage);
        $this->root = $this->storage.'/booklets';
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    #[Test]
    public function it_imports_every_booklet_column_and_skips_an_unchanged_booklet(): void
    {
        $this->writeBooklet([
            $this->program('31601', ['female_capacity' => 13, 'male_capacity' => 12, 'capacity_first' => 25]),
            $this->program('43303', [
                'campus' => 'پردیس فاطمه‌الزهراء (س) تبریز',
                'course' => null,
                'course_type' => 'teacher',
                'method' => null,
                'accepts_male' => false,
                'admission_scope' => 'مخصوص متقاضیان بومی استان آذربایجان شرقی',
                'service_location' => 'آذرشهر',
            ]),
        ]);

        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();

        $program = StudyProgram::query()->with(['examYear', 'examGroup', 'province', 'city', 'institution', 'academicField', 'courseType', 'admissionType'])->where('code', '31601')->firstOrFail();
        $this->assertSame(1405, (int) $program->examYear->year);
        $this->assertSame('tajrobi', $program->examGroup->slug);
        $this->assertSame('آذربایجان شرقی', $program->province->name);
        $this->assertSame('تبریز', $program->city->name);
        $this->assertSame('دانشگاه علوم پزشکی و خدمات بهداشتی درمانی تبریز', $program->institution->name);
        $this->assertSame('دکتری عمومی پزشکی', $program->academicField->name);
        $this->assertSame('day', $program->courseType->slug);
        $this->assertSame('with_exam', $program->admissionType->slug);
        $this->assertSame(25, $program->first_semester_capacity);
        $this->assertSame(13, $program->female_capacity);
        $this->assertSame(12, $program->male_capacity);
        $this->assertSame('نیم‌سال اول و دوم سال ۱۴۰۵', $program->admission_period);
        $this->assertSame('فاقد خوابگاه', $program->description);
        $this->assertSame('booklet', $program->source_type);
        $this->assertSame(47, $program->booklet_page);
        $this->assertSame(46, $program->raw_data['printed_page']);

        $teacher = StudyProgram::query()->with(['courseType', 'institutionCampus'])->where('code', '43303')->firstOrFail();
        $this->assertSame('دانشجو - معلم', $teacher->courseType->name);
        $this->assertSame('پردیس فاطمه‌الزهراء (س) تبریز', $teacher->institutionCampus->name);
        $this->assertSame('آذرشهر', $teacher->service_location);
        $this->assertFalse($teacher->accepts_male);
        $this->assertTrue($teacher->accepts_female);

        $this->artisan('education:import-booklets', ['--path' => $this->root])
            ->expectsOutputToContain('قبلاً وارد شده')
            ->assertSuccessful();

        $this->assertSame(2, StudyProgram::query()->count());
        $this->assertSame(1, DB::table('study_program_imports')->where('status', 'completed')->count());
        $this->assertCount(1, glob($this->storage.'/app/backups/study-programs/*/backup-manifest.json'));
        $this->assertSame(1, DB::table('institutions')->count());
        $this->assertSame(1, DB::table('cities')->count());
    }

    #[Test]
    public function a_new_version_updates_changed_rows_keeps_admin_edits_and_drops_removed_codes(): void
    {
        $this->writeBooklet([$this->program('1'), $this->program('2'), $this->program('3'), $this->program('4')]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();
        DB::table('study_programs')->where('code', '2')->update(['source_type' => 'manual', 'description' => 'ویرایش مدیر']);

        $this->writeBooklet([
            $this->program('1', ['description' => 'دارای خوابگاه']),
            $this->program('2', ['description' => 'نسخه تازه']),
            $this->program('4'),
            $this->program('5'),
        ]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();

        $this->assertSame(['1', '2', '4', '5'], StudyProgram::query()->orderBy('code')->pluck('code')->all());
        $this->assertSame('دارای خوابگاه', StudyProgram::query()->where('code', '1')->value('description'));
        $this->assertSame('ویرایش مدیر', StudyProgram::query()->where('code', '2')->value('description'));
        $import = DB::table('study_program_imports')->orderByDesc('id')->first();
        $this->assertSame([1, 1, 1, 1], [(int) $import->inserted_rows, (int) $import->updated_rows, (int) $import->unchanged_rows, (int) $import->skipped_rows]);
        $this->assertSame(1, json_decode($import->metadata, true)['removed_rows']);
    }

    #[Test]
    public function going_back_to_an_earlier_version_of_a_booklet_imports_it_again(): void
    {
        $this->writeBooklet([$this->program('1', ['capacity_first' => 54])]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();
        $this->writeBooklet([$this->program('1', ['capacity_first' => 99]), $this->program('9')]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();

        $this->writeBooklet([$this->program('1', ['capacity_first' => 54])]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();

        $this->assertSame(['1'], StudyProgram::query()->pluck('code')->all());
        $this->assertSame(54, StudyProgram::query()->where('code', '1')->value('first_semester_capacity'));
    }

    #[Test]
    public function it_fails_when_there_is_no_booklet_to_import(): void
    {
        File::ensureDirectoryExists($this->root);

        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertFailed();
    }

    #[Test]
    public function an_admin_edit_keeps_the_printed_course_and_the_booklet_details(): void
    {
        $this->writeBooklet([$this->program('31650', ['course' => 'پردیس خودگردان', 'course_type' => 'tuition', 'method' => 'صرفا با سوابق تحصیلی', 'admission_type' => 'academic_records', 'female_capacity' => 3, 'male_capacity' => 2])]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();
        $program = StudyProgram::query()->where('code', '31650')->firstOrFail();

        app(StudyProgramAdminService::class)->updateManual($program, [
            ...$program->only(['exam_year_id', 'exam_group_id', 'code', 'province_id', 'native_province_id', 'city_id', 'institution_id', 'institution_campus_id', 'academic_field_id', 'course_type_id', 'admission_type_id', 'second_semester_capacity', 'female_capacity', 'male_capacity', 'admission_period', 'admission_scope', 'service_location', 'description', 'section_note']),
            'first_semester_capacity' => 6,
            'male_capacity' => 3,
            'is_active' => true,
        ], User::factory()->create());

        $program->refresh();
        $this->assertSame('manual', $program->source_type);
        $this->assertSame('پردیس خودگردان', $program->original_course_type);
        $this->assertSame('صرفا با سوابق تحصیلی', $program->original_admission_type);
        $this->assertSame([6, 3, 3], [$program->first_semester_capacity, $program->female_capacity, $program->male_capacity]);
        $this->assertSame(46, $program->raw_data['printed_page']);
        $this->assertSame('پردیس خودگردان', app(FieldSelectionService::class)->searchFieldCatalog(['q' => '31650'])->first()['university_type']);
    }

    #[Test]
    public function existing_reference_rows_take_the_booklet_spelling_instead_of_being_duplicated(): void
    {
        $now = now()->toDateTimeString();
        $provinceId = DB::table('provinces')->insertGetId(['name' => 'آذربایجان شرقی', 'normalized_name' => 'آذربایجان شرقی', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('cities')->insert(['province_id' => $provinceId, 'name' => 'تبريز', 'normalized_name' => 'تبریز', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('institutions')->insert(['name' => 'دانشگاه علوم پزشكي و خدمات بهداشتي درماني تبريز', 'normalized_name' => 'دانشگاه علوم پزشکی و خدمات بهداشتی درمانی تبریز', 'created_at' => $now, 'updated_at' => $now]);

        $this->writeBooklet([$this->program('31601')]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();

        $this->assertSame(['تبریز'], DB::table('cities')->pluck('name')->all());
        $this->assertSame(['دانشگاه علوم پزشکی و خدمات بهداشتی درمانی تبریز'], DB::table('institutions')->pluck('name')->all());
    }

    #[Test]
    public function the_field_selection_catalog_shows_the_printed_course_and_every_admission_detail(): void
    {
        $this->writeBooklet([
            $this->program('31650', ['course' => 'پردیس خودگردان', 'course_type' => 'tuition', 'female_capacity' => 3, 'male_capacity' => 2, 'section_note' => 'یادداشت جدول']),
            $this->program('43303', [
                'course' => null,
                'course_type' => 'teacher',
                'description' => null,
                'admission_period' => null,
                'admission_scope' => 'مخصوص متقاضیان بومی استان آذربایجان شرقی',
                'service_location' => 'آذرشهر',
            ]),
        ]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();

        $catalog = app(FieldSelectionService::class);
        $tuition = $catalog->searchFieldCatalog(['q' => '31650'])->firstWhere('field_code', '31650');
        $teacher = $catalog->searchFieldCatalog(['q' => '43303'])->firstWhere('field_code', '43303');

        $this->assertSame('پردیس خودگردان', $tuition['university_type']);
        $this->assertSame([3, 2], [$tuition['female_capacity'], $tuition['male_capacity']]);
        $this->assertSame('یادداشت جدول', $tuition['section_note']);
        $this->assertSame('فاقد خوابگاه؛ پذیرش نیم‌سال اول و دوم سال ۱۴۰۵', $tuition['field_description']);
        $this->assertSame('دانشجو - معلم', $teacher['university_type']);
        $this->assertSame('مخصوص متقاضیان بومی استان آذربایجان شرقی؛ محل خدمت: آذرشهر', $teacher['field_description']);
    }

    #[Test]
    public function a_commitment_program_is_found_under_both_its_study_province_and_its_native_province(): void
    {
        $scope = 'پذیرش از تمام متقاضیان سراسر کشور، با اولویت متقاضیان بومی استان کردستان';
        $kurdistan = [
            'province' => 'کردستان',
            'city' => 'سنندج',
            'institution' => 'دانشگاه علوم پزشکی و خدمات بهداشتی درمانی کردستان',
            'institution_province' => 'کردستان',
            'institution_city' => 'سنندج',
        ];
        $commitment = ['part' => 'health_commitment_council', 'course' => null, 'course_type' => 'commitment', 'section' => $scope, 'admission_scope' => $scope, 'native_province' => 'کردستان'];
        $this->writeBooklet([
            $this->program('31601'),
            $this->program('38873', $commitment),
            $this->program('38878', $kurdistan + $commitment),
            $this->program('38879', $kurdistan),
        ]);
        $this->artisan('education:import-booklets', ['--path' => $this->root])->assertSuccessful();

        $province = fn (string $name) => (int) DB::table('provinces')->where('name', $name)->value('id');
        $codes = fn ($rows) => collect($rows)->pluck('field_code')->sort()->values()->all();
        $catalog = app(FieldSelectionService::class);

        // 38873 is studied in Tabriz and kept for Kurdistan natives: both provinces find it.
        $this->assertSame(['38873', '38878', '38879'], $codes($catalog->searchFieldCatalog(['province_id' => $province('کردستان')])));
        $this->assertSame(['31601', '38873'], $codes($catalog->searchFieldCatalog(['province_id' => $province('آذربایجان شرقی')])));
        $this->assertSame(['38873', '38878', '38879'], $codes($catalog->searchFieldCatalog(['province_ids' => [$province('کردستان')]])));
        $this->assertSame(['38873', '38878', '38879'], $codes($catalog->searchFieldCatalog(['q' => 'پزشکی', 'province_ids' => ['', $province('کردستان')]])));
        // A city is where the program is studied.
        $sanandaj = (int) DB::table('cities')->where('name', 'سنندج')->value('id');
        $this->assertSame(['38878', '38879'], $codes($catalog->searchFieldCatalog(['province_id' => $province('کردستان'), 'city_id' => $sanandaj])));

        $row = $catalog->searchFieldCatalog(['q' => '38873'])->firstWhere('field_code', '38873');
        $this->assertSame(['آذربایجان شرقی', 'کردستان', 'تبریز'], [$row['province'], $row['native_province'], $row['city']]);
        $this->assertNull($catalog->searchFieldCatalog(['q' => '31601'])->firstWhere('field_code', '31601')['native_province']);

        // The admin catalogue list filters the same way.
        $this->assertSame(['38873', '38878', '38879'], app(StudyProgramQuery::class)->base(['province_id' => $province('کردستان')])->orderBy('code')->pluck('code')->all());
    }

    #[Test]
    public function a_programs_file_that_does_not_match_its_manifest_is_refused(): void
    {
        $dir = $this->writeBooklet([$this->program('1')]);
        file_put_contents($dir.'/programs.jsonl.gz', gzencode(json_encode($this->program('2'), JSON_UNESCAPED_UNICODE)."\n"));

        $this->expectException(RuntimeException::class);

        try {
            $this->artisan('education:import-booklets', ['--path' => $this->root])->run();
        } finally {
            $this->assertSame(0, StudyProgram::query()->count());
        }
    }

    /** @param list<array<string, mixed>> $programs */
    private function writeBooklet(array $programs): string
    {
        $dir = $this->root.'/1405/tajrobi';
        File::ensureDirectoryExists($dir);

        $lines = array_map(fn (array $program) => json_encode($program, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $programs);
        file_put_contents($dir.'/programs.jsonl.gz', gzencode(implode("\n", $lines)."\n"));

        file_put_contents($dir.'/manifest.json', json_encode([
            'format' => 'booklet-programs-v1',
            'year' => 1405,
            'group' => 'tajrobi',
            'group_name' => 'تجربی',
            'source_file' => 'Tajrobi_v1.pdf',
            'source_sha256' => str_repeat('0', 64),
            'programs_file' => 'programs.jsonl.gz',
            'programs' => count($programs),
            'programs_sha256' => hash_file('sha256', $dir.'/programs.jsonl.gz'),
            'counts' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $dir;
    }

    /** @return array<string, mixed> one programs.jsonl.gz record as tools/booklet/build_1405.py writes it */
    private function program(string $code, array $overrides = []): array
    {
        return $overrides + [
            'code' => $code,
            'source_row' => (int) $code,
            'page' => 47,
            'printed_page' => 46,
            'part' => 'health',
            'part_heading' => 'رشته‌محل‌های دانشگاه‌های وزارت بهداشت، درمان و آموزش پزشکی',
            'subpart' => null,
            'section' => 'استان آذربایجان شرقی - دانشگاه علوم پزشکی و خدمات بهداشتی درمانی تبریز',
            'province' => 'آذربایجان شرقی',
            'native_province' => null,
            'city' => 'تبریز',
            'city_how' => 'از نام دانشگاه',
            'institution' => 'دانشگاه علوم پزشکی و خدمات بهداشتی درمانی تبریز',
            'institution_province' => 'آذربایجان شرقی',
            'institution_city' => 'تبریز',
            'campus' => null,
            'field' => 'دکتری عمومی پزشکی',
            'course' => 'روزانه',
            'course_type' => 'day',
            'method' => 'با آزمون',
            'admission_type' => 'with_exam',
            'capacity_first' => 54,
            'capacity_second' => null,
            'accepts_female' => true,
            'accepts_male' => true,
            'female_capacity' => null,
            'male_capacity' => null,
            'description' => 'فاقد خوابگاه',
            'admission_period' => 'نیم‌سال اول و دوم سال ۱۴۰۵',
            'admission_scope' => null,
            'service_location' => null,
            'section_note' => null,
            'field_list' => null,
            'cells' => [],
        ];
    }
}
