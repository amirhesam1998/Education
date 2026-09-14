<?php

namespace App\Http\Requests\Admin;

use App\Support\PersianDate;
use Illuminate\Foundation\Http\FormRequest;

class OperatorFieldSelectionStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => PersianDate::toGregorianDate($this->input('from')),
            'to' => PersianDate::toGregorianDate($this->input('to')),
        ]);
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'operator_id' => ['nullable', 'integer', 'exists:users,id'],
            'activity_type' => ['nullable', 'in:created,edited'],
            'exam_type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
