<?php

use App\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('post model accessors work', function () {
    $content = str_repeat('word ', 400);
    $post = Post::factory()->published()->create([
        'content' => $content,
        'slug' => 'example',
        'meta_title' => 'Example',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);

    expect($post->word_count)->toBe(400)
        ->and($post->read_time)->toBe(2.0)
        ->and($post->directory)->toBe("blog/{$post->id}");

    $tag = $post->toSitemapTag();
    expect($tag->url)->toBe(route('blog.view', $post->slug));

    $rendered = $post->renderedContent();
    expect($rendered)->toBeString();
});
