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
            ->when($filters['search'] ?? null, fn (Builder $query, $search) => $query->where(function (Builder $q) use ($search): void {
                $q->where('description', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhereHas('institution', fn ($institution) => $institution->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('academicField', fn ($field) => $field->where('name', 'like', '%'.$search.'%'));
            }));
    }

    public function cityBelongsToProvince(?int $cityId, ?int $provinceId): bool
    {
        if (! $cityId || ! $provinceId) {
            return true;
        }

        return \App\Models\City::query()->whereKey($cityId)->where('province_id', $provinceId)->exists();
    }
}
