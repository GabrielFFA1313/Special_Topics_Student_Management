<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'administrator',
            'status' => 'active',
        ];
    }

    public function instructor(): static
    {
        return $this->state(fn () => ['role' => 'instructor']);
    }

    public function student(): static
    {
        return $this->state(fn () => ['role' => 'student']);
    }
}