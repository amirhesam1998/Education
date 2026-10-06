<?php

namespace App\Console\Commands;

use App\Services\Azad\AzadProgramImporter;
use Illuminate\Console\Command;

class ImportAzadPrograms extends Command
{
    protected $signature = 'education:import-azad-programs
        {--path= : پوشه رشته‌محل‌های آزاد (پیش‌فرض database/seeders/data/azad)}
        {--again : ورود دوباره حتی اگر همین نسخه قبلاً وارد شده باشد}';

    protected $description = 'Load the committed Islamic Azad University programmes (manifest.json + programs.jsonl.gz) that changed since their last import, after backing up the year.';

    public function handle(AzadProgramImporter $importer): int
    {
        $root = $this->option('path') ?: database_path('seeders/data/azad');
        $dirs = $importer->discover($root);

        if ($dirs === []) {
            $this->error("هیچ فایل رشته‌محل آزادی در {$root} پیدا نشد.");

            return self::FAILURE;
        }

        foreach ($dirs as $dir) {
            $manifest = $importer->manifest($dir);
            $year = (int) $manifest['year'];

            if (! $this->option('again') && $importer->isImported($manifest)) {
                $this->line("آزاد {$year}: این نسخه قبلاً وارد شده است.");
                continue;
            }

            $backup = $importer->backup($year);
            if ($backup !== null) {
                $this->line("بکاپ پیش از ورود: {$backup}");
            }

            $counts = $importer->import($dir);
            $this->info("آزاد {$year}: {$counts['total']} رشته‌محل");
            $this->table(['inserted', 'updated', 'unchanged', 'kept_manual', 'removed'], [[
                $counts['inserted'], $counts['updated'], $counts['unchanged'], $counts['kept_manual'], $counts['removed'],
            ]]);
        }

        return self::SUCCESS;
    }
}
