<?php

namespace App\Blog\Seeders;

use App\Blog\Enums\PostCategory;
use App\Blog\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        Post::factory()->published()->create([
            'category' => PostCategory::PRODUCT_UPDATES,
            'title' => 'Welcome to Ghostable',
            'description' => 'An introduction to managing environment configuration with Ghostable.',
            'slug' => 'welcome-to-ghostable',
            'content' => 'Ghostable helps your team keep environment variables in sync and secure.',
            'meta_title' => 'Welcome to Ghostable',
            'meta_description' => 'Learn what Ghostable is and how it simplifies environment management.',
            'meta_keywords' => ['ghostable', 'environment management'],
        ]);

        Post::factory()->published()->create([
            'category' => PostCategory::BEST_PRACTICES,
            'title' => 'Sync Your .env with Confidence',
            'description' => 'Best practices for keeping environment files consistent across teams.',
            'slug' => 'sync-your-env-with-confidence',
            'content' => 'Discover workflows that make sharing and validating .env files effortless.',
            'meta_title' => 'Sync Your .env with Confidence',
            'meta_description' => 'Tips for sharing environment variables safely using Ghostable.',
            'meta_keywords' => ['env', 'best practices'],
        ]);
    }
}
