<?php

namespace App\Services\StudyPrograms;

use Illuminate\Support\Collection;

class StudyProgramFileDiscovery
{
    public function __construct(private readonly StudyProgramValueMapper $mapper)
    {
    }

    public function discover(?string $path = null, ?string $group = null, ?string $file = null): Collection
    {
        $files = $file ? [$file] : glob(($path ?: base_path('Docs')).'/*.xlsx');

        return collect($files)
            ->filter(fn ($path) => is_file($path) && ! str_starts_with(basename($path), '~$'))
            ->filter(fn ($path) => str_contains(basename($path), '_clean_v3'))
            ->filter(fn ($path) => ! str_contains(basename($path), '(1)'))
            ->filter(fn ($path) => basename($path) !== 'universities_and_cities_reference_all_groups_v3.xlsx')
            ->map(fn ($path) => [
                'path' => $path,
                'filename' => basename($path),
                'group' => $this->mapper->examGroupFromFilename(basename($path)),
            ])
            ->filter(fn ($item) => $item['group'] && (! $group || $item['group'] === $group))
            ->sortBy('filename')
            ->values();
    }

    public function referenceFile(?string $path = null): ?string
    {
        $file = ($path ?: base_path('Docs')).'/universities_and_cities_reference_all_groups_v3.xlsx';

        return is_file($file) ? $file : null;
    }
}
