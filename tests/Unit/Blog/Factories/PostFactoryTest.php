<?php

use App\Blog\Enums\PostStatus;
use App\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('post factory creates draft posts by default', function () {
    $post = Post::factory()->create([
        'meta_title' => 'title',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);
    expect($post->status)->toBe(PostStatus::DRAFT)
        ->and($post->is_featured)->toBeFalse();
});

test('post factory states work', function () {
    $published = Post::factory()->published()->featured()->create([
        'meta_title' => 'title',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);
    $archived = Post::factory()->archived()->create([
        'meta_title' => 'title',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);

    expect($published->status)->toBe(PostStatus::PUBLISHED)
        ->and($published->is_featured)->toBeTrue();
    expect($archived->status)->toBe(PostStatus::ARCHIVED);
});
