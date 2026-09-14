<?php

namespace App\Http\Requests\PublicAccess;

use App\Models\ReservationRequest;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = fn (?string $value): string => strtr((string) $value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);

        $this->merge([
            'phone_1' => $phone($this->input('student_phone')),
            'phone_2' => filled($this->input('parent_phone')) ? $phone($this->input('parent_phone')) : null,
            'exam_type' => array_values(array_filter((array) $this->input('exam_type', []))),
            'region_quota' => $this->regionQuotaKey(),
        ]);
    }

    public function rules(): array
    {
        $settings = app(SettingsService::class);
        $examTypes = $settings->get('exam_types', []);

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_1' => ['required', 'regex:/^09\d{9}$/'],
            'phone_2' => ['nullable', 'regex:/^09\d{9}$/', 'different:phone_1'],
            'major' => ['nullable', Rule::in(['تجربی', 'انسانی', 'ریاضی', 'هنر', 'زبان'])],
            'exam_type' => ['required', 'array', 'min:1'],
            'exam_type.*' => array_filter(['required', 'string', 'max:255', $examTypes ? Rule::in($examTypes) : null]),
            'region' => ['required', Rule::in(['منطقه یک', 'منطقه دو', 'منطقه سه', 'اطلاعی ندارم'])],
            'region_quota' => ['nullable', Rule::in(array_keys(ReservationRequest::regionQuotaOptions()))],
            'special_quota' => ['nullable', Rule::in(['خیر، سهمیه خاص ندارم', 'ایثارگران ۵٪', 'ایثارگران ۲۵٪', 'رزمندگان', 'خانواده شهدا', 'بهیاران', 'سایر', 'اطلاعی ندارم'])],
            'special_quota_other' => ['nullable', 'string', 'max:60', Rule::requiredIf($this->input('special_quota') === 'سایر')],
            'captcha' => ['required', 'integer', function (string $attribute, mixed $value, \Closure $fail): void {
                if ((int) $value !== (int) session('public_reservation_request_captcha')) {
                    $fail('پاسخ عبارت امنیتی صحیح نیست.');
                }
            }],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'نام و نام خانوادگی', 'phone_1' => 'شماره تماس دانش‌آموز', 'phone_2' => 'شماره تماس اولیا',
            'major' => 'رشته تحصیلی', 'exam_type' => 'نوع کنکور', 'region' => 'سهمیه منطقه',
            'special_quota' => 'سهمیه خاص', 'special_quota_other' => 'عنوان سهمیه',
            'captcha' => 'عبارت امنیتی',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_1.regex' => 'شماره تماس باید یک شماره همراه معتبر ایرانی باشد.',
            'phone_2.regex' => 'شماره تماس دوم باید یک شماره همراه معتبر ایرانی باشد.',
            'phone_2.different' => 'شماره تماس دوم نباید با شماره تماس یکسان باشد.',
            'exam_type.min' => 'حداقل یک نوع کنکور را انتخاب کنید.',
        ];
    }

    private function regionQuotaKey(): ?string
    {
        return match ($this->input('special_quota')) {
            'ایثارگران ۵٪' => 'quota_5',
            'ایثارگران ۲۵٪' => 'quota_25',
            default => match ($this->input('region')) {
                'منطقه یک' => 'region_1',
                'منطقه دو' => 'region_2',
                'منطقه سه' => 'region_3',
                default => null,
            },
        };
    }
}
