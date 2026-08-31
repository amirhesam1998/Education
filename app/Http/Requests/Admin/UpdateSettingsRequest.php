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
            'default_prepayment_amount' => ['nullable', 'integer', 'min:0'],
            'prepayment_presets' => ['nullable', 'array'],
            'prepayment_presets.*.amount' => ['nullable', 'integer', 'min:0'],
            'prepayment_presets.*.label' => ['nullable', 'string', 'max:255'],
            'prepayment_presets.*.is_active' => ['boolean'],
            'payment_cards' => ['nullable', 'array'],
            'payment_cards.*.id' => ['nullable', 'integer', 'exists:payment_cards,id'],
            'payment_cards.*.holder_name' => ['nullable', 'string', 'max:255'],
            'payment_cards.*.card_number' => ['nullable', 'string', 'max:32'],
            'payment_cards.*.bank_name' => ['nullable', 'string', 'max:255'],
            'payment_cards.*.description' => ['nullable', 'string', 'max:1000'],
            'payment_cards.*.is_active' => ['boolean'],
            'max_receipt_image_size_kb' => ['required', 'integer', 'min:100', 'max:20480'],
            'report_card_max_upload_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'allowed_receipt_formats' => ['required', 'string', 'max:255'],
            'public_link_message_template' => ['nullable', 'string', 'max:5000'],
            'exam_types' => ['nullable', 'string', 'max:5000'],
            'majors' => ['nullable', 'string', 'max:5000'],
            'expired_message' => ['required', 'string', 'max:1000'],
            'cancelled_message' => ['required', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prepayment_presets' => $this->booleanRows('prepayment_presets'),
            'payment_cards' => $this->booleanRows('payment_cards'),
        ]);
    }

    private function booleanRows(string $key): array
    {
        return collect($this->input($key, []))
            ->map(function (array $row): array {
                $row['is_active'] = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOL);

                return $row;
            })
            ->all();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ($this->input('payment_cards', []) as $index => $row) {
                $used = filled($row['holder_name'] ?? null) || filled($row['card_number'] ?? null) || filled($row['bank_name'] ?? null);

                if (! $used) {
                    continue;
                }

                foreach (['holder_name', 'card_number', 'bank_name'] as $field) {
                    if (blank($row[$field] ?? null)) {
                        $validator->errors()->add("payment_cards.$index.$field", 'اطلاعات کارت پرداخت را کامل وارد کنید.');
                    }
                }
            }
        });
    }
}
