<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SelectFieldSelectionExamTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['exam_type_key' => ['required', 'string', 'max:100']];
    }

    public function attributes(): array
    {
        return ['exam_type_key' => 'نوع کنکور'];
    }
}
