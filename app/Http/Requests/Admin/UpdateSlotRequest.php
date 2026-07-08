<?php

namespace App\Http\Requests\Admin;

use App\Enums\SlotStatus;
use App\Support\PersianDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date' => PersianDate::toGregorianDate($this->input('date')),
        ]);
    }

    public function rules(): array
    {
        return [
            'advisor_id' => ['required', 'exists:advisors,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['required', Rule::in(array_keys(SlotStatus::options()))],
        ];
    }

    public function attributes(): array
    {
        return [
            'advisor_id' => 'مشاور',
            'date' => 'تاریخ',
            'start_time' => 'زمان شروع',
            'end_time' => 'زمان پایان',
            'capacity' => 'ظرفیت',
            'status' => 'وضعیت',
        ];
    }
}
