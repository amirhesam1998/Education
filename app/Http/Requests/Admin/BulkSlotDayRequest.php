<?php

namespace App\Http\Requests\Admin;

use App\Models\Advisor;
use App\Support\PersianDate;
use Illuminate\Foundation\Http\FormRequest;

class BulkSlotDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['date' => PersianDate::toGregorianDate($this->input('date'))]);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'advisor_id' => ['nullable', 'integer', 'exists:advisors,id'],
            'action' => ['nullable', 'in:delete,deactivate'],
        ];
    }

    public function attributes(): array
    {
        return ['date' => 'تاریخ', 'advisor_id' => 'مشاور'];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'تاریخ انتخاب‌شده را وارد کنید.',
            'date.date' => 'تاریخ انتخاب‌شده معتبر نیست.',
            'advisor_id.exists' => 'مشاور انتخاب‌شده معتبر نیست.',
        ];
    }
}
