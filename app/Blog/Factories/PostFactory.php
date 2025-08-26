<?php

namespace App\Blog\Factories;

use App\Blog\Enums\PostCategory;
use App\Blog\Enums\PostStatus;
use App\Blog\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->realText(50);

        return [
            'title' => $title,
            'slug' => str($title)->slug(),
            'posted_at' => now(),
            'status' => PostStatus::DRAFT,
            'category' => PostCategory::PRODUCT_UPDATES,
            'is_featured' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::PUBLISHED,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::ARCHIVED,
        ]);
    }
}
