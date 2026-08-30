<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFieldSelectionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_code' => ['required', 'string', 'max:100'],
            'field_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'field_code.required' => 'کد رشته الزامی است.',
            'field_name.required' => 'نام رشته الزامی است.',
            'city.required' => 'شهر الزامی است.',
        ];
    }
}
