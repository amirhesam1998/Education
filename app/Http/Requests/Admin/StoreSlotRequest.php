<?php

namespace App\Http\Requests\Admin;

use App\Enums\SlotStatus;
use App\Models\Advisor;
use App\Models\ReservationSlot;
use App\Support\PersianDate;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'advisor_id' => ['required', 'integer', $this->consultantAdvisorRule()],
            'date' => ['required_if:mode,single', 'nullable', 'date'],
            'start_time' => ['required_if:mode,single', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:mode,single', 'nullable', 'date_format:H:i', 'after:start_time'],
            'repeat_start_date' => ['required_if:mode,repeat', 'nullable', 'date'],
            'repeat_end_date' => ['required_if:mode,repeat', 'nullable', 'date', 'after_or_equal:repeat_start_date'],
            'daily_start_time' => ['required_if:mode,repeat', 'nullable', 'date_format:H:i'],
            'daily_end_time' => ['required_if:mode,repeat', 'nullable', 'date_format:H:i', 'after:daily_start_time'],
            'interval_minutes' => ['required_if:mode,repeat', 'nullable', 'integer', 'min:15', 'max:240'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['required', Rule::in(array_keys(SlotStatus::options()))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('mode') === 'repeat') {
                $this->validateRepeatedSlotConflicts($validator);

                return;
            }

            $this->validateDurationFits(
                $validator,
                (string) $this->input('start_time'),
                (string) $this->input('end_time'),
            );

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->hasSlotOverlap(
                (int) $this->input('advisor_id'),
                (string) $this->input('date'),
                (string) $this->input('start_time'),
                (string) $this->input('end_time'),
            )) {
                $validator->errors()->add('start_time', 'برای این مشاور در این بازه زمانی قبلا تایم ثبت شده است.');
            }
        });
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
            'duration_minutes' => 'مدت هر رزرو',
            'capacity' => 'ظرفیت',
            'status' => 'وضعیت',
        ];
    }

    private function consultantAdvisorRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! Advisor::query()->selectableConsultants()->whereKey($value)->exists()) {
                $fail('مشاور انتخاب شده معتبر نیست.');
            }
        };
    }

    private function validateRepeatedSlotConflicts(Validator $validator): void
    {
        $date = Carbon::parse($this->input('repeat_start_date'));
        $endDate = Carbon::parse($this->input('repeat_end_date'));

        while ($date->lte($endDate)) {
            $cursor = Carbon::parse($date->toDateString().' '.$this->input('daily_start_time'));
            $dayEnd = Carbon::parse($date->toDateString().' '.$this->input('daily_end_time'));

            while ($cursor->copy()->addMinutes((int) $this->input('interval_minutes'))->lte($dayEnd)) {
                $slotEnd = $cursor->copy()->addMinutes((int) $this->input('interval_minutes'));

                $this->validateDurationFits($validator, $cursor->format('H:i'), $slotEnd->format('H:i'));

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->hasSlotOverlap(
                    (int) $this->input('advisor_id'),
                    $date->toDateString(),
                    $cursor->format('H:i'),
                    $slotEnd->format('H:i'),
                )) {
                    $validator->errors()->add('daily_start_time', 'برای این مشاور در یکی از بازههای تکرار قبلا تایم ثبت شده است.');

                    return;
                }

                $cursor = $slotEnd;
            }

            $date->addDay();
        }
    }

    private function validateDurationFits(Validator $validator, string $startTime, string $endTime): void
    {
        $start = Carbon::parse('2000-01-01 '.$startTime);
        $end = Carbon::parse('2000-01-01 '.$endTime);

        if ($start->diffInMinutes($end) < (int) $this->input('duration_minutes')) {
            $validator->errors()->add('duration_minutes', 'مدت هر رزرو نباید از طول بازه تایم بیشتر باشد.');
        }
    }

    private function hasSlotOverlap(int $advisorId, string $date, string $startTime, string $endTime): bool
    {
        return ReservationSlot::query()
            ->where('advisor_id', $advisorId)
            ->whereDate('date', $date)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }
}
