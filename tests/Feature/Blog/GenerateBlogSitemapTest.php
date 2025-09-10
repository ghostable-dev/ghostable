<?php

use App\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('blog sitemap lists published posts', function () {
    $posts = Post::factory()->count(2)->published()->create();

    $response = $this->get('/sitemap-blog.xml');

    $response->assertOk();
    foreach ($posts as $post) {
        $response->assertSee($post->slug, false);
    }
});
