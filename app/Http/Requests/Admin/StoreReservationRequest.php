<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use App\Support\PersianDate;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $prepaymentRequired = $this->boolean('prepayment_required');

        $this->merge([
            'prepayment_required' => $prepaymentRequired,
            'payment_deadline_at' => PersianDate::toGregorianDateTime($this->input('payment_deadline_at')),
            'exam_type' => is_array($this->input('exam_type')) ? array_values(array_filter($this->input('exam_type'))) : [],
            ...$this->parsedReservationInterval(),
        ]);
    }

    public function rules(): array
    {
        $settings = app(SettingsService::class);
        $majors = $settings->get('majors', []);
        $examTypes = $settings->get('exam_types', []);
        $prepaymentRequired = $this->boolean('prepayment_required');

        return [
            'full_name' => ['nullable', 'string', 'max:255'],
            'major' => array_filter(['nullable', 'string', 'max:255', $majors ? Rule::in($majors) : null]),
            'region' => ['nullable', 'string', Rule::in(Student::regionOptions())],
            'score' => ['nullable', 'string', 'max:255'],
            'exam_type' => ['nullable', 'array'],
            'exam_type.*' => array_filter(['string', 'max:255', $examTypes ? Rule::in($examTypes) : null]),
            'phone_one' => ['required', 'string', 'max:30'],
            'phone_two' => ['nullable', 'string', 'max:30'],
            'slot_id' => ['required', 'exists:reservation_slots,id'],
            'reservation_interval' => ['required', 'string'],
            'reserved_start_time' => ['required', 'date_format:H:i'],
            'reserved_end_time' => ['required', 'date_format:H:i', 'after:reserved_start_time'],
            'prepayment_required' => ['boolean'],
            'prepayment_amount' => [Rule::excludeIf(! $prepaymentRequired), Rule::requiredIf($prepaymentRequired), 'nullable', 'integer', 'min:'.$settings->minimumPrepaymentAmount()],
            'payment_card_id' => [Rule::excludeIf(! $prepaymentRequired), Rule::requiredIf($prepaymentRequired), 'nullable', 'exists:payment_cards,id'],
            'payment_deadline_at' => [Rule::excludeIf(! $prepaymentRequired), 'nullable', 'date', 'after:now'],
            'admin_note' => ['nullable', 'string', 'max:5000'],
            'reservation_request_id' => ['nullable', 'integer', 'exists:reservation_requests,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'نام و نام خانوادگی',
            'major' => 'رشته',
            'region' => 'منطقه',
            'score' => 'تراز',
            'exam_type' => 'نوع کنکور',
            'phone_one' => 'شماره تماس اول',
            'phone_two' => 'شماره تماس دوم',
            'slot_id' => 'تایم رزرو',
            'reservation_interval' => 'زمان رزرو',
            'reserved_start_time' => 'از ساعت',
            'reserved_end_time' => 'تا ساعت',
            'prepayment_required' => 'نیاز به پیش پرداخت',
            'prepayment_amount' => 'مبلغ پیش پرداخت',
            'payment_card_id' => 'شماره کارت پرداخت',
            'payment_deadline_at' => 'مهلت پرداخت',
            'admin_note' => 'یادداشت داخلی',
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
