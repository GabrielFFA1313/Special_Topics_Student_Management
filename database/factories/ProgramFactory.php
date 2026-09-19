<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('???')) . $this->faker->numberBetween(100, 999),
            'name' => 'BS ' . $this->faker->words(2, true),
            'status' => 'active',
        ];
    }
}