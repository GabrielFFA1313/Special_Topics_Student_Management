<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseOfferingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'academic_term_id' => $this->academic_term_id,
            'academic_term' => new AcademicTermResource($this->whenLoaded('academicTerm')),
            'instructor_id' => $this->instructor_id,
            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id' => $this->instructor->id,
                    'name' => $this->instructor->name,
                    'email' => $this->instructor->email,
                ];
            }),
            'section' => $this->section,
            'schedule' => $this->schedule,
            'room' => $this->room,
            'capacity' => $this->capacity,
            'enrolled_count' => $this->whenCounted('enrollments'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}