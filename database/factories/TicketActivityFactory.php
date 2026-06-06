<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement([
                'ticket_created',
                'ticket_updated',
                'status_changed',
                'ticket_assigned',
                'ticket_unassigned',
                'comment_created',
            ]),
            'description' => fake()->sentence(),
            'old_value' => null,
            'new_value' => null,
            'metadata' => null,
        ];
    }
}
