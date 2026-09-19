<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StudyProgramsSnapshotSeeder extends Seeder
{
    private const SNAPSHOT_DIR = 'study_programs_1404';

    /** @var array<string, int> */
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

    /** @var array<string, array<int, string>> */
    private const REFERENCE_KEYS = [
        'exam_years' => ['year'],
        'exam_groups' => ['slug'],
        'provinces' => ['normalized_name'],
        'cities' => ['province_id', 'normalized_name'],
        'institutions' => ['normalized_name'],
        'institution_campuses' => ['institution_id', 'normalized_name'],
        'academic_fields' => ['normalized_name'],
        'course_types' => ['slug'],
        'admission_types' => ['slug'],
    ];

    /** @var array<string, array<string, string>> */
    private const FOREIGN_KEY_TABLES = [
        'cities' => ['province_id' => 'provinces'],
        'institutions' => ['province_id' => 'provinces', 'city_id' => 'cities'],
        'institution_campuses' => ['institution_id' => 'institutions', 'province_id' => 'provinces', 'city_id' => 'cities'],
        'study_programs' => [
            'exam_year_id' => 'exam_years',
            'exam_group_id' => 'exam_groups',
            'province_id' => 'provinces',
            'city_id' => 'cities',
            'institution_id' => 'institutions',
            'institution_campus_id' => 'institution_campuses',
            'academic_field_id' => 'academic_fields',
            'course_type_id' => 'course_types',
            'admission_type_id' => 'admission_types',
        ],
        'study_program_review_records' => [
            'exam_year_id' => 'exam_years',
            'exam_group_id' => 'exam_groups',
        ],
    ];

    /** @var array<string, array<int, string>> */
    private array $columns = [];

    public function run(): void
    {
        $snapshotPath = $this->snapshotPath();
        $manifestPath = $snapshotPath.DIRECTORY_SEPARATOR.'manifest.json';

        if (! is_file($manifestPath)) {
            throw new RuntimeException("Study-program snapshot manifest not found: {$manifestPath}");
        }

        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $this->verifyManifest($manifest, $snapshotPath);

        $this->command?->info('Seeding study-program snapshot '.$manifest['snapshot'].' (safe upsert mode)');

        // Snapshot ids are deliberately never inserted. Production may contain manual
        // catalogue records with the same numeric ids, so rows are matched by natural keys.
        $sourceIdMaps = [];

        foreach (self::TABLES as $table => $chunkSize) {
            $count = $this->seedTable($table, $snapshotPath.DIRECTORY_SEPARATOR.$table.'.jsonl.gz', $chunkSize, $sourceIdMaps);
            $this->command?->line("  {$table}: {$count}");
        }
    }

    protected function snapshotPath(): string
    {
        return database_path('seeders/data/'.self::SNAPSHOT_DIR);
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

    /**
     * @param array<string, array<int, int>> $sourceIdMaps
     */
    private function seedTable(string $table, string $file, int $chunkSize, array &$sourceIdMaps): int
    {
        if (isset(self::REFERENCE_KEYS[$table])) {
            $count = 0;
            foreach ($this->readRows($file) as $row) {
                $this->seedReferenceRow($table, $row, $sourceIdMaps);
                $count++;
            }

            return $count;
        }

        $count = 0;
        $chunk = [];
        foreach ($this->readRows($file) as $row) {
            $chunk[] = $row;
            $count++;

            if (count($chunk) >= $chunkSize) {
                $this->seedDataChunk($table, $chunk, $sourceIdMaps);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->seedDataChunk($table, $chunk, $sourceIdMaps);
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, array<int, int>> $sourceIdMaps
     */
    private function seedReferenceRow(string $table, array $row, array &$sourceIdMaps): void
    {
        if (! array_key_exists('id', $row)) {
            throw new RuntimeException("Snapshot {$table} row has no source id.");
        }

        $values = $this->prepareRow($table, $row, $sourceIdMaps);
        $identity = [];
        foreach (self::REFERENCE_KEYS[$table] as $column) {
            if (! array_key_exists($column, $values)) {
                throw new RuntimeException("Snapshot {$table} row is missing {$column}.");
            }
            $identity[$column] = $values[$column];
        }

        $id = DB::table($table)->where($identity)->value('id');
        if ($id === null) {
            DB::table($table)->insert($values);
            $id = DB::table($table)->where($identity)->value('id');
        }

        if ($id === null) {
            throw new RuntimeException("Could not resolve {$table} snapshot row.");
        }

        $sourceIdMaps[$table][(int) $row['id']] = (int) $id;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, array<int, int>> $sourceIdMaps
     */
    private function seedDataChunk(string $table, array $rows, array $sourceIdMaps): void
    {
        $values = array_map(fn (array $row): array => $this->prepareRow($table, $row, $sourceIdMaps), $rows);

        if ($table === 'study_programs') {
            $this->seedStudyPrograms($values);

            return;
        }

        if ($table === 'study_program_review_records') {
            $this->seedReviewRecords($values);

            return;
        }

        throw new RuntimeException("Unsupported snapshot table: {$table}");
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function seedStudyPrograms(array $rows): void
    {
        $hashes = array_column($rows, 'identity_hash');
        if (in_array(null, $hashes, true) || in_array('', $hashes, true)) {
            throw new RuntimeException('Study-program snapshot row has no identity hash.');
        }

        $select = ['id', 'identity_hash', 'source_hash'];
        if ($this->hasColumn('study_programs', 'source_type')) {
            $select[] = 'source_type';
        }

        $existing = DB::table('study_programs')
            ->whereIn('identity_hash', $hashes)
            ->get($select)
            ->keyBy('identity_hash');

        $insert = [];
        $update = [];
        foreach ($rows as $row) {
            if ($this->hasColumn('study_programs', 'source_type')) {
                $row['source_type'] = 'snapshot';
            }
            if ($this->hasColumn('study_programs', 'is_active')) {
                $row['is_active'] = true;
            }

            $current = $existing->get($row['identity_hash']);
            if ($current === null) {
                $insert[] = $row;
                continue;
            }

            if (($current->source_type ?? null) === 'manual') {
                continue;
            }

            if (($current->source_hash ?? null) !== ($row['source_hash'] ?? null)
                || ($current->source_type ?? null) !== 'snapshot') {
                $update[] = $row;
            }
        }

        DB::transaction(function () use ($insert, $update): void {
            if ($insert !== []) {
                DB::table('study_programs')->insert($insert);
            }

            if ($update !== []) {
                $updateColumns = array_values(array_diff(array_keys($update[0]), ['identity_hash', 'created_at']));
                DB::table('study_programs')->upsert($update, ['identity_hash'], $updateColumns);
            }
        });
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function seedReviewRecords(array $rows): void
    {
        $hashes = array_column($rows, 'source_hash');
        if (in_array(null, $hashes, true) || in_array('', $hashes, true)) {
            throw new RuntimeException('Study-program review snapshot row has no source hash.');
        }

        $existing = DB::table('study_program_review_records')
            ->whereIn('source_hash', $hashes)
            ->pluck('source_hash')
            ->flip();

        $insert = array_values(array_filter($rows, fn (array $row): bool => ! $existing->has($row['source_hash'])));
        if ($insert !== []) {
            DB::transaction(fn () => DB::table('study_program_review_records')->insert($insert));
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, array<int, int>> $sourceIdMaps
     * @return array<string, mixed>
     */
    private function prepareRow(string $table, array $row, array $sourceIdMaps): array
    {
        $values = array_intersect_key($row, array_flip($this->columnsFor($table)));
        unset($values['id']);

        foreach (self::FOREIGN_KEY_TABLES[$table] ?? [] as $column => $relatedTable) {
            if (! array_key_exists($column, $values) || $values[$column] === null) {
                continue;
            }

            $sourceId = (int) $values[$column];
            if (! isset($sourceIdMaps[$relatedTable][$sourceId])) {
                throw new RuntimeException("Could not map {$table}.{$column} snapshot id {$sourceId}.");
            }

            $values[$column] = $sourceIdMaps[$relatedTable][$sourceId];
        }

        return $values;
    }

    /** @return array<int, string> */
    private function columnsFor(string $table): array
    {
        return $this->columns[$table] ??= Schema::getColumnListing($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columnsFor($table), true);
    }

    /** @return iterable<array<string, mixed>> */
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
                if ($line !== '') {
                    yield json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                }
            }
        } finally {
            gzclose($handle);
        }
    }
}
