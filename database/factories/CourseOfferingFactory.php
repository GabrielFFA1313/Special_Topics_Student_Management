<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseOfferingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'section' => $this->faker->randomLetter(),
            'capacity' => 30,
            'status' => 'open',
        ];
    }
}