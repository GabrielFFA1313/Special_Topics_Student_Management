<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'midterm_grade' => ['nullable', 'numeric', 'min:1.0', 'max:5.0'],
            'final_grade' => ['nullable', 'numeric', 'min:1.0', 'max:5.0'],
            'remarks' => ['nullable', 'in:passed,failed,incomplete'],
        ];
    }
}