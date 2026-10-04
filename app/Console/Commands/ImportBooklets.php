<?php

namespace App\Console\Commands;

use App\Services\StudyPrograms\BookletImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class ImportBooklets extends Command
{
    protected $signature = 'education:import-booklets
        {--path= : پوشه دفترچه‌ها (پیش‌فرض database/seeders/data/booklets)}
        {--again : ورود دوباره حتی اگر همین نسخه قبلاً وارد شده باشد}';

    protected $description = 'Back up the catalogue, then load the committed field-selection booklets (manifest.json + programs.jsonl.gz) that changed since their last import.';

    public function handle(BookletImporter $importer): int
    {
        $root = $this->option('path') ?: database_path('seeders/data/booklets');
        $dirs = $importer->discover($root);

        // The deploy removes the previous year right after this step, so finding nothing must stop it.
        if ($dirs === []) {
            $this->error("هیچ دفترچه‌ای در {$root} پیدا نشد.");

            return self::FAILURE;
        }

        $pending = [];
        foreach ($dirs as $dir) {
            $manifest = $importer->manifest($dir);

            if (! $this->option('again') && $importer->isImported($manifest)) {
                $this->line($importer->bookletId($manifest).': این نسخه قبلاً وارد شده است.');
                continue;
            }

            $pending[] = [$dir, $importer->bookletId($manifest)];
        }

        if ($pending === []) {
            return self::SUCCESS;
        }

        $backup = new BufferedOutput;
        if (Artisan::call('education:backup-study-programs', [], $backup) !== self::SUCCESS) {
            $this->error('بکاپ ناموفق بود؛ هیچ دفترچه‌ای وارد نشد.');
            $this->line($backup->fetch());

            return self::FAILURE;
        }
        $this->line('بکاپ پیش از ورود: '.trim($backup->fetch()));

        foreach ($pending as [$dir, $booklet]) {
            $counts = $importer->import($dir);
            $this->info("{$booklet}: {$counts['total']} رشته‌محل");
            $this->table(['inserted', 'updated', 'unchanged', 'kept_manual', 'removed'], [[
                $counts['inserted'], $counts['updated'], $counts['unchanged'], $counts['kept_manual'], $counts['removed'],
            ]]);
        }

        return self::SUCCESS;
    }
}
