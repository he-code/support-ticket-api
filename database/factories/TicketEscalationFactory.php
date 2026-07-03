<?php

namespace Database\Factories;

use App\Models\SupportTeam;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketEscalationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'escalated_by_id' => User::factory()->supportAgent(),
            'escalated_to_id' => User::factory()->supportAgent(),
            'team_id' => SupportTeam::factory(),
            'from_priority' => 'medium',
            'to_priority' => 'high',
            'status' => 'open',
            'reason' => fake()->sentence(),
        ];
    }
}
