<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'max:2000']];
    }

    public function attributes(): array
    {
        return ['rejection_reason' => 'دلیل رد'];
    }
}
