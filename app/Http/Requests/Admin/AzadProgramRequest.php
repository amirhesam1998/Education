<?php

namespace App\Http\Requests\Admin;

use App\Models\AzadProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AzadProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'unit_code' => AzadProgram::asciiDigits(trim((string) $this->input('unit_code'))),
            'field_code' => AzadProgram::asciiDigits(trim((string) $this->input('field_code'))),
            'part_time' => $this->boolean('part_time'),
            'self_funded' => $this->boolean('self_funded'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $program = $this->route('azadProgram');

        return [
            'year' => ['required', 'integer', 'between:1390,1500'],
            'booklet' => ['required', Rule::in(array_keys(AzadProgram::BOOKLETS))],
            'province' => ['required', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:80'],
            'unit_code' => ['required', 'regex:/^\d{1,10}$/'],
            'unit_name' => ['required', 'string', 'max:255'],
            'field_code' => [
                'required', 'regex:/^\d{1,10}$/',
                Rule::unique('azad_programs', 'field_code')
                    ->where(fn ($q) => $q->where('year', $this->input('year'))->where('booklet', $this->input('booklet'))
                        ->where('unit_code', $this->input('unit_code'))->where('part_time', $this->boolean('part_time')))
                    ->ignore($program?->id),
            ],
            'field_name' => ['required', 'string', 'max:255'],
            'part_time' => ['boolean'],
            'self_funded' => ['boolean'],
            'gender' => ['required', Rule::in(AzadProgram::GENDERS)],
            'exam_group' => ['nullable', Rule::in(array_keys(AzadProgram::GROUPS))],
            'education_group' => ['nullable', Rule::in(array_keys(AzadProgram::GROUPS))],
            'capacity_first' => ['nullable', 'integer', 'min:0', 'max:65000'],
            'capacity_second' => ['nullable', 'integer', 'min:0', 'max:65000'],
            'booklet_page' => ['nullable', 'integer', 'min:1', 'max:65000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_code.regex' => 'کد محل دانشگاهی باید فقط عدد باشد.',
            'field_code.regex' => 'کد رشته تحصیلی باید فقط عدد باشد.',
            'field_code.unique' => 'این کد محل و کد رشته در همین دفترچه قبلاً ثبت شده است.',
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => 'سال',
            'booklet' => 'دفترچه',
            'province' => 'استان',
            'city' => 'شهر',
            'unit_code' => 'کد محل دانشگاهی',
            'unit_name' => 'نام محل دانشگاهی',
            'field_code' => 'کد رشته تحصیلی',
            'field_name' => 'نام رشته تحصیلی',
            'gender' => 'جنس پذیرش',
            'exam_group' => 'گروه آزمایشی',
            'education_group' => 'گروه آموزشی',
            'capacity_first' => 'ظرفیت نیمسال اول',
            'capacity_second' => 'ظرفیت نیمسال دوم',
            'booklet_page' => 'صفحه دفترچه',
        ];
    }
}
