<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuickReplyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by_id' => User::factory()->supportAgent(),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
