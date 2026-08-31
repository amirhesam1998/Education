<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => blank($this->input('email')) ? null : trim((string) $this->input('email')),
            'phone' => trim((string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'phone' => 'شماره تماس',
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
            'status' => 'وضعیت',
            'roles' => 'نقش‌ها',
        ];
    }
}
