<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\CourseOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_offering_id' => CourseOffering::factory(),
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ];
    }
}