<?php

namespace App\Services\Azad;

use App\Models\AzadProgram;
use App\Models\AzadProgramImport;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Loads the Azad programmes built by tools/azad (manifest.json + programs.jsonl.gz) into
 * azad_programs. Every text in the file is already in its one canonical spelling.
 *
 * Re-running is safe: programmes are matched by year, booklet, unit code, field code and
 * part-time, unchanged rows are left alone, rows an admin edited or added (source_type =
 * manual) are never overwritten, and booklet rows the file no longer has are removed.
 */
class AzadProgramImporter
{
    public const FORMAT = 'azad-programs-v1';

    private const CHUNK = 500;

    /** @return list<string> year directories under $root, e.g. .../azad/1405 */
    public function discover(string $root): array
    {
        $dirs = array_map('dirname', glob($root.'/*/manifest.json') ?: []);
        sort($dirs);

        return $dirs;
    }

    public function manifest(string $dir): array
    {
        $manifest = json_decode((string) file_get_contents($dir.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);

        if (($manifest['format'] ?? null) !== self::FORMAT) {
            throw new RuntimeException("Unsupported Azad programmes format in {$dir}.");
        }

        $file = $dir.'/'.$manifest['programs_file'];
        if (! is_file($file) || ! hash_equals($manifest['programs_sha256'], hash_file('sha256', $file))) {
            throw new RuntimeException("Azad programmes file is missing or does not match its checksum: {$file}");
        }

        return $manifest;
    }

    /** True when the last completed import of this year loaded exactly this programmes file. */
    public function isImported(array $manifest): bool
    {
        $latest = AzadProgramImport::query()
            ->where('year', (int) $manifest['year'])
            ->where('status', 'completed')
            ->latest('id')
            ->value('programs_sha256');

        return $latest !== null && hash_equals($latest, $manifest['programs_sha256']);
    }

    /** Copies the year's programmes to storage before they are replaced; null when there are none. */
    public function backup(int $year): ?string
    {
        if (! AzadProgram::query()->where('year', $year)->exists()) {
            return null;
        }

        $dir = storage_path('app/backups/azad-programs');
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new RuntimeException("Cannot create {$dir}.");
        }

        $path = $dir.'/'.$year.'_'.now()->format('Y-m-d_His').'.jsonl';
        $handle = fopen($path, 'wb');
        DB::table('azad_programs')->where('year', $year)->orderBy('id')->chunk(1000, function ($rows) use ($handle): void {
            foreach ($rows as $row) {
                fwrite($handle, json_encode($row, JSON_UNESCAPED_UNICODE).PHP_EOL);
            }
        });
        fclose($handle);

        return $path;
    }

    /** @return array<string, int> */
    public function import(string $dir): array
    {
        $manifest = $this->manifest($dir);
        $year = (int) $manifest['year'];

        $import = AzadProgramImport::query()->create([
            'year' => $year,
            'programs_sha256' => $manifest['programs_sha256'],
            'status' => 'running',
            'total_rows' => $manifest['programs'],
            'started_at' => now(),
        ]);

        try {
            $counts = DB::transaction(fn (): array => $this->load($dir.'/'.$manifest['programs_file'], $manifest, $year));
        } catch (Throwable $e) {
            $import->update(['status' => 'failed', 'finished_at' => now(), 'error_message' => mb_strcut($e->getMessage(), 0, 60000)]);

            throw $e;
        }

        $import->update([
            'status' => 'completed',
            'finished_at' => now(),
            'inserted_rows' => $counts['inserted'],
            'updated_rows' => $counts['updated'],
            'unchanged_rows' => $counts['unchanged'],
            'kept_manual_rows' => $counts['kept_manual'],
            'removed_rows' => $counts['removed'],
        ]);

        return $counts;
    }

    public static function identity(string $booklet, string $unitCode, string $fieldCode, bool $partTime): string
    {
        return $booklet.'|'.$unitCode.'|'.$fieldCode.'|'.($partTime ? '1' : '0');
    }

