<?php

use App\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('published posts can be viewed', function () {
    $post = Post::factory()->published()->create([
        'meta_title' => 'title',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);
    $this->get(route('blog.view', $post))->assertOk();
});

test('draft posts return not found', function () {
    $post = Post::factory()->create([
        'meta_title' => 'title',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);
    $this->get(route('blog.view', $post))->assertNotFound();
});
