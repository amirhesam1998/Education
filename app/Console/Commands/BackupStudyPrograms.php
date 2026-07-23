<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupStudyPrograms extends Command
{
    protected $signature = 'education:backup-study-programs {--dir=}';
    protected $description = 'Backup study-program subsystem tables before destructive replacement.';

    private array $tables = [
        'study_programs',
        'study_program_review_records',
        'study_program_imports',
        'study_program_import_failures',
        'exam_years',
        'exam_groups',
        'provinces',
        'cities',
        'institutions',
        'institution_campuses',
        'academic_fields',
        'course_types',
        'admission_types',
    ];

    public function handle(): int
    {
        $root = storage_path('app/backups/study-programs');
        $dir = $this->option('dir') ?: $root.'/'.now()->format('Y-m-d_His');

        if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
            $this->error('ساخت پوشه بکاپ ممکن نیست.');
            return self::FAILURE;
        }

        $files = [];
        $counts = [];
        $schemas = [];
        $indexes = [];

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $counts[$table] = DB::table($table)->count();
            $schemas[$table] = Schema::getColumns($table);
            $indexes[$table] = Schema::getIndexes($table);
            $path = $dir.'/'.$table.'.jsonl';
            $handle = fopen($path, 'wb');

            DB::table($table)->orderBy('id')->chunk(1000, function ($rows) use ($handle): void {
                foreach ($rows as $row) {
                    fwrite($handle, json_encode($row, JSON_UNESCAPED_UNICODE).PHP_EOL);
                }
            });

            fclose($handle);
            $files[basename($path)] = hash_file('sha256', $path);
        }

        $metadata = [
            'schemas' => $schemas,
            'indexes' => $indexes,
            'migrations' => Artisan::call('migrate:status') === 0 ? Artisan::output() : null,
            'source_files' => Schema::hasTable('study_programs')
                ? DB::table('study_programs')->select('source_file')->distinct()->pluck('source_file')->values()->all()
                : [],
            'source_hashes_count' => Schema::hasTable('study_programs')
                ? DB::table('study_programs')->distinct()->count('source_hash')
                : 0,
        ];

        $metadataPath = $dir.'/schema_metadata.json';
        file_put_contents($metadataPath, json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $files[basename($metadataPath)] = hash_file('sha256', $metadataPath);

        $manifest = [
            'backup_timestamp' => now()->toDateTimeString(),
            'database_connection' => DB::connection()->getName(),
            'affected_tables' => array_keys($counts),
            'row_counts' => $counts,
            'files' => $files,
            'status' => 'success',
        ];

        $manifestPath = $dir.'/manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        if (! is_readable($manifestPath)) {
            $this->error('اعتبارسنجی بکاپ ناموفق بود.');
            return self::FAILURE;
        }

        $this->info($dir);
        $this->table(['table', 'rows'], collect($counts)->map(fn ($count, $table) => [$table, $count])->all());

        return self::SUCCESS;
    }
}
