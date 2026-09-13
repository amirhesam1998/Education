<?php

namespace App\Http\Requests\PublicAccess;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UploadReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = app(SettingsService::class);

        return [
            'receipt_image' => [
                'required',
                'image',
                'mimes:'.implode(',', $settings->receiptFormats()),
                'max:'.$settings->receiptMaxKilobytes(),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'receipt_image' => 'فیش پرداخت',
        ];
    }

    public function messages(): array
    {
        return [
            'receipt_image.required' => 'فیش پرداخت را انتخاب کنید.',
            'receipt_image.image' => 'فیش پرداخت باید تصویر باشد.',
            'receipt_image.mimes' => 'فرمت فیش پرداخت باید JPG، PNG یا WEBP باشد.',
            'receipt_image.max' => 'حجم فایل فیش پرداخت از حد مجاز بیشتر است.',
        ];
    }
}
