<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateFieldSelectionItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:150'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.field_code' => ['required', 'string', 'max:100'],
            'items.*.field_name' => ['required', 'string', 'max:255'],
            'items.*.field_description' => ['nullable', 'string', 'max:5000'],
            'items.*.city' => ['required', 'string', 'max:255'],
            'items.*.university_name' => ['nullable', 'string', 'max:255'],
            'items.*.university_type' => ['nullable', 'string', 'max:255'],
            'items.*.university_description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items.*.field_code' => 'کد رشته',
            'items.*.field_name' => 'نام رشته',
            'items.*.field_description' => 'توضیحات رشته',
            'items.*.city' => 'شهر',
            'items.*.university_name' => 'نام دانشگاه',
            'items.*.university_type' => 'نوع دانشگاه',
            'items.*.university_description' => 'توضیحات دانشگاه',
        ];
    }

    public function messages(): array
    {
        return [
            'items.max' => 'حداکثر تعداد انتخاب‌ها ۱۵۰ مورد است.',
            'items.*.field_code.required' => 'کد رشته الزامی است.',
            'items.*.field_name.required' => 'نام رشته الزامی است.',
            'items.*.city.required' => 'شهر الزامی است.',
        ];
    }
}
