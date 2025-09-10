<?php

namespace App\Blog\Seeders;

use App\Blog\Enums\PostCategory;
use App\Blog\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        Post::factory()->published()->featured()->create([
            'category' => PostCategory::PRODUCT_UPDATES,
            'title' => 'Welcome to Ghostable',
            'description' => 'An introduction to managing environment configuration with Ghostable.',
            'slug' => 'welcome-to-ghostable',
            'content' => "Ghostable helps your team keep environment variables in sync and secure.\n\nFrom small side projects to large applications, Ghostable centralizes your configuration so everyone works from the same source of truth. You'll never have to wonder which .env file is the most up to date again.",
            'meta_title' => 'Welcome to Ghostable',
            'meta_description' => 'Learn what Ghostable is and how it simplifies environment management.',
            'meta_keywords' => ['ghostable', 'environment management'],
        ]);

        Post::factory()->published()->featured()->create([
            'category' => PostCategory::BEST_PRACTICES,
            'title' => 'Sync Your .env with Confidence',
            'description' => 'Best practices for keeping environment files consistent across teams.',
            'slug' => 'sync-your-env-with-confidence',
            'content' => "Discover workflows that make sharing and validating .env files effortless.\n\nWe explore strategies for reviewing changes, catching mistakes before they reach production, and automating the tedious parts of configuration management so your team can focus on shipping features.",
            'meta_title' => 'Sync Your .env with Confidence',
            'meta_description' => 'Tips for sharing environment variables safely using Ghostable.',
            'meta_keywords' => ['env', 'best practices'],
        ]);

        Post::factory()->published()->featured()->create([
            'category' => PostCategory::SECURITY,
            'title' => 'Keeping Secrets out of Git',
            'description' => 'Strategies for managing sensitive values without committing them to source control.',
            'slug' => 'keeping-secrets-out-of-git',
            'content' => "Explore techniques that help your team share secrets securely using Ghostable.\n\nWe'll cover how encrypted storage, access controls, and audit logs work together to provide a simple yet powerful layer of protection for your most critical data.",
            'meta_title' => 'Keeping Secrets out of Git',
            'meta_description' => 'Learn how to protect secrets in your projects with Ghostable.',
            'meta_keywords' => ['security', 'secrets', 'git'],
        ]);

        Post::factory()->published()->create([
            'category' => PostCategory::CASE_STUDIES,
            'title' => 'Case Study: Scaling Config at Acme Corp',
            'description' => 'How a growing startup keeps environments in sync with Ghostable.',
            'slug' => 'case-study-scaling-config-at-acme-corp',
            'content' => "A behind-the-scenes look at how Acme Corp adopted Ghostable to manage hundreds of variables.\n\nTheir engineering team reduced onboarding time, eliminated stale configuration, and gained visibility into every change across staging and production.",
            'meta_title' => 'Scaling Config at Acme Corp',
            'meta_description' => 'See how Acme Corp uses Ghostable to manage config at scale.',
            'meta_keywords' => ['case study', 'scaling', 'config'],
        ]);

        Post::factory()->published()->create([
            'category' => PostCategory::RELEASE_NOTES,
            'title' => 'Ghostable 1.0 Release Notes',
            'description' => 'Highlights from the first major release of Ghostable.',
            'slug' => 'ghostable-1-0-release-notes',
            'content' => "Get the details on new features and improvements in Ghostable 1.0.\n\nFrom a redesigned dashboard to improved access controls, this release lays the groundwork for a host of upcoming enhancements.",
            'meta_title' => 'Ghostable 1.0 Release Notes',
            'meta_description' => "Discover what's new in the Ghostable 1.0 release.",
            'meta_keywords' => ['release notes', 'ghostable'],
        ]);

        Post::factory()->published()->create([
            'category' => PostCategory::BEST_PRACTICES,
            'title' => 'Automating Environment Setup with Ghostable',
            'description' => 'Save time by scripting your project onboarding.',
            'slug' => 'automating-environment-setup-with-ghostable',
            'content' => "Setting up a new project should be a joy, not a chore.\n\nIn this article we show how Ghostable can be combined with simple scripts to provision variables and secrets for developers in seconds.",
            'meta_title' => 'Automating Environment Setup with Ghostable',
            'meta_description' => 'Use Ghostable to bootstrap new projects quickly.',
            'meta_keywords' => ['automation', 'onboarding'],
        ]);

        Post::factory()->published()->create([
            'category' => PostCategory::SECURITY,
            'title' => 'How Ghostable Ensures Compliance',
            'description' => 'Meet regulatory requirements without the headache.',
            'slug' => 'how-ghostable-ensures-compliance',
            'content' => "Compliance audits don't have to be terrifying.\n\nGhostable provides detailed history and role-based access so you can demonstrate control over configuration data whenever questions arise.",
            'meta_title' => 'How Ghostable Ensures Compliance',
            'meta_description' => 'Understand Ghostable features that support compliance initiatives.',
            'meta_keywords' => ['compliance', 'audit'],
        ]);

        Post::factory()->published()->create([
            'category' => PostCategory::CASE_STUDIES,
            'title' => 'From .env Chaos to Clarity',
            'description' => 'One team’s journey to organized configuration.',
            'slug' => 'from-env-chaos-to-clarity',
            'content' => "Before using Ghostable, the team juggled dozens of mismatched .env files.\n\nToday their configuration lives in one place, changes are reviewed, and production stays in sync with staging and development.",
            'meta_title' => 'From .env Chaos to Clarity',
            'meta_description' => 'Read how a team tamed environment sprawl with Ghostable.',
            'meta_keywords' => ['case study', 'environment'],
        ]);
    }
}
