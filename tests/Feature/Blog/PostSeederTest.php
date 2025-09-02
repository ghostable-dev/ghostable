<?php

use App\Blog\Models\Post;
use App\Blog\Seeders\PostSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('post seeder seeds posts', function () {
    $this->seed(PostSeeder::class);
    expect(Post::count())->toBeGreaterThan(0);
});
