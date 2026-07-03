<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'assigned_to_id' => null,
            'category_id' => null,
            'channel_id' => null,
            'team_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement([
                'open',
                'in_progress',
                'waiting_customer',
                'waiting_internal',
                'resolved',
                'closed',
                'reopened',
            ]),
            'priority' => fake()->randomElement([
                'low',
                'medium',
                'high',
                'urgent',
            ]),

        ];
    }
}
