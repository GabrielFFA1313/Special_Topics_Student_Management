<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'instructor_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('role', 'instructor'),
            ],
            'section' => [
                'required', 'string', 'max:20',
                Rule::unique('course_offerings')->where(function ($query) {
                    return $query->where('course_id', $this->course_id)
                                 ->where('academic_term_id', $this->academic_term_id);
                }),
            ],
            'schedule' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'status' => ['nullable', 'in:open,closed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'instructor_id.exists' => 'The selected user is not a valid instructor.',
            'section.unique' => 'This section already exists for the selected course and term.',
        ];
    }
}