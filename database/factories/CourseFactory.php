<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_code' => strtoupper($this->faker->unique()->lexify('???')) . $this->faker->numberBetween(100, 499),
            'course_title' => $this->faker->words(3, true),
            'units' => $this->faker->numberBetween(1, 5),
            'status' => 'active',
        ];
    }
}