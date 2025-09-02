<?php

use App\Blog\Livewire\PostShow;
use App\Blog\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('post show component renders', function () {
    $post = Post::factory()->published()->create([
        'title' => 'Example',
        'meta_title' => 'Example',
        'meta_description' => 'desc',
        'meta_keywords' => ['a'],
    ]);

    Livewire::test(PostShow::class, ['post' => $post])
        ->assertSee('Example');
});
