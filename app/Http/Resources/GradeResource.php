<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'midterm_grade' => $this->midterm_grade,
            'final_grade' => $this->final_grade,
            'remarks' => $this->remarks,
            'enrollment' => $this->whenLoaded('enrollment', function () {
                return [
                    'id' => $this->enrollment->id,
                    'student_id' => $this->enrollment->student_id,
                    'course_offering_id' => $this->enrollment->course_offering_id,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}