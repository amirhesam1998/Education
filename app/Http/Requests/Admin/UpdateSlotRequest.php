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
            'advisor_id' => ['required', 'integer', $this->consultantAdvisorRule()],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
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

            if (! $this->isValidRange((string) $this->input('start_time'), (string) $this->input('end_time'))) {
                $validator->errors()->add('end_time', 'زمان پایان باید بعد از زمان شروع باشد؛ ساعت ۰۰:۰۰ پایان روز محسوب می‌شود.');

                return;
            }

            $this->validateDurationFits($validator);

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->hasSlotOverlap(
                (int) $this->input('advisor_id'),
                (string) $this->input('date'),
                (string) $this->input('start_time'),
                (string) $this->input('end_time'),
                $this->route('slot')?->id,
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
            'duration_minutes' => 'مدت هر رزرو',
            'capacity' => 'ظرفیت',
            'status' => 'وضعیت',
        ];
    }

    private function consultantAdvisorRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $slot = $this->route('slot');
            $include = $slot instanceof ReservationSlot ? $slot->advisor : null;

            if (! Advisor::query()->selectableConsultants($include)->whereKey($value)->exists()) {
                $fail('مشاور انتخاب شده معتبر نیست.');
            }
        };
    }

    private function validateDurationFits(Validator $validator): void
    {
        if ($this->timeToMinutes((string) $this->input('end_time'), true) - $this->timeToMinutes((string) $this->input('start_time')) < (int) $this->input('duration_minutes')) {
            $validator->errors()->add('duration_minutes', 'مدت هر رزرو نباید از طول بازه تایم بیشتر باشد.');
        }
    }

    private function hasSlotOverlap(int $advisorId, string $date, string $startTime, string $endTime, ?int $ignoreSlotId): bool
    {
        return ReservationSlot::query()
            ->where('advisor_id', $advisorId)
            ->whereDate('date', $date)
            ->when($endTime !== '00:00', fn ($query) => $query->where('start_time', '<', $endTime))
            ->where(fn ($query) => $query->where('end_time', '00:00')->orWhere('end_time', '>', $startTime))
            ->when($ignoreSlotId, fn ($query) => $query->where('id', '!=', $ignoreSlotId))
            ->exists();
    }

    private function isValidRange(string $startTime, string $endTime): bool
    {
        return $this->timeToMinutes($startTime) < $this->timeToMinutes($endTime, true);
    }

    private function timeToMinutes(string $time, bool $endBoundary = false): int
    {
        if ($endBoundary && $time === '00:00') {
            return 1440;
        }

        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }
}
