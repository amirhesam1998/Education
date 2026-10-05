<?php

namespace App\Services\StudyPrograms;

use App\Models\City;
use App\Models\StudyProgram;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudyProgramQuery
{
    // TODO: add a dormitory predicate only when the import source provides a normalized dormitory field.
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 30), 1), 100);

        return $this->base($filters)
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function base(array $filters = []): Builder
    {
        $isActive = array_key_exists('is_active', $filters) ? $filters['is_active'] : true;

        return StudyProgram::query()
            ->with(['examYear', 'examGroup', 'province', 'nativeProvince', 'city', 'institution', 'institutionCampus', 'academicField', 'courseType', 'admissionType'])
            ->where('validation_status', $filters['validation_status'] ?? 'validated')
            ->when($isActive !== null && $isActive !== '', fn (Builder $query) => $query->where('is_active', in_array($isActive, [false, 0, '0'], true) ? false : true))
            ->when($filters['exam_year_id'] ?? null, fn (Builder $query, $id) => $query->where('exam_year_id', $id))
            ->when($filters['exam_group_id'] ?? null, fn (Builder $query, $id) => $query->where('exam_group_id', $id))
            ->when($filters['year'] ?? null, fn (Builder $query, $year) => $query->whereHas('examYear', fn ($q) => $q->where('year', $year)))
            ->when($filters['group'] ?? null, fn (Builder $query, $group) => $query->whereHas('examGroup', fn ($q) => $q->where('slug', $group)))
            ->when($filters['code'] ?? null, fn (Builder $query, $code) => $query->where('code', (string) $code))
            ->when($filters['province_id'] ?? null, fn (Builder $query, $id) => $this->inProvinces($query, [$id]))
            ->when(array_filter((array) ($filters['province_ids'] ?? [])), fn (Builder $query, $ids) => $this->inProvinces($query, $ids))
            ->when($filters['city_id'] ?? null, fn (Builder $query, $id) => $query->where('city_id', $id))
            ->when($filters['institution_id'] ?? null, fn (Builder $query, $id) => $query->where('institution_id', $id))
            ->when($filters['institution_campus_id'] ?? null, fn (Builder $query, $id) => $query->where('institution_campus_id', $id))
            ->when($filters['academic_field_id'] ?? null, fn (Builder $query, $id) => $query->where('academic_field_id', $id))
            ->when($filters['course_type_id'] ?? null, fn (Builder $query, $id) => $query->where('course_type_id', $id))
            ->when($filters['admission_type_id'] ?? null, fn (Builder $query, $id) => $query->where('admission_type_id', $id))
            ->when($filters['course_type'] ?? null, fn (Builder $query, $slug) => $query->whereHas('courseType', fn ($q) => $q->where('slug', $slug)))
            ->when($filters['admission_type'] ?? null, fn (Builder $query, $slug) => $query->whereHas('admissionType', fn ($q) => $q->where('slug', $slug)))
            ->when(($filters['accepts_male'] ?? null) !== null, fn (Builder $query) => $query->where('accepts_male', (bool) $filters['accepts_male']))
            ->when(($filters['accepts_female'] ?? null) !== null, fn (Builder $query) => $query->where('accepts_female', (bool) $filters['accepts_female']))
            ->when($filters['booklet_page'] ?? null, fn (Builder $query, $page) => $query->where('booklet_page', (int) $page))
            ->when($filters['booklet'] ?? null, fn (Builder $query, $booklet) => $query->where('source_file', 'like', '%'.$booklet.'%'))
            ->when($filters['semester'] ?? null, fn (Builder $query, $semester) => $query->where($semester === 'first' ? 'first_semester_capacity' : 'second_semester_capacity', '>', 0))
            ->when($filters['academic_record_type'] ?? null, function (Builder $query, string $type): void {
                $type === 'with_records'
                    ? $query->whereHas('admissionType', fn (Builder $admission) => $admission->where('slug', 'academic_records'))
                    : $query->whereDoesntHave('admissionType', fn (Builder $admission) => $admission->where('slug', 'academic_records'));
            })
            ->when($filters['gender'] ?? null, fn (Builder $query, $gender) => $query->where($gender === 'male' ? 'accepts_male' : 'accepts_female', true))
            ->when($filters['description'] ?? null, fn (Builder $query, $search) => $query->where('description', 'like', '%'.$search.'%'))
            ->when($filters['search'] ?? null, function (Builder $query, $search): void {
                $this->applySearch($query, (string) $search);
            });
    }

    /**
     * A program belongs to the province it is studied in and, for service-commitment and native-quota
     * programs, to the province whose natives it is for (38873 is studied in Tabriz for Kurdistan natives).
     */
    public function inProvinces(Builder $query, array $ids): Builder
    {
        return $query->where(fn (Builder $nested) => $nested
            ->whereIn('province_id', $ids)
            ->orWhereIn('native_province_id', $ids));
    }

    /**
     * The cities a province filter can be narrowed to: the province's own cities, then the cities where
     * the programs kept for its natives are studied, named with their province ("تبریز (آذربایجان شرقی)").
     *
     * @param  Builder  $programs  study programs already limited to the province with inProvinces()
     * @return Collection<int, array{id:int, name:string}>
     */
    public function provinceCities(int $provinceId, Builder $programs): Collection
    {
        return City::query()
            ->with('province:id,name')
            ->whereIn('id', $programs->select('city_id')->whereNotNull('city_id'))
            ->orderByRaw('case when province_id = ? then 0 else 1 end', [$provinceId])
            ->orderBy('normalized_name')
            ->get(['id', 'name', 'province_id'])
            ->map(fn (City $city): array => [
                'id' => $city->id,
                'name' => (int) $city->province_id === $provinceId ? $city->name : $city->name.' ('.$city->province?->name.')',
            ])
            ->values();
    }

    public function applySearch(Builder $query, string $search): Builder
    {
        $search = $this->normalizedSearch($search);

        if ($search === '') {
            return $query;
        }

        if (preg_match('/^\d+$/', $search)) {
            return $query
                ->where('code', 'like', '%'.$search.'%')
                ->orderByRaw('case when code = ? then 0 when code like ? then 1 else 2 end', [$search, $search.'%']);
        }

        $tokens = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $query->whereHas('academicField', function (Builder $field) use ($tokens): void {
            foreach ($tokens as $token) {
                $field->where('normalized_name', 'like', '%'.$token.'%');
            }
        });

        return $query->orderByRaw(
            'case when (select normalized_name from academic_fields where academic_fields.id = study_programs.academic_field_id) = ? then 0 when (select normalized_name from academic_fields where academic_fields.id = study_programs.academic_field_id) like ? then 1 else 2 end',
            [$search, $search.'%'],
        );
    }

    public function cityBelongsToProvince(?int $cityId, ?int $provinceId): bool
    {
        if (! $cityId || ! $provinceId) {
            return true;
        }

        return City::query()->whereKey($cityId)->where('province_id', $provinceId)->exists()
            || StudyProgram::query()->where('city_id', $cityId)->where('native_province_id', $provinceId)->exists();
    }

    private function normalizedSearch(string $value): string
    {
        $value = strtr($value, [
            'ي' => 'ی', 'ك' => 'ک', '‌' => ' ',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }
}
