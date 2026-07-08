<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChangeReservationSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slot_id' => ['required', 'exists:reservation_slots,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slot_id' => 'تایم جدید',
        ];
    }
}
