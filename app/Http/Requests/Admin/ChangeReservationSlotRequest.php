<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChangeReservationSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->parsedReservationInterval());
    }

    public function rules(): array
    {
        return [
            'slot_id' => ['required', 'exists:reservation_slots,id'],
            'reservation_interval' => ['required', 'string'],
            'reserved_start_time' => ['required', 'date_format:H:i'],
            'reserved_end_time' => ['required', 'date_format:H:i', 'after:reserved_start_time'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slot_id' => 'تایم جدید',
            'reservation_interval' => 'زمان رزرو',
            'reserved_start_time' => 'از ساعت',
            'reserved_end_time' => 'تا ساعت',
        ];
    }

    /**
     * @return array{reserved_start_time?:string, reserved_end_time?:string}
     */
    private function parsedReservationInterval(): array
    {
        $interval = $this->input('reservation_interval');

        if (! is_string($interval) || ! str_contains($interval, '|')) {
            return [];
        }

        [$startTime, $endTime] = array_map('trim', explode('|', $interval, 2));

        return [
            'reserved_start_time' => substr($startTime, 0, 5),
            'reserved_end_time' => substr($endTime, 0, 5),
        ];
    }
}
