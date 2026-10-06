<?php

namespace App\Services\Azad;

use App\Models\AzadProgram;
use App\Support\PersianSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Filters shared by the Azad catalogue page and the Azad selection search. */
class AzadProgramQuery
{
    public const FILTERS = [
        'q', 'year', 'booklet', 'admission', 'province', 'city', 'unit_code', 'field_code', 'group', 'exam_group',
        'education_group', 'gender', 'part_time', 'self_funded', 'semester', 'is_active',
    ];

    /** @param array<string, mixed> $filters */
    public function apply(Builder $query, array $filters): Builder
    {
        $f = fn (string $key) => is_string($filters[$key] ?? null) ? trim($filters[$key]) : ($filters[$key] ?? null);

        $query
            ->when(filled($f('year')), fn ($q) => $q->where('year', (int) $f('year')))
            ->when(filled($f('booklet')), fn ($q) => $q->where('booklet', $f('booklet')))
            ->when(filled($f('admission')), fn ($q) => $q->where('admission', $f('admission')))
            ->when(filled($f('province')), fn ($q) => $q->where('province', $f('province')))
            ->when(filled($f('city')), fn ($q) => $q->where('city', $f('city')))
            ->when(filled($f('unit_code')), fn ($q) => $q->where('unit_code', AzadProgram::asciiDigits($f('unit_code'))))
            ->when(filled($f('field_code')), fn ($q) => $q->where('field_code', AzadProgram::asciiDigits($f('field_code'))))
            ->when(filled($f('exam_group')), fn ($q) => $q->where('exam_group', $f('exam_group')))
            ->when(filled($f('education_group')), fn ($q) => $q->where('education_group', $f('education_group')))
            // گروه آزمایشی (exam booklet) or گروه آموزشی (records booklets): either one.
            ->when(filled($f('group')), fn ($q) => $q->where(fn ($q) => $q->where('exam_group', $f('group'))->orWhere('education_group', $f('group'))))
            ->when(filled($f('part_time')), fn ($q) => $q->where('part_time', (bool) (int) $f('part_time')))
            ->when(filled($f('self_funded')), fn ($q) => $q->where('self_funded', (bool) (int) $f('self_funded')))
            ->when(filled($f('is_active')), fn ($q) => $q->where('is_active', (bool) (int) $f('is_active')));

        // «زن» / «مرد»: programmes that admit that gender, mixed ones included.
        $gender = $f('gender');
        if (in_array($gender, ['زن', 'مرد'], true)) {
            $query->whereIn('gender', [$gender, 'زن و مرد']);
        } elseif (in_array($gender, ['only_mixed', 'only_female', 'only_male'], true)) {
            $query->where('gender', ['only_mixed' => 'زن و مرد', 'only_female' => 'زن', 'only_male' => 'مرد'][$gender]);
        }

        $semester = $f('semester');
        if ($semester === 'first') {
            $query->whereNotNull('capacity_first');
        } elseif ($semester === 'second') {
            $query->whereNotNull('capacity_second');
        }

        $search = AzadProgram::lookup(AzadProgram::asciiDigits((string) $f('q')));
        if ($search !== '') {
            if (preg_match('/^\d+$/', $search)) {
                $query->where(fn ($q) => $q->where('field_code', 'like', $search.'%')->orWhere('unit_code', $search));
            } else {
                foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
                    $query->where('search_text', 'like', '%'.$token.'%');
                }
            }
        }

        return $query;
    }

    /** Provinces in the catalogue, in the booklets' alphabetical order, «برون مرزی» last. */
    public function provinces(): Collection
    {
        $provinces = PersianSort::sort(AzadProgram::query()->where('is_active', true)->distinct()->pluck('province'));

        return $provinces->reject(fn (string $p) => $p === AzadProgram::ABROAD)
            ->concat($provinces->contains(AzadProgram::ABROAD) ? [AzadProgram::ABROAD] : [])
            ->values();
    }

    /** Cities of one province that hold programmes matching the other filters. */
    public function cities(string $province, array $filters = []): Collection
    {
        $query = AzadProgram::query()->where('is_active', true);
        $this->apply($query, ['province' => $province] + array_diff_key($filters, ['city' => true, 'province' => true]));

        return PersianSort::sort($query->distinct()->pluck('city'));
    }

    /**
     * Units (واحد / مرکز) matching the filters, by name, as [code, name] pairs.
     *
     * @return Collection<int, array{code: string, name: string}>
     */
    public function units(array $filters = []): Collection
    {
        $query = AzadProgram::query()->where('is_active', true);
        $this->apply($query, array_diff_key($filters, ['unit_code' => true, 'q' => true]));
        $units = $query->distinct()->get(['unit_code', 'unit_name'])
            ->unique('unit_code')
            ->map(fn (AzadProgram $p) => ['code' => $p->unit_code, 'name' => $p->unit_name]);
        $order = PersianSort::sort($units->pluck('name')->unique())->flip();

        return $units->sortBy(fn (array $u) => [$order[$u['name']], (int) $u['code']])->values();
    }

    public function years(): Collection
    {
        return AzadProgram::query()->distinct()->orderByDesc('year')->pluck('year');
    }
}