    /** @return array<string, int> */
    private function load(string $file, array $manifest, int $year): array
    {
        $existing = [];
        foreach (DB::table('azad_programs')->where('year', $year)
            ->get(['id', 'booklet', 'unit_code', 'field_code', 'part_time', 'source_type', 'source_hash']) as $row) {
            $existing[self::identity($row->booklet, $row->unit_code, $row->field_code, (bool) $row->part_time)] = $row;
        }

        $counts = ['total' => 0, 'inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'kept_manual' => 0, 'removed' => 0];
        $seen = [];
        $inserts = [];
        $now = now()->toDateTimeString();

        foreach ($this->lines($file) as $line) {
            $p = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $counts['total']++;
            $key = self::identity($p['booklet'], (string) $p['unit_code'], (string) $p['field_code'], (bool) $p['part_time']);
            if (isset($seen[$key])) {
                throw new RuntimeException("Azad programme {$key} is in the file twice.");
            }
            $seen[$key] = true;
            $hash = hash('sha256', $line);
            $current = $existing[$key] ?? null;

            if ($current && $current->source_type === AzadProgram::SOURCE_MANUAL) {
                $counts['kept_manual']++;
                continue;
            }

            if ($current && $current->source_hash === $hash) {
                $counts['unchanged']++;
                continue;
            }

            $row = $this->row($p, $year, $hash) + ['updated_at' => $now];

            if ($current) {
                // Checked again on write: an admin edit saved since the row was read wins.
                if (DB::table('azad_programs')->where('id', $current->id)->where('source_type', '<>', AzadProgram::SOURCE_MANUAL)->update($row)) {
                    $counts['updated']++;
                } else {
                    $counts['kept_manual']++;
                }
            } else {
                $inserts[] = $row + ['created_at' => $now];
                $counts['inserted']++;
                if (count($inserts) >= self::CHUNK) {
                    DB::table('azad_programs')->insert($inserts);
                    $inserts = [];
                }
            }
        }

        $inserts && DB::table('azad_programs')->insert($inserts);

        if ($counts['total'] !== (int) $manifest['programs']) {
            throw new RuntimeException("Azad file has {$counts['total']} programmes, manifest says {$manifest['programs']}.");
        }

        $stale = collect($existing)
            ->filter(fn ($row, $key) => ! isset($seen[$key]) && $row->source_type === AzadProgram::SOURCE_BOOKLET)
            ->pluck('id');
        foreach ($stale->chunk(self::CHUNK) as $ids) {
            $counts['removed'] += DB::table('azad_programs')->whereIn('id', $ids->all())->where('source_type', AzadProgram::SOURCE_BOOKLET)->delete();
        }

        return $counts;
    }

    private function row(array $p, int $year, string $hash): array
    {
        return [
            'year' => $year,
            'booklet' => $p['booklet'],
            'admission' => $p['admission'],
            'level' => $p['level'],
            'province' => $p['province'],
            'city' => $p['city'],
            'unit_code' => (string) $p['unit_code'],
            'unit_name' => $p['unit_name'],
            'self_funded' => (bool) $p['self_funded'],
            'field_code' => (string) $p['field_code'],
            'field_name' => $p['field_name'],
            'part_time' => (bool) $p['part_time'],
            'gender' => $p['gender'],
            'exam_group' => $p['exam_group'],
            'education_group' => $p['education_group'],
            'capacity_first' => $p['capacity_first'],
            'capacity_second' => $p['capacity_second'],
            'booklet_page' => $p['page'],
            'search_text' => AzadProgram::searchText($p),
            'source_type' => AzadProgram::SOURCE_BOOKLET,
            'source_hash' => $hash,
            'is_active' => true,
        ];
    }

    /** @return iterable<string> */
    private function lines(string $file): iterable
    {
        $handle = gzopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Cannot read {$file}.");
        }

        try {
            while (($line = gzgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line !== '') {
                    yield $line;
                }
            }
        } finally {
            gzclose($handle);
        }
    }
}
