<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->sentence(),
            'conditions' => ['event' => 'ticket_created'],
            'actions' => ['priority' => 'high'],
            'is_active' => true,
            'priority' => 100,
        ];
    }
}
