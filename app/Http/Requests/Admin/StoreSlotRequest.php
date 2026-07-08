<?php

namespace App\Http\Requests\Admin;

use App\Enums\SlotStatus;
use App\Support\PersianDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => $this->input('mode', 'single'),
            'date' => PersianDate::toGregorianDate($this->input('date')),
            'repeat_start_date' => PersianDate::toGregorianDate($this->input('repeat_start_date')),
            'repeat_end_date' => PersianDate::toGregorianDate($this->input('repeat_end_date')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['single', 'repeat'])],
            'advisor_id' => ['required', 'exists:advisors,id'],
            'date' => ['required_if:mode,single', 'nullable', 'date'],
            'start_time' => ['required_if:mode,single', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:mode,single', 'nullable', 'date_format:H:i', 'after:start_time'],
            'repeat_start_date' => ['required_if:mode,repeat', 'nullable', 'date'],
            'repeat_end_date' => ['required_if:mode,repeat', 'nullable', 'date', 'after_or_equal:repeat_start_date'],
            'daily_start_time' => ['required_if:mode,repeat', 'nullable', 'date_format:H:i'],
            'daily_end_time' => ['required_if:mode,repeat', 'nullable', 'date_format:H:i', 'after:daily_start_time'],
            'interval_minutes' => ['required_if:mode,repeat', 'nullable', 'integer', 'min:15', 'max:240'],
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
            'repeat_start_date' => 'تاریخ شروع تکرار',
            'repeat_end_date' => 'تاریخ پایان تکرار',
            'daily_start_time' => 'شروع روزانه',
            'daily_end_time' => 'پایان روزانه',
            'interval_minutes' => 'فاصله زمانی',
            'capacity' => 'ظرفیت',
            'status' => 'وضعیت',
        ];
    }
}
