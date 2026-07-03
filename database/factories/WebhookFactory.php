<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by_id' => User::factory()->admin(),
            'name' => fake()->unique()->company(),
            'url' => 'https://example.com/webhook',
            'secret' => 'secret',
            'events' => ['ticket.created'],
            'is_active' => true,
        ];
    }
}
