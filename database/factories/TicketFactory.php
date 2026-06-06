<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement([
                'open',
                'in_progress',
                'resolved',
                'closed',
            ]),
            'priority' => fake()->randomElement([
                'low',
                'medium',
                'high',
            ]),
            'user_id' => User::factory(),
        ];
    }
}