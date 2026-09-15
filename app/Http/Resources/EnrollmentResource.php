<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'student_number' => $this->student->student_number,
                    'full_name' => $this->student->full_name,
                ];
            }),
            'course_offering_id' => $this->course_offering_id,
            'course_offering' => new CourseOfferingResource($this->whenLoaded('courseOffering')),
            'enrollment_date' => $this->enrollment_date?->format('Y-m-d'),
            'status' => $this->status,
            'grade' => new GradeResource($this->whenLoaded('grade')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}