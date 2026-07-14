<?php

namespace App\Http\Requests\PublicAccess;

use App\Models\Reservation;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (is_array($this->input('exam_type'))) {
            $this->merge(['exam_type' => array_values(array_filter($this->input('exam_type')))]);
        }

        $settings = app(SettingsService::class);
        $majors = $settings->get('majors', []);
        $examTypes = $settings->get('exam_types', []);
        $reservation = Reservation::query()
            ->with('student.phones')
            ->where('public_token', $this->route('token'))
            ->first();

        $missing = $reservation?->missingStudentFields() ?? [];

        return [
            'full_name' => [in_array('full_name', $missing, true) ? 'required' : 'nullable', 'string', 'max:255'],
            'major' => array_filter([in_array('major', $missing, true) ? 'required' : 'nullable', 'string', 'max:255', $majors ? Rule::in($majors) : null]),
            'score' => [in_array('score', $missing, true) ? 'required' : 'nullable', 'string', 'max:255'],
            'exam_type' => [in_array('exam_type', $missing, true) ? 'required' : 'nullable', 'array'],
            'exam_type.*' => array_filter(['string', 'max:255', $examTypes ? Rule::in($examTypes) : null]),
            'phone_one' => [in_array('phone_one', $missing, true) ? 'required' : 'nullable', 'string', 'max:30'],
            'phone_two' => ['nullable', 'string', 'max:30'],
            'student_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'نام و نام خانوادگی',
            'major' => 'رشته',
            'score' => 'تراز',
            'exam_type' => 'نوع کنکور',
            'phone_one' => 'شماره تماس اول',
            'phone_two' => 'شماره تماس دوم',
            'student_note' => 'توضیح دانش آموز',
        ];
    }
}
