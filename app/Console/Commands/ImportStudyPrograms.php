<?php

namespace App\Console\Commands;

use App\Services\StudyPrograms\StudyProgramFileDiscovery;
use App\Services\StudyPrograms\StudyProgramImporter;
use Illuminate\Console\Command;

class ImportStudyPrograms extends Command
{
    protected $signature = 'education:import-study-programs
        {--path= : پوشه فایل‌های V2}
        {--file= : مسیر یک فایل مشخص}
        {--year=1404 : سال آزمون}
        {--group= : گروه آزمون}
        {--dry-run : بررسی بدون ذخیره}
        {--only-validated : فقط ردیف‌های تأیید ساختاری}
        {--include-review : ثبت ردیف‌های نیازمند بررسی در staging}
        {--chunk=1000 : اندازه chunk}
        {--force : پردازش دوباره فایل تکراری}
        {--queue : فعلاً به اجرای مستقیم تبدیل می‌شود}
        {--report= : ذخیره گزارش JSON}
        {--skip-reference : عدم import مرجع سراسری}
        {--rollback-import= : در این نسخه پشتیبانی نمی‌شود}';

    protected $description = 'Import V2 Iranian university study programs.';

    public function handle(StudyProgramFileDiscovery $files, StudyProgramImporter $importer): int
    {
        $started = microtime(true);
        $path = $this->option('path') ?: base_path('Docs/v2');
        $detected = $files->discover($path, $this->option('group'), $this->option('file'));
        $totals = ['files_detected' => $detected->count(), 'files_processed' => 0, 'reference_rows' => 0, 'reference_skipped' => 0, 'total_rows' => 0, 'inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'review_rows' => 0, 'skipped' => 0, 'failed' => 0];

        if ($this->option('rollback-import')) {
            $this->error('rollback-import هنوز پیاده‌سازی نشده است.');
            return self::FAILURE;
        }

        if ($detected->isEmpty()) {
            $this->error('فایل V2 معتبری پیدا نشد.');
            return self::FAILURE;
        }

        if ($this->option('queue')) {
            $this->warn('گزینه صف فعلاً به اجرای مستقیم تبدیل شد.');
        }

        if (! $this->option('skip-reference')) {
            $reference = $files->referenceFile($path);
            if (! $reference) {
                $this->error('فایل مرجع سراسری پیدا نشد.');
                return self::FAILURE;
            }

            $this->info('در حال پردازش مرجع سراسری: '.basename($reference));
            foreach ($importer->importReference($reference, (bool) $this->option('dry-run')) as $key => $value) {
                $totals[$key] += $value;
            }
        }

        foreach ($detected as $file) {
            $this->info('در حال پردازش: '.$file['filename']);
            $summary = $importer->importFile($file['path'], (int) $this->option('year'), $this->option('group') ?: $file['group'], [
                'dry_run' => (bool) $this->option('dry-run'),
                'only_validated' => (bool) $this->option('only-validated') && ! $this->option('include-review'),
                'force' => (bool) $this->option('force'),
                'chunk' => (int) $this->option('chunk'),
            ]);
            $totals['files_processed']++;

            foreach ($summary as $key => $value) {
                if (isset($totals[$key])) {
                    $totals[$key] += (int) $value;
                }
            }
        }

        $totals['execution_time_seconds'] = round(microtime(true) - $started, 2);
        $this->table(['metric', 'value'], collect($totals)->map(fn ($value, $key) => [$key, $value])->all());

        if ($this->option('report')) {
            file_put_contents($this->option('report'), json_encode($totals, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }
}

