<?php

namespace App\Services\StudyPrograms;

use App\Models\StudyProgram;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudyProgramAdminService
{
    public function __construct(
        private readonly StudyProgramQuery $query,
        private readonly PersianTextNormalizer $normalizer,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return $this->query->paginate($filters);
    }

    public function createManual(array $data, User $user): StudyProgram
    {
        return DB::transaction(function () use ($data, $user): StudyProgram {
            $payload = $this->payload($data);
            $this->assertIdentityAvailable($payload['identity_hash']);
            $program = StudyProgram::query()->create($payload);
            $this->activityLog->log('study_program_created_manually', null, $user, null, ['study_program_id' => $program->id]);

            return $program;
        });
    }

    public function updateManual(StudyProgram $studyProgram, array $data, User $user): StudyProgram
    {
        return DB::transaction(function () use ($studyProgram, $data, $user): StudyProgram {
            $payload = $this->payload($data, $studyProgram);
            $this->assertIdentityAvailable($payload['identity_hash'], $studyProgram->id);
            $old = $studyProgram->only(array_keys($payload));
            $studyProgram->update($payload);
            $this->activityLog->log('study_program_updated_manually', null, $user, $old, $studyProgram->only(array_keys($payload)) + ['study_program_id' => $studyProgram->id]);

            return $studyProgram->refresh();
        });
    }

    public function deleteOrDeactivate(StudyProgram $studyProgram, User $user): void
    {
        if (! $studyProgram->is_active) {
            return;
        }

        $studyProgram->update(['is_active' => false, 'source_type' => 'manual']);
        $this->activityLog->log('study_program_deleted_or_deactivated_manually', null, $user, ['is_active' => true], ['study_program_id' => $studyProgram->id, 'is_active' => false]);
    }

    public function canDelete(StudyProgram $studyProgram): bool
    {
        return $studyProgram->is_active;
    }

    private function payload(array $data, ?StudyProgram $existing = null): array
    {
        $relations = $this->relatedNames($data);
        $identityHash = hash('sha256', implode('|', [
            $relations['year'],
            $relations['group'],
            trim($data['code']),
            $this->normalizer->lookup($relations['institution']),
            $this->normalizer->lookup($relations['campus']),
            $this->normalizer->lookup($relations['field']),
            $relations['course'],
        ]));

        $values = [
            'code' => trim($data['code']),
            'province' => $relations['province'],
            'city' => $relations['city'],
            'institution' => $relations['institution'],
            'campus' => $relations['campus'],
            'academic_field' => $relations['field'],
            'course_type' => $relations['course'],
            'admission_type' => $relations['admission'],
            'first_capacity' => $data['first_semester_capacity'] ?? null,
            'second_capacity' => $data['second_semester_capacity'] ?? null,
            'description' => $data['description'] ?? null,
        ];

        return [
            ...collect($data)->only(['exam_year_id', 'exam_group_id', 'province_id', 'city_id', 'institution_id', 'institution_campus_id', 'academic_field_id', 'course_type_id', 'admission_type_id', 'first_semester_capacity', 'second_semester_capacity', 'description', 'is_active'])->all(),
            'code' => trim($data['code']),
            'original_course_type' => $relations['course'],
            'original_admission_type' => $relations['admission'],
            'source_file' => $existing?->source_file ?: 'manual',
            // An admin edit takes ownership of this catalogue row; snapshots must
            // not overwrite it on later production-safe imports.
            'source_type' => 'manual',
            'source_hash' => hash('sha256', json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'identity_hash' => $identityHash,
            'validation_status' => 'validated',
            'raw_data' => ['manual' => true],
        ];
    }

    private function relatedNames(array $data): array
    {
        return [
            'year' => \App\Models\ExamYear::query()->findOrFail($data['exam_year_id'])->year,
            'group' => \App\Models\ExamGroup::query()->findOrFail($data['exam_group_id'])->slug,
            'province' => \App\Models\Province::query()->findOrFail($data['province_id'])->name,
            'city' => \App\Models\City::query()->findOrFail($data['city_id'])->name,
            'institution' => \App\Models\Institution::query()->findOrFail($data['institution_id'])->name,
            'campus' => filled($data['institution_campus_id'] ?? null) ? \App\Models\InstitutionCampus::query()->findOrFail($data['institution_campus_id'])->name : '',
            'field' => \App\Models\AcademicField::query()->findOrFail($data['academic_field_id'])->name,
            'course' => \App\Models\CourseType::query()->findOrFail($data['course_type_id'])->slug,
            'admission' => \App\Models\AdmissionType::query()->findOrFail($data['admission_type_id'])->slug,
        ];
    }

    private function assertIdentityAvailable(string $identityHash, ?int $exceptId = null): void
    {
        $exists = StudyProgram::query()->where('identity_hash', $identityHash)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'رشته‌محل فعال با این مشخصات قبلاً ثبت شده است.']);
        }
    }
}
