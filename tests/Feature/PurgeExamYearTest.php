<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeExamYearTest extends TestCase
{
    use RefreshDatabase;

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        // The purge writes its backup under storage/app; keep it out of the real storage folder.
        $this->storage = sys_get_temp_dir().'/purge-exam-year-test-'.getmypid();
        File::ensureDirectoryExists($this->storage.'/app');
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    #[Test]
    public function it_backs_up_then_removes_the_year_and_the_reference_rows_only_it_used(): void
    {
        $old = $this->year(1404);
        $new = $this->year(1405);
        $group = $this->row('exam_groups', ['name' => 'تجربی', 'slug' => 'tajrobi']);
        $gilan = $this->row('provinces', ['name' => 'گیلان', 'normalized_name' => 'گیلان']);
        $tehran = $this->row('provinces', ['name' => 'تهران', 'normalized_name' => 'تهران']);
        $typo = $this->row('provinces', ['name' => 'گيلان شرقي', 'normalized_name' => 'گیلان شرقی']);
        $nativeOnly = $this->row('provinces', ['name' => 'استان آزمایشی', 'normalized_name' => 'استان آزمایشی']);
        $rasht = $this->row('cities', ['province_id' => $gilan, 'name' => 'رشت', 'normalized_name' => 'رشت']);
        $lahijan = $this->row('cities', ['province_id' => $typo, 'name' => 'لاهيجان', 'normalized_name' => 'لاهیجان']);
        $shared = $this->row('institutions', ['name' => 'دانشگاه گیلان', 'normalized_name' => 'دانشگاه گیلان', 'province_id' => $gilan, 'city_id' => $rasht]);
        $oldOnly = $this->row('institutions', ['name' => 'دا نشگاه گیلان', 'normalized_name' => 'دا نشگاه گیلان', 'province_id' => $typo, 'city_id' => $lahijan]);
        $oldCampus = $this->row('institution_campuses', ['institution_id' => $shared, 'name' => 'پردیس قدیم', 'normalized_name' => 'پردیس قدیم', 'province_id' => $typo, 'city_id' => $lahijan]);
        $nursing = $this->row('academic_fields', ['name' => 'پرستاری', 'normalized_name' => 'پرستاری']);
        $oldField = $this->row('academic_fields', ['name' => 'پرستاری (قدیم)', 'normalized_name' => 'پرستاری (قدیم)']);

        $this->program($new, $group, '1', ['province_id' => $gilan, 'native_province_id' => $nativeOnly, 'city_id' => $rasht, 'institution_id' => $shared, 'academic_field_id' => $nursing]);
        $this->program($old, $group, '1', ['province_id' => $gilan, 'city_id' => $rasht, 'institution_id' => $shared, 'academic_field_id' => $nursing]);
        $this->program($old, $group, '2', ['province_id' => $typo, 'city_id' => $lahijan, 'institution_id' => $oldOnly, 'institution_campus_id' => $oldCampus, 'academic_field_id' => $oldField]);
        $this->program($old, $group, '3', ['source_type' => 'manual']);
        $this->row('study_program_review_records', ['exam_year_id' => $old, 'exam_group_id' => $group, 'code' => '9', 'source_file' => 'x', 'source_hash' => 'r', 'identity_hash' => 'r', 'raw_data' => '{}', 'status' => 'pending']);
        $import = $this->row('study_program_imports', ['exam_year_id' => $old, 'exam_group_id' => $group, 'source_path' => 'x', 'source_filename' => 'x', 'file_hash' => str_repeat('a', 64), 'status' => 'completed']);
        $this->row('study_program_import_failures', ['import_id' => $import, 'source_row' => 1, 'reason' => 'x']);

        $this->artisan('education:purge-exam-year', ['year' => 1404, '--force' => true])->assertSuccessful();

        $this->assertSame([1405], DB::table('exam_years')->pluck('year')->map(fn ($year) => (int) $year)->all());
        $this->assertSame([$new], DB::table('study_programs')->pluck('exam_year_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(0, DB::table('study_program_review_records')->count());
        $this->assertSame(0, DB::table('study_program_imports')->count());
        $this->assertSame(0, DB::table('study_program_import_failures')->count());
        $this->assertSame([$rasht], $this->ids('cities'));
        $this->assertSame([$shared], $this->ids('institutions'));
        $this->assertSame([], $this->ids('institution_campuses'));
        $this->assertSame([$nursing], $this->ids('academic_fields'));
        // Official provinces stay even with no programs, and so does one a program names as its native province; the misspelled one goes.
        $this->assertSame([$gilan, $tehran, $nativeOnly], $this->ids('provinces'));

        $backups = glob($this->storage.'/app/backups/study-programs/*/backup-manifest.json');
        $this->assertCount(1, $backups);
        $this->assertStringContainsString('study_programs', (string) file_get_contents($backups[0]));

        // A new backup would get a new folder name, which is stamped to the second.
        $this->travel(2)->seconds();
        $this->artisan('education:purge-exam-year', ['year' => 1404, '--force' => true])
            ->expectsOutputToContain('در دیتابیس نیست')
            ->assertSuccessful();
        $this->assertCount(1, glob($this->storage.'/app/backups/study-programs/*/backup-manifest.json'));
    }

    #[Test]
    public function it_deletes_nothing_without_force(): void
    {
        $year = $this->year(1404);
        $group = $this->row('exam_groups', ['name' => 'تجربی', 'slug' => 'tajrobi']);
        $this->program($year, $group, '1');

        $this->artisan('education:purge-exam-year', ['year' => 1404])->assertFailed();

        $this->assertSame(1, DB::table('study_programs')->count());
        $this->assertSame(1, DB::table('exam_years')->count());
    }

    #[Test]
    public function it_refuses_to_empty_the_catalogue(): void
    {
        $year = $this->year(1404);
        $group = $this->row('exam_groups', ['name' => 'تجربی', 'slug' => 'tajrobi']);
        $this->program($year, $group, '1');
        $this->program($this->year(1405), $group, '1', ['is_active' => false]);

        $this->artisan('education:purge-exam-year', ['year' => 1404, '--force' => true])->assertFailed();

        $this->assertSame(2, DB::table('study_programs')->count());
        $this->assertSame([], glob($this->storage.'/app/backups/study-programs/*'));
    }

    private function year(int $year): int
    {
        return $this->row('exam_years', ['year' => $year, 'is_active' => true]);
    }

    private function program(int $year, int $group, string $code, array $values = []): int
    {
        return $this->row('study_programs', $values + [
            'exam_year_id' => $year,
            'exam_group_id' => $group,
            'code' => $code,
            'source_file' => 'test',
            'source_hash' => hash('sha256', $year.$code),
            'identity_hash' => hash('sha256', $year.'|'.$code),
            'validation_status' => 'validated',
            'is_active' => true,
            'source_type' => 'booklet',
        ]);
    }

    private function row(string $table, array $values): int
    {
        $now = now()->toDateTimeString();

        return (int) DB::table($table)->insertGetId($values + ['created_at' => $now, 'updated_at' => $now]);
    }

    /** @return list<int> */
    private function ids(string $table): array
    {
        return DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
