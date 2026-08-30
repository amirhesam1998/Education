<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StudyProgramsSnapshotSeeder extends Seeder
{
    private const SNAPSHOT_DIR = 'study_programs_1404';

    /**
     * Ordered by foreign-key dependencies.
     *
     * This seeder restores the cleaned 1404 study-program catalog snapshot.
     * Default mode is idempotent upsert. To replace current catalog tables first:
     * STUDY_PROGRAM_SNAPSHOT_REPLACE=true php artisan db:seed --class=StudyProgramsSnapshotSeeder
     */
    private const TABLES = [
        'exam_years' => 250,
        'exam_groups' => 250,
        'provinces' => 500,
        'cities' => 1000,
        'institutions' => 1000,
        'institution_campuses' => 1000,
        'academic_fields' => 1000,
        'course_types' => 250,
        'admission_types' => 250,
        'study_programs' => 500,
        'study_program_review_records' => 500,
    ];

    public function run(): void
    {
        $snapshotPath = database_path('seeders/data/'.self::SNAPSHOT_DIR);
        $manifestPath = $snapshotPath.DIRECTORY_SEPARATOR.'manifest.json';

        if (! is_file($manifestPath)) {
            throw new RuntimeException("Study-program snapshot manifest not found: {$manifestPath}");
        }

        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $this->verifyManifest($manifest, $snapshotPath);

        $replace = filter_var(env('STUDY_PROGRAM_SNAPSHOT_REPLACE', false), FILTER_VALIDATE_BOOL);

        $this->command?->info('Seeding study-program snapshot '.$manifest['snapshot'].' ('.($replace ? 'replace' : 'upsert').' mode)');

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($snapshotPath, $replace): void {
                if ($replace) {
                    foreach (array_reverse(array_keys(self::TABLES)) as $table) {
                        DB::table($table)->delete();
                    }
                }

                foreach (self::TABLES as $table => $chunkSize) {
                    $count = $this->seedTable($table, $snapshotPath.DIRECTORY_SEPARATOR.$table.'.jsonl.gz', $chunkSize);
                    $this->command?->line("  {$table}: {$count}");
                }
            });

            // MySQL ALTER TABLE causes an implicit commit, so AUTO_INCREMENT repair must
            // happen after Laravel's transaction has committed.
            foreach (array_keys(self::TABLES) as $table) {
                $this->resetAutoIncrement($table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function verifyManifest(array $manifest, string $snapshotPath): void
    {
        if (($manifest['format'] ?? null) !== 'jsonl.gz') {
            throw new RuntimeException('Unsupported study-program snapshot format.');
        }

        foreach (array_keys(self::TABLES) as $table) {
            $file = $snapshotPath.DIRECTORY_SEPARATOR.$table.'.jsonl.gz';
            if (! is_file($file)) {
                throw new RuntimeException("Snapshot table file not found: {$file}");
            }

            $expectedHash = $manifest['tables'][$table]['sha256'] ?? null;
            if (! $expectedHash || ! hash_equals($expectedHash, hash_file('sha256', $file))) {
                throw new RuntimeException("Snapshot checksum mismatch for {$table}.");
            }
        }
    }

    private function seedTable(string $table, string $file, int $chunkSize): int
    {
        $columns = Schema::getColumnListing($table);
        $updateColumns = array_values(array_filter($columns, fn (string $column): bool => $column !== 'id'));
        $chunk = [];
        $count = 0;

        foreach ($this->readRows($file) as $row) {
            $chunk[] = array_intersect_key($row, array_flip($columns));
            $count++;

            if (count($chunk) >= $chunkSize) {
                $this->upsertChunk($table, $chunk, $updateColumns);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->upsertChunk($table, $chunk, $updateColumns);
        }

        return $count;
    }

    private function upsertChunk(string $table, array $chunk, array $updateColumns): void
    {
        if ($updateColumns === []) {
            DB::table($table)->insertOrIgnore($chunk);
            return;
        }

        DB::table($table)->upsert($chunk, ['id'], $updateColumns);
    }

    private function readRows(string $file): iterable
    {
        $handle = gzopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Could not open snapshot file: {$file}");
        }

        try {
            while (! gzeof($handle)) {
                $line = gzgets($handle);
                if ($line === false) {
                    break;
                }

                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                yield json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            }
        } finally {
            gzclose($handle);
        }
    }

    private function resetAutoIncrement(string $table): void
    {
        $maxId = (int) DB::table($table)->max('id');
        if ($maxId < 1) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `'.$table.'` AUTO_INCREMENT = '.($maxId + 1));
        } elseif ($driver === 'sqlite') {
            DB::table('sqlite_sequence')->updateOrInsert(['name' => $table], ['seq' => $maxId]);
        }
    }
}
