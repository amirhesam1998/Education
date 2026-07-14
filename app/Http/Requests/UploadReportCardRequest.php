<?php

namespace App\Http\Requests;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UploadReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_card' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:'.app(SettingsService::class)->reportCardMaxKilobytes(),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'report_card' => 'کارنامه دانش‌آموز',
        ];
    }
}
