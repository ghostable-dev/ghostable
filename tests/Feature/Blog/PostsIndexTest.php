<?php

use App\Blog\Livewire\PostsIndex;
use App\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('posts index component lists posts', function () {
    $featured = Post::factory()->published()->featured()->create(['title' => 'Featured']);
    $post = Post::factory()->published()->create(['title' => 'Regular']);

    Livewire::test(PostsIndex::class, ['category' => null])
        ->assertSee('Featured')
        ->assertSee('Regular');
});
