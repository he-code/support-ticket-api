<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessHourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'day_of_week' => fake()->unique()->numberBetween(1, 5),
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_open' => true,
        ];
    }
}
