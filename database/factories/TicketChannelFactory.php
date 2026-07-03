<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketChannelFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'key' => Str::slug($name, '_'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
