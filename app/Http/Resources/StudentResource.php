<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'full_name' => $this->full_name, // from the accessor on the model
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
            'program_id' => $this->program_id,
            'program' => new ProgramResource($this->whenLoaded('program')),
            'year_level' => $this->year_level,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}