<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_code' => ['required', 'string', 'max:20', 'unique:courses,course_code'],
            'course_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'units' => ['required', 'integer', 'min:1', 'max:10'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}