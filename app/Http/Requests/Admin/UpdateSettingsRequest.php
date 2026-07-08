<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institute_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'default_payment_deadline_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'default_public_link_expiration_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'reservation_duration_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'default_prepayment_amount' => ['nullable', 'integer', 'min:0'],
            'max_receipt_image_size_kb' => ['required', 'integer', 'min:100', 'max:20480'],
            'allowed_receipt_formats' => ['required', 'string', 'max:255'],
            'public_link_message_template' => ['nullable', 'string', 'max:5000'],
            'exam_types' => ['nullable', 'string', 'max:5000'],
            'majors' => ['nullable', 'string', 'max:5000'],
            'expired_message' => ['required', 'string', 'max:1000'],
            'cancelled_message' => ['required', 'string', 'max:1000'],
            'release_slot_after_payment_rejection' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'release_slot_after_payment_rejection' => $this->boolean('release_slot_after_payment_rejection'),
        ]);
    }
}
