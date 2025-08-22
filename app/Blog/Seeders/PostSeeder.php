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

        Post::factory()->published()->create([
            'category' => PostCategory::SECURITY,
            'title' => 'Keeping Secrets out of Git',
            'description' => 'Strategies for managing sensitive values without committing them to source control.',
            'slug' => 'keeping-secrets-out-of-git',
            'content' => 'Explore techniques that help your team share secrets securely using Ghostable.',
            'meta_title' => 'Keeping Secrets out of Git',
            'meta_description' => 'Learn how to protect secrets in your projects with Ghostable.',
            'meta_keywords' => ['security', 'secrets', 'git'],
        ]);

        Post::factory()->published()->create([
            'category' => PostCategory::CASE_STUDIES,
            'title' => 'Case Study: Scaling Config at Acme Corp',
            'description' => 'How a growing startup keeps environments in sync with Ghostable.',
            'slug' => 'case-study-scaling-config-at-acme-corp',
            'content' => 'A behind-the-scenes look at how Acme Corp adopted Ghostable to manage hundreds of variables.',
            'meta_title' => 'Scaling Config at Acme Corp',
            'meta_description' => 'See how Acme Corp uses Ghostable to manage config at scale.',
            'meta_keywords' => ['case study', 'scaling', 'config'],
        ]);

        Post::factory()->published()->create([
            'category' => PostCategory::RELEASE_NOTES,
            'title' => 'Ghostable 1.0 Release Notes',
            'description' => 'Highlights from the first major release of Ghostable.',
            'slug' => 'ghostable-1-0-release-notes',
            'content' => 'Get the details on new features and improvements in Ghostable 1.0.',
            'meta_title' => 'Ghostable 1.0 Release Notes',
            'meta_description' => "Discover what's new in the Ghostable 1.0 release.",
            'meta_keywords' => ['release notes', 'ghostable'],
        ]);
    }
}
