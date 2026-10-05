<?php

namespace App\Services\StudyPrograms;

use App\Models\ExamGroup;
use App\Models\ExamYear;
use App\Models\StudyProgramImport;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Loads a field-selection booklet built by tools/booklet (manifest.json + programs.jsonl.gz)
 * into the catalogue. Every name in the file is already in its one canonical spelling, so
 * reference rows are matched by their normalized name and renamed to that spelling.
 *
 * Re-running is safe: programs are matched by year, group and code, unchanged rows are left
 * alone, and rows an admin edited (source_type = manual) are never overwritten.
 */
class BookletImporter
{
    public const SOURCE_TYPE = 'booklet';

    private const CHUNK = 500;

    /** @var array<string, array<string, int>> */
    private array $ids = [];

    public function __construct(private readonly PersianTextNormalizer $normalizer)
    {
    }

    /** @return list<string> booklet directories under $root, e.g. .../1405/tajrobi */
    public function discover(string $root): array
    {
        $dirs = array_map('dirname', glob($root.'/*/*/manifest.json') ?: []);
        sort($dirs);

        return $dirs;
    }

    public function manifest(string $dir): array
    {
        $manifest = json_decode((string) file_get_contents($dir.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);

        if (($manifest['format'] ?? null) !== 'booklet-programs-v1') {
            throw new RuntimeException("Unsupported booklet format in {$dir}.");
        }

        $file = $dir.'/'.$manifest['programs_file'];
        if (! is_file($file) || ! hash_equals($manifest['programs_sha256'], hash_file('sha256', $file))) {
            throw new RuntimeException("Booklet programs file is missing or does not match its checksum: {$file}");
        }

        return $manifest;
    }

    public function bookletId(array $manifest): string
    {
        return $manifest['year'].'/'.$manifest['group'];
    }

    /** True when the last completed import of this booklet loaded exactly this programs file. */
    public function isImported(array $manifest): bool
    {
        $latest = StudyProgramImport::query()
            ->where('source_file', $this->bookletId($manifest))
            ->where('status', 'completed')
            ->latest('id')
            ->value('file_hash');

        return $latest !== null && hash_equals($latest, $manifest['programs_sha256']);
    }

    /** @return array<string, int> */
    public function import(string $dir): array
    {
        $manifest = $this->manifest($dir);
        $this->ids = [];

        $year = ExamYear::query()->firstOrCreate(['year' => (int) $manifest['year']], ['is_active' => true]);
        if (! $year->is_active) {
            $year->update(['is_active' => true]);
        }
        $group = ExamGroup::query()->firstOrCreate(['slug' => $manifest['group']], ['name' => $manifest['group_name']]);

        $import = StudyProgramImport::query()->create([
            'exam_year_id' => $year->id,
            'exam_group_id' => $group->id,
            'source_path' => $dir,
            'source_filename' => $manifest['source_file'],
            'source_file' => $this->bookletId($manifest),
            'file_hash' => $manifest['programs_sha256'],
            'status' => 'running',
            'total_rows' => $manifest['programs'],
            'started_at' => now(),
            'metadata' => ['source_sha256' => $manifest['source_sha256'], 'counts' => $manifest['counts']],
        ]);

        try {
            $counts = DB::transaction(fn (): array => $this->load($dir.'/'.$manifest['programs_file'], $manifest, $year->id, $group->id));
        } catch (Throwable $e) {
            // A failed bulk insert's message holds every bound value; keep it within a TEXT column.
            $import->update(['status' => 'failed', 'finished_at' => now(), 'error_message' => mb_strcut($e->getMessage(), 0, 60000)]);

            throw $e;
        }

        $import->update([
            'status' => 'completed',
            'finished_at' => now(),
            'inserted_rows' => $counts['inserted'],
            'updated_rows' => $counts['updated'],
            'unchanged_rows' => $counts['unchanged'],
            'skipped_rows' => $counts['kept_manual'],
            'metadata' => $import->metadata + ['removed_rows' => $counts['removed']],
        ]);

        return $counts;
    }

    /** @return array<string, int> */
    private function load(string $file, array $manifest, int $yearId, int $groupId): array
    {
        $sourceFile = $this->bookletId($manifest).'/'.$manifest['source_file'];
        $existing = [];
        foreach (DB::table('study_programs')->where('exam_year_id', $yearId)->where('exam_group_id', $groupId)
            ->get(['id', 'code', 'source_type', 'source_hash', 'native_province_id']) as $row) {
            $existing[$row->code] = $row;
        }

        $counts = ['total' => 0, 'inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'kept_manual' => 0, 'removed' => 0];
        $seen = [];
        $inserts = [];
        $now = now()->toDateTimeString();

        foreach ($this->lines($file) as $line) {
            $program = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $counts['total']++;
            $code = (string) $program['code'];
            $seen[$code] = true;
            $hash = hash('sha256', $line);
            $current = $existing[$code] ?? null;

            if ($current && $current->source_type === 'manual') {
                $this->fillNativeProvince($current, $program);
                $counts['kept_manual']++;
                continue;
            }

            if ($current && $current->source_hash === $hash) {
                $counts['unchanged']++;
                continue;
            }

            $row = $this->row($program, $manifest, $sourceFile, $yearId, $groupId, $hash) + ['updated_at' => $now];

            if ($current) {
                // The row is checked again on write: an admin edit saved since it was read wins.
                if (DB::table('study_programs')->where('id', $current->id)->where('source_type', '<>', 'manual')->update($row)) {
                    $counts['updated']++;
                } else {
                    $counts['kept_manual']++;
                }
            } else {
                $inserts[] = $row + ['created_at' => $now];
                $counts['inserted']++;
                if (count($inserts) >= self::CHUNK) {
                    DB::table('study_programs')->insert($inserts);
                    $inserts = [];
                }
            }
        }

        $inserts && DB::table('study_programs')->insert($inserts);

        if ($counts['total'] !== (int) $manifest['programs']) {
            throw new RuntimeException("Booklet has {$counts['total']} programs, manifest says {$manifest['programs']}.");
        }

        // Codes that an earlier version of this booklet had and this one does not.
        $stale = collect($existing)
            ->filter(fn ($row, $code) => ! isset($seen[(string) $code]) && $row->source_type === self::SOURCE_TYPE)
            ->pluck('id');
        foreach ($stale->chunk(self::CHUNK) as $ids) {
            $counts['removed'] += DB::table('study_programs')->whereIn('id', $ids->all())->where('source_type', self::SOURCE_TYPE)->delete();
        }

        return $counts;
    }

    private function row(array $p, array $manifest, string $sourceFile, int $yearId, int $groupId, string $hash): array
    {
        $provinceId = $this->province($p['province']);
        $cityId = $this->city($provinceId, $p['city']);
        $institutionId = $this->institution($p);
        $campusId = $p['campus'] !== null ? $this->campus($institutionId, $p['campus'], $provinceId, $cityId) : null;

        return [
            'exam_year_id' => $yearId,
            'exam_group_id' => $groupId,
            'code' => (string) $p['code'],
            'province_id' => $provinceId,
            'native_province_id' => ($p['native_province'] ?? null) !== null ? $this->province($p['native_province']) : null,
            'city_id' => $cityId,
            'institution_id' => $institutionId,
            'institution_campus_id' => $campusId,
            'academic_field_id' => $this->named('academic_fields', $p['field']),
            'course_type_id' => $this->type('course_types', $p['course_type'], StudyProgramValueMapper::COURSE_TYPES),
            'admission_type_id' => $this->type('admission_types', $p['admission_type'], StudyProgramValueMapper::ADMISSION_TYPES),
            'original_course_type' => $p['course'],
            'original_admission_type' => $p['method'],
            'accepts_male' => $p['accepts_male'],
            'accepts_female' => $p['accepts_female'],
            'first_semester_capacity' => $p['capacity_first'],
            'second_semester_capacity' => $p['capacity_second'],
            'female_capacity' => $p['female_capacity'],
            'male_capacity' => $p['male_capacity'],
            'admission_period' => $p['admission_period'],
            'admission_scope' => $p['admission_scope'],
            'service_location' => $p['service_location'],
            'description' => $p['description'],
            'section_note' => $p['section_note'],
            'booklet_page' => $p['page'],
            'booklet_section' => $p['section'],
            'city_detection_method' => $p['city_how'],
            'source_file' => $sourceFile,
            'source_type' => self::SOURCE_TYPE,
            'source_row' => $p['source_row'],
            'source_hash' => $hash,
            // Same recipe as manual programs, so an admin cannot add a second copy of a booklet program.
            'identity_hash' => hash('sha256', implode('|', [
                $manifest['year'],
                $manifest['group'],
                $p['code'],
                $this->normalizer->lookup($p['institution']),
                $this->normalizer->lookup($p['campus'] ?? ''),
                $this->normalizer->lookup($p['field']),
                $p['course_type'],
            ])),
            'validation_status' => 'validated',
            'is_active' => true,
            'raw_data' => json_encode([
                'booklet' => $this->bookletId($manifest),
                'page' => $p['page'],
                'printed_page' => $p['printed_page'],
                'part' => $p['part'],
                'part_heading' => $p['part_heading'],
                'subpart' => $p['subpart'],
                'field_list' => $p['field_list'],
                'cells' => $p['cells'],
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * An admin edit keeps every column it saved. A row edited before programs had a native province
     * still takes the booklet's one, unless an admin has since chosen it on the form.
     */
    private function fillNativeProvince(object $current, array $program): void
    {
        if ($current->native_province_id !== null || ($program['native_province'] ?? null) === null) {
            return;
        }

        $raw = json_decode((string) DB::table('study_programs')->where('id', $current->id)->value('raw_data'), true);
        if (is_array($raw) && array_key_exists('native_province_by_admin', $raw)) {
            return;
        }

        DB::table('study_programs')->where('id', $current->id)->update(['native_province_id' => $this->province($program['native_province'])]);
    }

    private function province(string $name): int
    {
        return $this->ids['provinces'][$name] ??= $this->upsert('provinces', ['normalized_name' => $this->normalizer->lookup($name)], ['name' => $name]);
    }

    private function city(int $provinceId, string $name): int
    {
        return $this->ids['cities'][$provinceId.'|'.$name] ??= $this->upsert(
            'cities',
            ['province_id' => $provinceId, 'normalized_name' => $this->normalizer->lookup($name)],
            ['name' => $name],
        );
    }

    private function institution(array $p): int
    {
        if (isset($this->ids['institutions'][$p['institution']])) {
            return $this->ids['institutions'][$p['institution']];
        }

        $provinceId = $p['institution_province'] !== null ? $this->province($p['institution_province']) : null;
        $cityId = $provinceId !== null ? $this->city($provinceId, $p['institution_city']) : null;

        return $this->ids['institutions'][$p['institution']] = $this->upsert(
            'institutions',
            ['normalized_name' => $this->normalizer->lookup($p['institution'])],
            ['name' => $p['institution'], 'province_id' => $provinceId, 'city_id' => $cityId],
        );
    }

    private function campus(int $institutionId, string $name, int $provinceId, int $cityId): int
    {
        return $this->ids['institution_campuses'][$institutionId.'|'.$name] ??= $this->upsert(
            'institution_campuses',
            ['institution_id' => $institutionId, 'normalized_name' => $this->normalizer->lookup($name)],
            ['name' => $name, 'province_id' => $provinceId, 'city_id' => $cityId],
        );
    }

    private function named(string $table, string $name): int
    {
        return $this->ids[$table][$name] ??= $this->upsert($table, ['normalized_name' => $this->normalizer->lookup($name)], ['name' => $name]);
    }

    /** @param array<string, string> $names */
    private function type(string $table, string $slug, array $names): int
    {
        if (! isset($names[$slug])) {
            throw new RuntimeException("Unknown {$table} slug: {$slug}");
        }

        return $this->ids[$table][$slug] ??= $this->upsert($table, ['slug' => $slug], ['name' => $names[$slug]], updateExisting: false);
    }

    /** The row matching $keys gets $values (the booklet's spelling); a missing row is created. */
    private function upsert(string $table, array $keys, array $values, bool $updateExisting = true): int
    {
        $row = DB::table($table)->where($keys)->first();
        $now = now()->toDateTimeString();

        if (! $row) {
            return (int) DB::table($table)->insertGetId($keys + $values + ['created_at' => $now, 'updated_at' => $now]);
        }

        $changed = array_filter($values, fn ($value, $column) => $row->{$column} != $value, ARRAY_FILTER_USE_BOTH);
        if ($updateExisting && $changed !== []) {
            DB::table($table)->where('id', $row->id)->update($changed + ['updated_at' => $now]);
        }

        return (int) $row->id;
    }

    /** @return \Generator<int, string> */
    private function lines(string $file): \Generator
    {
        $handle = gzopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Cannot read {$file}");
        }

        try {
            while (($line = gzgets($handle)) !== false) {
                $line = rtrim($line, "\n");
                if ($line !== '') {
                    yield $line;
                }
            }
        } finally {
            gzclose($handle);
        }
    }
}
