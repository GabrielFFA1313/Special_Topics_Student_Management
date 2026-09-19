<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicTermFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+2 months');

        return [
            'academic_year' => $this->faker->unique()->numberBetween(2025, 2035) . '-' . $this->faker->numberBetween(2026, 2036),
            'semester' => $this->faker->randomElement(['1st Semester', '2nd Semester']),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+4 months'),
            'status' => 'upcoming',
        ];
    }
}