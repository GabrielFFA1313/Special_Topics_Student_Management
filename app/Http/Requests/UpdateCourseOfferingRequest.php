<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $offeringId = $this->route('course_offering')->id;

        return [
            'course_id' => ['sometimes', 'required', 'integer', 'exists:courses,id'],
            'academic_term_id' => ['sometimes', 'required', 'integer', 'exists:academic_terms,id'],
            'instructor_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('role', 'instructor'),
            ],
            'section' => ['sometimes', 'required', 'string', 'max:20'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:50'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:200'],
            'status' => ['nullable', 'in:open,closed,cancelled'],
        ];
    }
}