<?php

namespace Tests\Feature;

use Database\Seeders\StudyProgramsSnapshotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudyProgramsSnapshotSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_idempotent_and_preserves_manual_catalogue_records(): void
    {
        $now = now()->toDateTimeString();
        $manualYearId = DB::table('exam_years')->insertGetId(['year' => 1500, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $manualGroupId = DB::table('exam_groups')->insertGetId(['name' => 'دستی', 'slug' => 'manual', 'created_at' => $now, 'updated_at' => $now]);
        $manualIdentity = hash('sha256', 'manual-program');
        DB::table('study_programs')->insert([
            'exam_year_id' => $manualYearId,
            'exam_group_id' => $manualGroupId,
            'code' => 'MANUAL-1',
            'source_file' => 'manual',
            'source_hash' => hash('sha256', 'manual-source'),
            'identity_hash' => $manualIdentity,
            'validation_status' => 'validated',
            'is_active' => true,
            'source_type' => 'manual',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $seeder = new class($this->writeSnapshot()) extends StudyProgramsSnapshotSeeder
        {
            public function __construct(private readonly string $path) {}

            protected function snapshotPath(): string
            {
                return $this->path;
            }
        };

        $seeder->run();
        DB::table('study_program_review_records')->where('source_hash', 'review-source-hash')->update(['status' => 'approved']);
        $seeder->run();

        $this->assertSame(2, DB::table('study_programs')->count());
        $this->assertSame('manual', DB::table('study_programs')->where('identity_hash', $manualIdentity)->value('source_type'));
        $this->assertSame('snapshot', DB::table('study_programs')->where('identity_hash', 'snapshot-identity-hash')->value('source_type'));
        $this->assertSame(1, DB::table('study_program_review_records')->count());
        $this->assertSame('approved', DB::table('study_program_review_records')->where('source_hash', 'review-source-hash')->value('status'));
    }

    private function writeSnapshot(): string
    {
        $path = storage_path('framework/testing/study-program-snapshot');
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $now = now()->toDateTimeString();
        $tables = [
            'exam_years' => [['id' => 1, 'year' => 1404, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]],
            'exam_groups' => [['id' => 1, 'name' => 'تجربی', 'slug' => 'tajrobi', 'created_at' => $now, 'updated_at' => $now]],
            'provinces' => [['id' => 1, 'name' => 'گیلان', 'normalized_name' => 'گیلان', 'created_at' => $now, 'updated_at' => $now]],
            'cities' => [['id' => 1, 'province_id' => 1, 'name' => 'رشت', 'normalized_name' => 'رشت', 'created_at' => $now, 'updated_at' => $now]],
            'institutions' => [['id' => 1, 'name' => 'دانشگاه گیلان', 'normalized_name' => 'دانشگاه گیلان', 'province_id' => 1, 'city_id' => 1, 'created_at' => $now, 'updated_at' => $now]],
            'institution_campuses' => [['id' => 1, 'institution_id' => 1, 'province_id' => 1, 'city_id' => 1, 'name' => 'مرکزی', 'normalized_name' => 'مرکزی', 'created_at' => $now, 'updated_at' => $now]],
            'academic_fields' => [['id' => 1, 'name' => 'پرستاری', 'normalized_name' => 'پرستاری', 'created_at' => $now, 'updated_at' => $now]],
            'course_types' => [['id' => 1, 'name' => 'روزانه', 'slug' => 'day', 'created_at' => $now, 'updated_at' => $now]],
            'admission_types' => [['id' => 1, 'name' => 'با آزمون', 'slug' => 'with_exam', 'created_at' => $now, 'updated_at' => $now]],
            'study_programs' => [[
                'id' => 1, 'exam_year_id' => 1, 'exam_group_id' => 1, 'code' => '12345', 'province_id' => 1, 'city_id' => 1,
                'institution_id' => 1, 'institution_campus_id' => 1, 'academic_field_id' => 1, 'course_type_id' => 1,
                'admission_type_id' => 1, 'source_file' => 'snapshot.pdf', 'source_hash' => 'snapshot-source-hash',
                'identity_hash' => 'snapshot-identity-hash', 'validation_status' => 'validated', 'raw_data' => '{}',
                'created_at' => $now, 'updated_at' => $now,
            ]],
            'study_program_review_records' => [[
                'id' => 1, 'exam_year_id' => 1, 'exam_group_id' => 1, 'code' => '54321', 'source_file' => 'snapshot.pdf',
                'source_hash' => 'review-source-hash', 'identity_hash' => 'review-identity-hash', 'raw_data' => '{}',
                'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
            ]],
        ];

        $manifest = ['snapshot' => 'test', 'format' => 'jsonl.gz', 'tables' => []];
        foreach ($tables as $table => $rows) {
            $file = $path.DIRECTORY_SEPARATOR.$table.'.jsonl.gz';
            file_put_contents($file, gzencode(implode("\n", array_map(fn (array $row): string => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $rows))."\n"));
            $manifest['tables'][$table] = ['sha256' => hash_file('sha256', $file)];
        }

        file_put_contents($path.DIRECTORY_SEPARATOR.'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $path;
    }
}
