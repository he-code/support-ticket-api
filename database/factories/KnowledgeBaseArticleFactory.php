<?php

namespace Database\Factories;

use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KnowledgeBaseArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'category_id' => TicketCategory::factory(),
            'created_by_id' => User::factory()->supportAgent(),
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'is_published' => true,
        ];
    }
}
