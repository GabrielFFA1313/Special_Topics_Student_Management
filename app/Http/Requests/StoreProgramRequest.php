<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level middleware handles auth; policy will handle role checks later
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:programs,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}