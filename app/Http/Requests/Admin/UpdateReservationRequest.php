<?php

namespace App\Http\Requests\Admin;

use App\Support\PersianDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prepayment_required' => $this->boolean('prepayment_required'),
            'payment_deadline_at' => PersianDate::toGregorianDateTime($this->input('payment_deadline_at')),
            ...$this->parsedReservationInterval(),
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'score' => ['nullable', 'string', 'max:255'],
            'exam_type' => ['nullable', 'string', 'max:255'],
            'phone_one' => ['required', 'string', 'max:30'],
            'phone_two' => ['nullable', 'string', 'max:30'],
            'reservation_interval' => ['required', 'string'],
            'reserved_start_time' => ['required', 'date_format:H:i'],
            'reserved_end_time' => ['required', 'date_format:H:i', 'after:reserved_start_time'],
            'prepayment_required' => ['boolean'],
            'prepayment_amount' => [Rule::requiredIf($this->boolean('prepayment_required')), 'nullable', 'integer', 'min:1000'],
            'payment_deadline_at' => [Rule::requiredIf($this->boolean('prepayment_required')), 'nullable', 'date'],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return (new StoreReservationRequest())->attributes();
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
