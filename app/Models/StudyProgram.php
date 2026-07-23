<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'exam_year_id',
    'exam_group_id',
    'code',
    'province_id',
    'city_id',
    'institution_id',
    'institution_campus_id',
    'academic_field_id',
    'course_type_id',
    'admission_type_id',
    'original_course_type',
    'original_admission_type',
    'accepts_male',
    'accepts_female',
    'first_semester_capacity',
    'second_semester_capacity',
    'description',
    'booklet_page',
    'booklet_section',
    'city_detection_method',
    'source_file',
    'source_row',
    'source_hash',
    'identity_hash',
    'validation_status',
    'raw_data',
])]
class StudyProgram extends Model
{
    public function examYear(): BelongsTo { return $this->belongsTo(ExamYear::class); }
    public function examGroup(): BelongsTo { return $this->belongsTo(ExamGroup::class); }
    public function province(): BelongsTo { return $this->belongsTo(Province::class); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function institutionCampus(): BelongsTo { return $this->belongsTo(InstitutionCampus::class); }
    public function academicField(): BelongsTo { return $this->belongsTo(AcademicField::class); }
    public function courseType(): BelongsTo { return $this->belongsTo(CourseType::class); }
    public function admissionType(): BelongsTo { return $this->belongsTo(AdmissionType::class); }

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'booklet_page' => 'integer',
            'source_row' => 'integer',
            'accepts_male' => 'boolean',
            'accepts_female' => 'boolean',
            'first_semester_capacity' => 'integer',
            'second_semester_capacity' => 'integer',
        ];
    }
}
