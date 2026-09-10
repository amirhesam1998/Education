<?php

namespace App\Services\StudyPrograms;

use App\Models\StudyProgram;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StudyProgramQuery
{
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
        return StudyProgram::query()
            ->with(['examYear', 'examGroup', 'province', 'city', 'institution', 'institutionCampus', 'academicField', 'courseType', 'admissionType'])
            ->where('validation_status', $filters['validation_status'] ?? 'validated')
            ->when($filters['exam_year_id'] ?? null, fn (Builder $query, $id) => $query->where('exam_year_id', $id))
            ->when($filters['exam_group_id'] ?? null, fn (Builder $query, $id) => $query->where('exam_group_id', $id))
            ->when($filters['year'] ?? null, fn (Builder $query, $year) => $query->whereHas('examYear', fn ($q) => $q->where('year', $year)))
            ->when($filters['group'] ?? null, fn (Builder $query, $group) => $query->whereHas('examGroup', fn ($q) => $q->where('slug', $group)))
            ->when($filters['code'] ?? null, fn (Builder $query, $code) => $query->where('code', (string) $code))
            ->when($filters['province_id'] ?? null, fn (Builder $query, $id) => $query->where('province_id', $id))
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
            ->when($filters['description'] ?? null, fn (Builder $query, $search) => $query->where('description', 'like', '%'.$search.'%'))
            ->when($filters['search'] ?? null, function (Builder $query, $search): void {
                $this->applySearch($query, (string) $search);
            });
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

        return \App\Models\City::query()->whereKey($cityId)->where('province_id', $provinceId)->exists();
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
