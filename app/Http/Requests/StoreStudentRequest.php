<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'max:20', 'unique:students,student_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'status' => ['nullable', 'in:active,inactive,graduated,dropped'],
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.exists' => 'The selected program does not exist.',
        ];
    }
}