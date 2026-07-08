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
}
