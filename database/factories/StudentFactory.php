<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_number' => $this->faker->unique()->numerify('2026-#####'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'program_id' => Program::factory(),
            'year_level' => $this->faker->numberBetween(1, 4),
            'status' => 'active',
        ];
    }
}