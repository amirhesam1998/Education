<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'exam_group_id' => ['required', 'integer', 'exists:exam_groups,id'],
            'code' => ['required', 'string', 'max:32', 'regex:/^[0-9A-Za-z-]+$/'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'institution_campus_id' => ['nullable', 'integer', 'exists:institution_campuses,id'],
            'academic_field_id' => ['required', 'integer', 'exists:academic_fields,id'],
            'course_type_id' => ['required', 'integer', 'exists:course_types,id'],
            'admission_type_id' => ['required', 'integer', 'exists:admission_types,id'],
            'first_semester_capacity' => ['nullable', 'integer', 'min:0'],
            'second_semester_capacity' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('province_id') || ! $this->filled('city_id')) {
                return;
            }

            if (! \App\Models\City::query()->whereKey($this->integer('city_id'))->where('province_id', $this->integer('province_id'))->exists()) {
                $validator->errors()->add('city_id', 'شهر انتخاب‌شده متعلق به استان نیست.');
            }

            if ($this->filled('institution_campus_id') && ! \App\Models\InstitutionCampus::query()
                ->whereKey($this->integer('institution_campus_id'))
                ->where('institution_id', $this->integer('institution_id'))
                ->exists()) {
                $validator->errors()->add('institution_campus_id', 'پردیس انتخاب‌شده متعلق به دانشگاه نیست.');
            }
        }];
    }
}
