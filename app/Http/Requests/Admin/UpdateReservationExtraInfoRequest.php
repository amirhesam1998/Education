<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationExtraInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = strtr((string) $this->input('phone'), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->merge(['phone' => filled($phone) ? $phone : null]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'rank' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', Rule::in(Student::regionOptions())],
            'national_rank' => ['nullable', 'string', 'max:50'],
            'total_score' => ['nullable', 'string', 'max:50'],
            'konkur_score' => ['nullable', 'string', 'max:50'],
            'final_score' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'regex:/^09\d{9}$/'],
            'accepted_national_field' => ['nullable', 'string', 'max:255'],
            'accepted_azad_other_field' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'نام و نام خانوادگی', 'major' => 'رشته', 'rank' => 'رتبه',
            'region' => 'منطقه', 'national_rank' => 'رتبه کشوری', 'total_score' => 'نمره کل',
            'konkur_score' => 'تراز کنکور', 'final_score' => 'تراز نهایی', 'phone' => 'تلفن',
            'accepted_national_field' => 'رشته قبولی سراسری',
            'accepted_azad_other_field' => 'رشته قبولی آزاد / غیرانتفاعی / پیام نور',
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'تلفن باید یک شماره همراه معتبر ایرانی باشد.'];
    }
}
