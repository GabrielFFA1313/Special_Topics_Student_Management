<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enrollment_id' => [
                'required', 'integer', 'exists:enrollments,id',
                Rule::unique('grades', 'enrollment_id'), // one grade per enrollment
            ],
            'midterm_grade' => ['nullable', 'numeric', 'min:1.0', 'max:5.0'],
            'final_grade' => ['nullable', 'numeric', 'min:1.0', 'max:5.0'],
            'remarks' => ['nullable', 'in:passed,failed,incomplete'],
        ];
    }

    public function messages(): array
    {
        return [
            'enrollment_id.exists' => 'The selected enrollment does not exist.',
            'enrollment_id.unique' => 'This enrollment already has a grade recorded. Use update instead.',
        ];
    }
}