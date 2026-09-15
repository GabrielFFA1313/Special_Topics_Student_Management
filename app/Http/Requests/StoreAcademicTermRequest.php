<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year' => [
                'required', 'string', 'max:20',
                Rule::unique('academic_terms', 'academic_year')
                    ->where(fn ($query) => $query->where('semester', $this->semester)),
            ],
            'semester' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['nullable', 'in:upcoming,active,closed'],
        ];
    }
}