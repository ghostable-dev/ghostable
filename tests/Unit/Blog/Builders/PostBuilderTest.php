<?php

use App\Blog\Enums\PostStatus;
use App\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('post builder filters by status', function () {
    $draft = Post::factory()->create([
        'meta_title' => 'title',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);
    $published = Post::factory()->published()->create([
        'meta_title' => 'title',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);
    $archived = Post::factory()->archived()->create([
        'meta_title' => 'title',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);

    expect(Post::query()->draft()->first()->is($draft))->toBeTrue();
    expect(Post::query()->published()->first()->is($published))->toBeTrue();
    expect(Post::query()->archived()->first()->is($archived))->toBeTrue();
    expect(Post::query()->withStatus(PostStatus::DRAFT)->first()->is($draft))->toBeTrue();
});
