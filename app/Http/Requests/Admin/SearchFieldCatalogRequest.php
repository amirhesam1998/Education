<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SearchFieldCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'course_type_id' => ['nullable', 'integer', 'exists:course_types,id'],
            'province_ids' => ['nullable', 'array'],
            'province_ids.*' => ['integer', 'exists:provinces,id'],
            'booklet' => ['nullable', 'string', 'max:255'],
            'semester' => ['nullable', 'in:first,second'],
            'academic_record_type' => ['nullable', 'in:with_records,without_records'],
            'gender' => ['nullable', 'in:male,female'],
        ];
    }
}
