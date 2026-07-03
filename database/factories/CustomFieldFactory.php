<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomFieldFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => null,
            'name' => $name,
            'key' => Str::slug($name, '_'),
            'type' => 'text',
            'options' => null,
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
